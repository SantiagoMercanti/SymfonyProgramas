<?php

namespace App\Manager;

use App\Dto\Programa\CreateProgramaDTO;
use App\Dto\Programa\UpdateProgramaDTO;
use App\Entity\Programa;
use App\Repository\ProgramaRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProgramaManager
{
    public function __construct(private ProgramaRepository $repo) {}

    /**
     * Prepara filtros y retorna un QB listo para paginar.
     *
     * @param array{
     *   search?: string|null,
     *   activo?: string|int|bool|null,
     *   vigente?: string|int|bool|null,
     *   sort?: string|null,
     *   dir?: string|null
     * } $f
     */
    public function qbListado(array $f): QueryBuilder
    {
        $search  = $f['search']  ?? null;

        // activo: default true (1). Si viene "all" o null => no filtra
        $activoParam = $f['activo'] ?? 1;
        $activo = null;
        if ($activoParam !== null && $activoParam !== 'all') {
            $activo = filter_var($activoParam, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($activo === null) {
                // si viene "0"/"1" como string
                if ($activoParam === '0' || $activoParam === 0) $activo = false;
                if ($activoParam === '1' || $activoParam === 1) $activo = true;
            }
        }

        // vigente: si no viene, no filtra
        $vigente = null;
        if (array_key_exists('vigente', $f) && $f['vigente'] !== null && $f['vigente'] !== 'all') {
            $vigente = filter_var($f['vigente'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($vigente === null) {
                if ($f['vigente'] === '0' || $f['vigente'] === 0) $vigente = false;
                if ($f['vigente'] === '1' || $f['vigente'] === 1) $vigente = true;
            }
        }

        $allowedSort = ['id_programa', 'programa', 'vigente', 'activo'];
        $sort = $f['sort'] ?? 'programa';
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'programa';
        }

        $dir = strtolower($f['dir'] ?? 'asc');
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'asc';
        }

        return $this->repo->buildListadoQb($search, $activo, $vigente, $sort, $dir);
    }

    // ---------------------------
    // Obtener detalle (solo activos)
    // ---------------------------
    public function obtenerActivoPorId(int $id): Programa
    {
        $p = $this->repo->find($id);
        if (!$p || !$p->isActivo()) {
            throw new NotFoundHttpException('Programa no encontrado.');
        }
        return $p;
    }

    // ---------------------------
    // Crear desde DTO (setea defaults)
    // ---------------------------
    public function crearDesdeDto(CreateProgramaDTO $dto): Programa
    {
        $nombre = trim($dto->programa);
        $desc   = trim($dto->descripcion);

        $this->assertNombreDisponible($nombre, null);

        $p = new Programa();
        $p->setPrograma($nombre);
        $p->setDescripcion($desc);
        $p->setVigente(true); // default de negocio
        $p->setActivo(true);  // soft-delete flag

        $em = $this->repo->getEntityManager();
        $em->persist($p);
        $em->flush();

        return $p;
    }

    // ---------------------------
    // Actualizar (PUT/PATCH) desde DTO
    // ---------------------------
    public function actualizarDesdeDto(int $id, UpdateProgramaDTO $dto): Programa
    {
        $p = $this->repo->find($id);
        if (!$p || !$p->isActivo()) {
            throw new NotFoundHttpException('Programa no encontrado.');
        }

        if ($dto->programa !== null) {
            $nuevoNombre = trim($dto->programa);
            // Si cambió el nombre (case-insensitive), validar unicidad
            $actualNombre = (string) ($p->getPrograma() ?? '');
            if (mb_strtolower($nuevoNombre) !== mb_strtolower($actualNombre)) {
                $this->assertNombreDisponible($nuevoNombre, $p->getId());
            }
            $p->setPrograma($nuevoNombre);
        }

        if ($dto->descripcion !== null) {
            $p->setDescripcion(trim($dto->descripcion));
        }

        if ($dto->vigente !== null) {
            $p->setVigente($dto->vigente);
        }

        $this->repo->getEntityManager()->flush();

        return $p;
    }

    // ---------------------------
    // Borrado lógico (activo=false)
    // ---------------------------
    public function borradoLogico(int $id): void
    {
        $p = $this->repo->find($id);
        if (!$p || !$p->isActivo()) {
            // Tratamos "no existe" o "ya inactivo" como 404 para mantener semántica REST
            throw new NotFoundHttpException('Programa no encontrado.');
        }

        $p->setActivo(false);
        $this->repo->getEntityManager()->flush();
    }

    // ---------------------------
    // Helper: valida unicidad de nombre (case-insensitive)
    // ---------------------------
    private function assertNombreDisponible(string $nombre, ?int $excludingId): void
    {
        $qb = $this->repo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('LOWER(p.programa) = :n')
            ->setParameter('n', mb_strtolower($nombre));

        if ($excludingId !== null) {
            $qb->andWhere('p.id <> :id')->setParameter('id', $excludingId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        if ($count > 0) {
            // 409 Conflict: recurso con mismo "programa" ya existe
            throw new ConflictHttpException('Ya existe un programa con ese nombre.');
        }
    }
}
