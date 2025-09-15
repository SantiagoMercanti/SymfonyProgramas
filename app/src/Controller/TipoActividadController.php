<?php

namespace App\Controller;

use App\Manager\TipoActividadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tipos-actividad', name: 'api_tipos_actividad_')]
class TipoActividadController extends AbstractController
{
    public function __construct(private TipoActividadManager $manager) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->manager->listarActivos();
        return $this->json($items, 200, [], ['groups' => ['tipo:list']]);
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $item = $this->manager->obtenerActivoPorId($id);
        if (!$item) {
            return $this->json(['error' => 'No encontrado'], 404);
        }
        return $this->json($item, 200, [], ['groups' => ['tipo:detail']]);
    }
}
