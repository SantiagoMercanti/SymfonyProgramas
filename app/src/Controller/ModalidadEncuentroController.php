<?php

namespace App\Controller;

use App\Manager\ModalidadEncuentroManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/modalidades-encuentro', name: 'api_modalidades_encuentro_')]
class ModalidadEncuentroController extends AbstractController
{
    public function __construct(private ModalidadEncuentroManager $manager) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->manager->listarActivos();
        return $this->json($items, 200, [], ['groups' => ['mod:list']]);
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $item = $this->manager->obtenerActivoPorId($id);
        if (!$item) {
            return $this->json(['error' => 'No encontrado'], 404);
        }
        return $this->json($item, 200, [], ['groups' => ['mod:detail']]);
    }
}
