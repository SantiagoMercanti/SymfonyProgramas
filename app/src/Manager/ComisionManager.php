<?php

namespace App\Manager;

use App\Dto\Comision\CreateComisionDTO;
use App\Dto\Comision\UpdateComisionDTO;
use App\Entity\Actividad;
use App\Entity\Comision;
use App\Repository\ActividadRepository;
use App\Repository\ComisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComisionManager
{
    public function __construct(
        private ComisionRepository $repo,
        private ActividadRepository $actividadRepo,
        private EntityManagerInterface $em,
        private PaginatorInterface $paginator,
    ) {}

    /**
     * Prepara filtros y retorna un QB listo para paginar.
     *
     * @param array{
     *   search?: string|null,
     *   activo?: string|int|bool|null,   // default: true, "all" => sin filtro
     *   actividadId?: int|null,
     *   sort?: string|null,               // id|comision|actividad
     *   dir?: string|null                 // ASC|DESC
     * } $f
     */
    public function qbListado(array $f): QueryBuilder
    {
        $search      = $f['search']      ?? null;
        $activoParam = $f['activo']      ?? 1;
        $actividadId = $f['actividadId'] ?? null;
        $sort        = strtolower((string)($f['sort'] ?? 'id'));
        $dir         = strtoupper((string)($f['dir']  ?? 'ASC'));

        $qb = $this->repo->createQueryBuilder('c')
            ->leftJoin('c.actividad', 'a')->addSelect('a');

        // Filtro activo: default solo activas. Si viene "all" o null => no filtra.
        $filtrarActivo = true;
        if ($activoParam === null || $activoParam === 'all') {
            $filtrarActivo = false;
        } else {
            $activo = $this->toBoolOrNull($activoParam);
            if ($activo !== null) {
                $qb->andWhere('c.activo = :activo')->setParameter('activo', $activo);
                $filtrarActivo = false; // ya aplicado
            }
        }
        if ($filtrarActivo) { // default
            $qb->andWhere('c.activo = 1');
        }

        if ($actividadId) {
            $qb->andWhere('IDENTITY(c.actividad) = :actividadId')->setParameter('actividadId', $actividadId);
        }

        if ($search) {
            $like = '%' . mb_strtolower(trim($search)) . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(c.comision) LIKE :s',
                    'LOWER(a.actividad) LIKE :s'
                )
            )->setParameter('s', $like);
        }

        // Orden seguro (whitelist)
        $sortMap = [
            'id'         => 'c.id',
            'comision'   => 'c.comision',
            'actividad'  => 'a.actividad',
        ];
        $orderBy = $sortMap[$sort] ?? $sortMap['id'];
        $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';

        $qb->orderBy($orderBy, $dir)->addOrderBy('c.id', 'ASC');

        return $qb;
    }

    /**
     * Retorna array con items y meta para el listado paginado KNP.
     *
     * @param array{
     *   search?: string|null,
     *   activo?: string|int|bool|null,
     *   actividadId?: int|null,
     *   sort?: string|null,
     *   dir?: string|null,
     *   page?: int|null,
     *   perPage?: int|null
     * } $f
     * @return array{items: array<int, Comision>, meta: array{page:int, perPage:int, total:int, pages:int}}
     */
    public function listadoPaginado(array $f): array
    {
        $page    = max(1, (int)($f['page']    ?? 1));
        $perPage = max(1, (int)($f['perPage'] ?? 10));

        $qb = $this->qbListado($f);

        $pagination = $this->paginator->paginate($qb, $page, $perPage);

        return [
            'items' => (array)$pagination->getItems(),
            'meta'  => [
                'page'    => $page,
                'perPage' => $perPage,
                'total'   => (int)$pagination->getTotalItemCount(),
                'pages'   => (int)ceil(((int)$pagination->getTotalItemCount()) / $perPage),
            ],
        ];
    }

    /**
     * Aplica 404 si la comisión está inactiva (comportamiento usado por el Controller).
     */
    public function detail(Comision $c): Comision
    {
        if (!$c->isActivo()) {
            throw new NotFoundHttpException('Comisión no encontrada.');
        }
        return $c;
    }

    public function create(CreateComisionDTO $dto): Comision
    {
        $actividad = $this->actividadRepo->find($dto->actividadId);
        if (!$actividad) {
            throw new NotFoundHttpException('La actividad indicada no existe.');
        }
        if (method_exists($actividad, 'isActivo') && !$actividad->isActivo()) {
            throw new NotFoundHttpException('La actividad indicada no está activa.');
        }

        if ($dto->comision !== null && $this->existsNombreEnActividad($dto->comision, $actividad)) {
            throw new ConflictHttpException('Ya existe una comisión con ese nombre en la actividad.');
        }

        $c = new Comision();
        $c->setActividad($actividad);
        $c->setComision($dto->comision);
        $c->setActivo(true);

        $this->em->persist($c);
        $this->em->flush();

        return $c;
    }

    /**
     * @param bool $partial Si true (PATCH), solo aplica campos no-null.
     */
    public function update(Comision $c, UpdateComisionDTO $dto, bool $partial): Comision
    {
        // Cambiar actividad
        if ($dto->actividadId !== null) {
            $actividad = $this->actividadRepo->find($dto->actividadId);
            if (!$actividad) {
                throw new NotFoundHttpException('La actividad indicada no existe.');
            }
            if (method_exists($actividad, 'isActivo') && !$actividad->isActivo()) {
                throw new NotFoundHttpException('La actividad indicada no está activa.');
            }
            $c->setActividad($actividad);
        }

        // Cambiar nombre
        if ($dto->comision !== null || !$partial) {
            $nuevoNombre = $dto->comision ?? $c->getComision();
            // Unicidad por (actividad actual, nombre)
            if ($nuevoNombre !== null && $this->existsNombreEnActividad($nuevoNombre, $c->getActividad(), $c->getId())) {
                throw new ConflictHttpException('Ya existe una comisión con ese nombre en la actividad.');
            }
            $c->setComision($dto->comision !== null ? $dto->comision : $c->getComision());
        }

        // Cambiar activo (permitís reactivar/desactivar)
        if ($dto->activo !== null || !$partial) {
            $c->setActivo($dto->activo ?? $c->isActivo());
        }

        $this->em->flush();

        return $c;
    }

    /**
     * Soft-delete: setActivo(false)
     */
    public function delete(Comision $c): void
    {
        if (!$c->isActivo()) {
            // idempotente
        }
        $c->setActivo(false);
        $this->em->flush();
    }

    // ---------------------------
    // Helpers
    // ---------------------------

    private function toBoolOrNull(string|int|bool|null $v): ?bool
    {
        if ($v === null) return null;
        if (is_bool($v)) return $v;

        $s = is_string($v) ? strtolower(trim($v)) : $v;
        return match ($s) {
            1, '1', 'true',  true  => true,
            0, '0', 'false', false => false,
            default => null,
        };
    }

    /**
     * Verifica si existe otra Comisión con el mismo nombre (insensible a mayúsculas)
     * dentro de la misma Actividad. Si se pasa $excludeId, lo excluye del check (para updates).
     */
    private function existsNombreEnActividad(string $nombre, Actividad $actividad, ?int $excludeId = null): bool
    {
        $qb = $this->repo->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.actividad = :act')
            ->andWhere('LOWER(c.comision) = :nombre')
            ->setParameter('act', $actividad)
            ->setParameter('nombre', mb_strtolower(trim($nombre)));

        if ($excludeId) {
            $qb->andWhere('c.id != :id')->setParameter('id', $excludeId);
        }

        // Opcional: solo chequear contra activas
        // $qb->andWhere('c.activo = 1');

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }
}
