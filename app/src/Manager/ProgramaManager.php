<?php

namespace App\Manager;

use App\Repository\ProgramaRepository;
use Doctrine\ORM\QueryBuilder;

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
}
