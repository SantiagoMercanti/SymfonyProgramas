<?php

namespace App\Controller;

use App\Manager\ProgramaManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/programas', name: 'api_programas_')]
class ProgramaController extends AbstractController
{
    public function __construct(private ProgramaManager $pm) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $req, PaginatorInterface $paginator): JsonResponse
    {
        $page    = max(1, $req->query->getInt('page', 1));
        $perPage = max(1, min(100, $req->query->getInt('perPage', 10)));

        $filters = [
            'search' => $req->query->get('search'),
            'activo' => $req->query->get('activo', 1),   // default: solo activos
            'vigente' => $req->query->get('vigente'),     // sin filtro por defecto
            'sort'   => $req->query->get('sort', 'programa'),
            'dir'    => $req->query->get('dir', 'asc'),
        ];

        $qb = $this->pm->qbListado($filters);

        // $pagination = $paginator->paginate($qb, $page, $perPage);
        $pagination = $paginator->paginate(
            $qb,
            $page,
            $perPage,
            [
                'pageParameterName' => 'page',
                // Cambiamos los nombres internos para que KNP NO agarre "sort"
                'sortFieldParameterName' => '_sort',
                'sortDirectionParameterName' => '_dir',
            ]
        );

        // Devolvemos con Groups 'programa:list'
        return $this->json(
            [
                'items'   => $pagination->getItems(),
                'total'   => $pagination->getTotalItemCount(),
                'page'    => $page,
                'perPage' => $perPage,
                'pages'   => (int) ceil($pagination->getTotalItemCount() / $perPage),
            ],
            200,
            [],
            ['groups' => ['programa:list']]
        );
    }
}
