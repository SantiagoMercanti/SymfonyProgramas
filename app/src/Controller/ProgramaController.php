<?php

namespace App\Controller;

use App\Dto\Programa\CreateProgramaDTO;
use App\Dto\Programa\UpdateProgramaDTO;
use App\Manager\ProgramaManager;
use App\Service\ValidationErrorFormatter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/programas', name: 'api_programas_')]
class ProgramaController extends AbstractController
{
    public function __construct(
        private ProgramaManager $pm,
        private ValidationErrorFormatter $errorFormatter
    ) {}

    // ---------------------------
    // LISTAR (paginado con KNP)
    // ---------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $req, PaginatorInterface $paginator): JsonResponse
    {
        $page    = max(1, $req->query->getInt('page', 1));
        $perPage = max(1, min(100, $req->query->getInt('perPage', 10)));

        $filters = [
            'search'  => $req->query->get('search'),
            'activo'  => $req->query->get('activo', 1), // default: solo activos
            'vigente' => $req->query->get('vigente'),   // sin filtro por defecto
            'sort'    => $req->query->get('sort', 'programa'),
            'dir'     => $req->query->get('dir', 'asc'),
        ];

        $qb = $this->pm->qbListado($filters);

        // $pagination = $paginator->paginate($qb, $page, $perPage);
        $pagination = $paginator->paginate(
            $qb,
            $page,
            $perPage,
            [
                'pageParameterName'        => 'page',
                // Evitamos que KNP lea ?sort/dir (ya ordenamos en Repo)
                'sortFieldParameterName'   => '_sort',
                'sortDirectionParameterName'=> '_dir',
            ]
        );

        return $this->json(
            [
                'items'   => $pagination->getItems(),
                'total'   => $pagination->getTotalItemCount(),
                'page'    => $page,
                'perPage' => $perPage,
                'pages'   => (int) ceil($pagination->getTotalItemCount() / $perPage),
            ],
            Response::HTTP_OK,
            [],
            ['groups' => ['programa:list']]
        );
    }

    // ---------------------------
    // OBTENER DETALLE
    // ---------------------------
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        // Política: mostramos solo activos por defecto
        $programa = $this->pm->obtenerActivoPorId($id); // lanzar 404 si no existe/inactivo

        return $this->json(
            $programa,
            Response::HTTP_OK,
            [],
            ['groups' => ['programa:detail']]
        );
    }

    // ---------------------------
    // CREAR (POST) - deserializa DTO
    // ---------------------------
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $req,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var CreateProgramaDTO $dto */
            $dto = $serializer->deserialize($req->getContent(), CreateProgramaDTO::class, 'json');
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['errors' => ['general' => ['JSON inválido']]],
                Response::HTTP_BAD_REQUEST
            );
        }

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            return $this->json(
                $this->errorFormatter->format($violations), // haciendo uso del service
                Response::HTTP_BAD_REQUEST
            );
        }

        // Reglas de negocio (unicidad de nombre, defaults) van en el Manager
        $programa = $this->pm->crearDesdeDto($dto);

        return $this->json(
            $programa,
            Response::HTTP_CREATED,
            ['Location' => sprintf('/api/programas/%d', $programa->getId())],
            ['groups' => ['programa:detail']]
        );
    }

    // ---------------------------
    // ACTUALIZAR TOTAL (PUT)
    // ---------------------------
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $req,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateProgramaDTO $dto */
            $dto = $serializer->deserialize($req->getContent(), UpdateProgramaDTO::class, 'json');
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['errors' => ['general' => ['JSON inválido']]],
                Response::HTTP_BAD_REQUEST
            );
        }

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            return $this->json(
                $this->errorFormatter->format($violations), // haciendo uso del service
                Response::HTTP_BAD_REQUEST
            );
        }

        $programa = $this->pm->actualizarDesdeDto($id, $dto);

        return $this->json(
            $programa,
            Response::HTTP_OK,
            [],
            ['groups' => ['programa:detail']]
        );
    }

    // ---------------------------
    // ACTUALIZACIÓN PARCIAL (PATCH)
    // ---------------------------
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(
        int $id,
        Request $req,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateProgramaDTO $dto */
            $dto = $serializer->deserialize($req->getContent(), UpdateProgramaDTO::class, 'json');
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['errors' => ['general' => ['JSON inválido']]],
                Response::HTTP_BAD_REQUEST
            );
        }

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            return $this->json(
                $this->errorFormatter->format($violations), // haciendo uso del service
                Response::HTTP_BAD_REQUEST
            );
        }

        $programa = $this->pm->actualizarDesdeDto($id, $dto);

        return $this->json(
            $programa,
            Response::HTTP_OK,
            [],
            ['groups' => ['programa:detail']]
        );
    }

    // ---------------------------
    // ELIMINAR (soft-delete)
    // ---------------------------
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->pm->borradoLogico($id); // set activo=false (404 si no existe)

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
