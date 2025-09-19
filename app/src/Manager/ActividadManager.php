<?php

namespace App\Manager;

use App\Dto\Actividad\CreateActividadDTO;
use App\Dto\Actividad\UpdateActividadDTO;
use App\Entity\Actividad;
use App\Entity\Programa;
use App\Entity\TipoActividad;
use App\Repository\ActividadRepository;
use App\Repository\ProgramaRepository;
use App\Repository\TipoActividadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActividadManager
{
    public function __construct(
        private EntityManagerInterface   $em,
        private PaginatorInterface       $paginator,
        private ActividadRepository      $repo,
        private ProgramaRepository       $programaRepo,
        private TipoActividadRepository  $tipoRepo,
    ) {}

    /**
     * Listado paginado. El Manager decide la política por defecto:
     * - Por defecto solo ACTIVAS (activo=1).
     * - Si llega 'activo=all' no filtra.
     * - Si llega 'activo=0' lista solo inactivas.
     *
     * Filtros esperados:
     *  - search, activo, programaId, tipoActividadId, sort, dir, page, perPage
     */
    public function listadoPaginado(array $f): array
    {
        // Default: solo activas
        if (!array_key_exists('activo', $f)) {
            $f['activo'] = 1;
        }

        $page    = max(1, (int)($f['page']    ?? 1));
        $perPage = max(1, (int)($f['perPage'] ?? 10));

        $query = $this->repo->buildListadoQuery($f);

        $pagination = $this->paginator->paginate($query, $page, $perPage);
        $itemCount = $pagination->getTotalItemCount();

        return [
            'items' => $pagination->getItems(),
            'meta'  => [
                'page'    => $page,
                'perPage' => $perPage,
                'total'   => (int)$itemCount,
                'pages'   => (int)ceil(((int)$itemCount) / $perPage),
            ],
        ];
    }

    /**
     * Detail aplicando regla de "eliminada" (activo=false => 404).
     * Se espera que el Controller pase la entidad por ParamConverter.
     */
    public function detail(Actividad $actividad): Actividad
    {
        if (!$actividad->isActivo()) {
            throw new NotFoundHttpException('La actividad solicitada no existe.');
        }
        return $actividad;
    }

    /**
     * Crear Actividad (activo=true por defecto) + unicidad estricta.
     */
    public function create(CreateActividadDTO $dto): Actividad
    {
        // Verificar existencial programa y tipo de actividad indicado
        $programa      = $this->resolvePrograma($dto->programaId);
        $tipoActividad = $this->resolveTipoActividad($dto->tipoActividadId);
        // Verificar unicidad estricta
        $this->assertUnique($programa, $tipoActividad, $dto->actividad, null);

        $a = new Actividad();
        $a->setPrograma($programa);
        $a->setTipoActividad($tipoActividad);
        $a->setActividad($dto->actividad);
        $a->setDescripcion($dto->descripcion);
        $a->setActivo(true);

        $this->em->persist($a);
        $this->em->flush();

        return $a;
    }

    /**
     * Update/Patch aplicando regla de "eliminada" (activo=false => 404) + unicidad.
     * Se espera que el Controller pase la entidad por ParamConverter.
     * - Se agrega una flag $isPatch para distinguir entre PUT (todos los campos) y PATCH (solo los que vienen).
     * -- Sin embargo de momento la función update siempre actua como PATCH.
     */
    public function update(Actividad $actividad, UpdateActividadDTO $dto, bool $isPatch = true): Actividad
    {
        if (!$actividad->isActivo()) {
            throw new NotFoundHttpException('La actividad solicitada no existe.');
        }

        $programa = $actividad->getPrograma();
        $tipo     = $actividad->getTipoActividad();
        $nombre   = $actividad->getActividad();

        if ($dto->programaId !== null) {
            $programa = $this->resolvePrograma($dto->programaId);
            $actividad->setPrograma($programa);
        }
        if ($dto->tipoActividadId !== null) {
            $tipo = $this->resolveTipoActividad($dto->tipoActividadId);
            $actividad->setTipoActividad($tipo);
        }
        if ($dto->actividad !== null) {
            $nombre = $dto->actividad;
            $actividad->setActividad($nombre);
        }
        if ($dto->descripcion !== null) {
            $actividad->setDescripcion($dto->descripcion);
        }

        $this->assertUnique($programa, $tipo, $nombre, $actividad->getId());

        $this->em->flush();
        return $actividad;
    }

    /**
     * Soft-delete: setActivo(false). Idempotente.
     */
    public function delete(Actividad $actividad): void
    {
        if (!$actividad->isActivo()) {
            return; // ya estaba "eliminada"
        }
        $actividad->setActivo(false);
        $this->em->flush();
    }

    // ========= Helpers =========

    private function resolvePrograma(int $programaId): Programa
    {
        $programa = $this->programaRepo->find($programaId);
        if (!$programa) {
            throw new NotFoundHttpException('El Programa especificado no existe.');
        }
        return $programa;
    }

    private function resolveTipoActividad(int $tipoId): TipoActividad
    {
        $tipo = $this->tipoRepo->find($tipoId);
        if (!$tipo) {
            throw new NotFoundHttpException('El Tipo de Actividad especificado no existe.');
        }
        return $tipo;
    }

    private function assertUnique(Programa $p, TipoActividad $t, string $nombre, ?int $exceptId): void
    {
        if ($this->repo->existsByProgramaTipoNombre($p, $t, $nombre, $exceptId)) {
            throw new ConflictHttpException('Ya existe una Actividad con ese nombre para ese Programa y Tipo de Actividad.');
        }
    }
}
