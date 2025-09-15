<?php

namespace App\Manager;

use App\Entity\ModalidadEncuentro;
use App\Repository\ModalidadEncuentroRepository;

class ModalidadEncuentroManager
{
    public function __construct(private ModalidadEncuentroRepository $repo) {}

    /** @return ModalidadEncuentro[] */
    public function listarActivos(): array
    {
        return $this->repo->findActivos();
    }

    public function obtenerActivoPorId(int $id): ?ModalidadEncuentro
    {
        return $this->repo->findOneActivoById($id);
    }
}
