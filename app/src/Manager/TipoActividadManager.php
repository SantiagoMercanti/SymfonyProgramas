<?php

namespace App\Manager;

use App\Entity\TipoActividad;
use App\Repository\TipoActividadRepository;

class TipoActividadManager
{
    public function __construct(private TipoActividadRepository $repo) {}

    /** @return TipoActividad[] */
    public function listarActivos(): array
    {
        return $this->repo->findActivos();
    }

    public function obtenerActivoPorId(int $id): ?TipoActividad
    {
        return $this->repo->findOneActivoById($id);
    }
}
