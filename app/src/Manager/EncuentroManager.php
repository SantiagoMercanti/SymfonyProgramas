<?php

namespace App\Manager;

use App\Dto\Encuentro\CreateEncuentroDTO;
use App\Dto\Encuentro\UpdateEncuentroDTO;
use App\Entity\Comision;
use App\Entity\Encuentro;
use App\Entity\ModalidadEncuentro;
use App\Repository\ComisionRepository;
use App\Repository\EncuentroRepository;
use App\Repository\ModalidadEncuentroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EncuentroManager
{
    public function __construct(
        private EncuentroRepository $repo,
        private ComisionRepository $comisionRepo,
        private ModalidadEncuentroRepository $modalidadRepo,
        private EntityManagerInterface $em,
        private PaginatorInterface $paginator,
    ) {}

    /**
     * Arma un QB para el listado con filtros.
     *
     * Filtros soportados:
     *  - search: string|null        (en e.encuentro y c.comision)
     *  - activo: 1|0|"all"|null     (default: solo activos)
     *  - comisionId: int|null
     *  - modalidadEncuentroId: int|null
     *  - desde: ISO-8601|null       (fechaHoraInicio >= desde)
     *  - hasta: ISO-8601|null       (fechaHoraFin    <= hasta)
     *  - sort: id|encuentro|inicio|fin|comision|modalidad
     *  - dir: ASC|DESC
     */
    public function qbListado(array $f): QueryBuilder
    {
        $search   = $f['search']               ?? null;
        $activo   = $f['activo']               ?? 1;
        $cid      = $f['comisionId']           ?? null;
        $mid      = $f['modalidadEncuentroId'] ?? null;
        $desdeStr = $f['desde']                ?? null;
        $hastaStr = $f['hasta']                ?? null;
        $sort     = strtolower((string)($f['sort'] ?? 'inicio'));
        $dir      = strtoupper((string)($f['dir']  ?? 'ASC'));

        $qb = $this->repo->createQueryBuilder('e')
            ->leftJoin('e.comision', 'c')->addSelect('c')
            ->leftJoin('e.modalidadEncuentro', 'm')->addSelect('m');

        // Filtro activo: por defecto solo activos
        if (!($activo === null || $activo === 'all')) {
            $b = $this->toBoolOrNull($activo);
            if ($b !== null) {
                $qb->andWhere('e.activo = :activo')->setParameter('activo', $b);
            } else {
                $qb->andWhere('e.activo = 1'); // default
            }
        } else {
            // no filtra
        }
        if ($activo === null) {
            // default
            $qb->andWhere('e.activo = 1');
        }

        if ($cid) {
            $qb->andWhere('IDENTITY(e.comision) = :cid')->setParameter('cid', (int)$cid);
        }
        if ($mid) {
            $qb->andWhere('IDENTITY(e.modalidadEncuentro) = :mid')->setParameter('mid', (int)$mid);
        }

        if ($search) {
            $s = '%' . mb_strtolower(trim($search)) . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(e.encuentro) LIKE :s',
                    'LOWER(c.comision) LIKE :s'
                )
            )->setParameter('s', $s);
        }

        // Filtros de fecha (si están bien parseados)
        if ($desde = $this->parseIsoDateTime($desdeStr)) {
            $qb->andWhere('e.fechaHoraInicio >= :desde')->setParameter('desde', $desde);
        }
        if ($hasta = $this->parseIsoDateTime($hastaStr)) {
            $qb->andWhere('e.fechaHoraFin <= :hasta')->setParameter('hasta', $hasta);
        }

        // Orden seguro
        $sortMap = [
            'id'        => 'e.id',
            'encuentro' => 'e.encuentro',
            'inicio'    => 'e.fechaHoraInicio',
            'fin'       => 'e.fechaHoraFin',
            'comision'  => 'c.comision',
            'modalidad' => 'm.id',
        ];
        $orderBy = $sortMap[$sort] ?? $sortMap['inicio'];
        $dir     = ($dir === 'DESC') ? 'DESC' : 'ASC';

        $qb->orderBy($orderBy, $dir)->addOrderBy('e.id', 'ASC');

        return $qb;
    }

    /**
     * Retorna items + meta paginada.
     *
     * @return array{items: array<int, Encuentro>, meta: array{page:int, perPage:int, total:int, pages:int}}
     */
    public function listadoPaginado(array $f): array
    {
        $page    = max(1, (int)($f['page']    ?? 1));
        $perPage = max(1, (int)($f['perPage'] ?? 10));

        $qb = $this->qbListado($f);
        $pagination = $this->paginator->paginate($qb, $page, $perPage);

        $total = (int)$pagination->getTotalItemCount();

        return [
            'items' => (array)$pagination->getItems(),
            'meta'  => [
                'page'    => $page,
                'perPage' => $perPage,
                'total'   => $total,
                'pages'   => (int)ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Aplica 404 si está inactivo.
     */
    public function detail(Encuentro $e): Encuentro
    {
        if (!$e->isActivo()) {
            throw new NotFoundHttpException('Encuentro no encontrado.');
        }
        return $e;
    }

    public function create(CreateEncuentroDTO $dto): Encuentro
    {
        $comision = $this->comisionRepo->find($dto->comisionId);
        if (!$comision || (method_exists($comision, 'isActivo') && !$comision->isActivo())) {
            throw new NotFoundHttpException('La comisión indicada no existe o no está activa.');
        }

        $modalidad = $this->modalidadRepo->find($dto->modalidadEncuentroId);
        if (!$modalidad || (method_exists($modalidad, 'isActivo') && !$modalidad->isActivo())) {
            throw new NotFoundHttpException('La modalidad indicada no existe o no está activa.');
        }

        $inicio = $this->requireIso($dto->fechaHoraInicio, 'fechaHoraInicio');
        $fin    = $this->requireIso($dto->fechaHoraFin, 'fechaHoraFin');
        $this->assertFinNoAntesQueInicio($inicio, $fin);

        $e = new Encuentro();
        $e->setComision($comision);
        $e->setModalidadEncuentro($modalidad);
        $e->setEncuentro($dto->encuentro);
        $e->setFechaHoraInicio($inicio);
        $e->setFechaHoraFin($fin);
        $e->setActivo(true);

        $this->em->persist($e);
        $this->em->flush();

        return $e;
    }

    /**
     * @param bool $partial true => PATCH, false => PUT-like (conserva campos no provistos)
     */
    public function update(Encuentro $e, UpdateEncuentroDTO $dto, bool $partial): Encuentro
    {
        if ($dto->comisionId !== null) {
            $comision = $this->comisionRepo->find($dto->comisionId);
            if (!$comision || (method_exists($comision, 'isActivo') && !$comision->isActivo())) {
                throw new NotFoundHttpException('La comisión indicada no existe o no está activa.');
            }
            $e->setComision($comision);
        }

        if ($dto->modalidadEncuentroId !== null) {
            $modalidad = $this->modalidadRepo->find($dto->modalidadEncuentroId);
            if (!$modalidad || (method_exists($modalidad, 'isActivo') && !$modalidad->isActivo())) {
                throw new NotFoundHttpException('La modalidad indicada no existe o no está activa.');
            }
            $e->setModalidadEncuentro($modalidad);
        }

        if ($dto->encuentro !== null || !$partial) {
            $e->setEncuentro($dto->encuentro ?? $e->getEncuentro());
        }

        // Fechas: si viene alguna, recomputar intervalo y validar.
        $nuevoInicio = $e->getFechaHoraInicio();
        $nuevoFin    = $e->getFechaHoraFin();

        if ($dto->fechaHoraInicio !== null || !$partial) {
            $nuevoInicio = $dto->fechaHoraInicio !== null
                ? $this->requireIso($dto->fechaHoraInicio, 'fechaHoraInicio')
                : $nuevoInicio;
        }
        if ($dto->fechaHoraFin !== null || !$partial) {
            $nuevoFin = $dto->fechaHoraFin !== null
                ? $this->requireIso($dto->fechaHoraFin, 'fechaHoraFin')
                : $nuevoFin;
        }

        // Si tenemos ambos, validar orden
        if ($nuevoInicio && $nuevoFin) {
            $this->assertFinNoAntesQueInicio($nuevoInicio, $nuevoFin);
        }

        $e->setFechaHoraInicio($nuevoInicio);
        $e->setFechaHoraFin($nuevoFin);

        if ($dto->activo !== null || !$partial) {
            $e->setActivo($dto->activo ?? $e->isActivo());
        }

        $this->em->flush();

        return $e;
    }

    /**
     * Soft-delete.
     */
    public function delete(Encuentro $e): void
    {
        if (!$e->isActivo()) {
            // idempotente
        }
        $e->setActivo(false);
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

    private function parseIsoDateTime(?string $s): ?\DateTimeImmutable
    {
        if (!$s) return null;
        try {
            return new \DateTimeImmutable($s);
        } catch (\Exception) {
            return null;
        }
    }

    private function requireIso(?string $s, string $field): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable((string)$s);
        } catch (\Throwable) {
            throw new ConflictHttpException(sprintf('Campo %s debe estar en ISO-8601.', $field));
        }
    }

    private function assertFinNoAntesQueInicio(\DateTimeInterface $ini, \DateTimeInterface $fin): void
    {
        if ($fin < $ini) {
            throw new ConflictHttpException('La fecha/hora de fin debe ser igual o posterior a la de inicio.');
        }
    }
}
