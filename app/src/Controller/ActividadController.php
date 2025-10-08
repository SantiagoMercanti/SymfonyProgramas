<?php

namespace App\Controller;

use App\Dto\Actividad\CreateActividadDTO;
use App\Dto\Actividad\UpdateActividadDTO;
use App\Entity\Actividad;
use App\Manager\ActividadManager;
use App\Service\ValidationErrorFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/actividades', name: 'api_actividades_')]
class ActividadController extends AbstractController
{
    public function __construct(
        private ActividadManager $am,
        private ValidationErrorFormatter $errorFormatter,
        private SerializerInterface $serializer,
    ) {}

    // ---------------------------
    // LISTAR (KNP en el Manager)
    // ---------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $req): JsonResponse
    {
        $filters = [
            'search'          => $req->query->get('search'),
            // Manager decidirá: por defecto solo activas
            'activo'          => $req->query->get('activo'),
            'programaId'      => $req->query->getInt('programaId') ?: null,
            'tipoActividadId' => $req->query->getInt('tipoActividadId') ?: null,
            'sort'            => $req->query->get('sort'),
            'dir'             => $req->query->get('dir'),
            'page'            => max(1, (int)$req->query->get('page', 1)),
            'perPage'         => max(1, (int)$req->query->get('perPage', 10)),
        ];

        $result = $this->am->listadoPaginado($filters);

        $data = [
            'items' => $result['items'] ?? [],
            'meta'  => $result['meta']  ?? ['page' => 1, 'perPage' => 10, 'total' => 0, 'pages' => 0],
        ];

        return $this->json($data, 200, [], [
            'groups' => ['actividad:list', 'programa:rel', 'tipoActividad:rel']
        ]);
    }

    // ---------------------------
    // DETALLE (aplica "inactiva = 404" en Manager)
    // ---------------------------
    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(Actividad $actividad): JsonResponse
    {
        $actividad = $this->am->detail($actividad);
        return $this->json($actividad, 200, [], [
            'groups' => ['actividad:detail', 'programa:rel', 'tipoActividad:rel']
        ]);
    }

    // ---------------------------
    // CREAR
    // ---------------------------
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Crea una actividad',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateActividadDTO')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Actividad creada',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['id', 'actividad'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 123),
                        new OA\Property(property: 'actividad', type: 'string', example: 'Curso de Symfony'),
                        new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                        new OA\Property(property: 'programa', type: 'object', nullable: true),
                        new OA\Property(property: 'tipoActividad', type: 'object', nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'JSON inválido'),
            new OA\Response(response: 422, description: 'Validación fallida')
        ]
    )]
    public function create(
        Request $req,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGen
    ): JsonResponse {
        try {
            /** @var CreateActividadDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), CreateActividadDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $actividad = $this->am->create($dto);

        $location = $urlGen->generate('api_actividades_detail', ['id' => $actividad->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($actividad, 200, [], [
            'groups' => ['actividad:detail', 'programa:rel', 'tipoActividad:rel']
        ]);
    }

    // ---------------------------
    // UPDATE (PUT)
    // ---------------------------
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Actividad $actividad,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateActividadDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateActividadDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $actividad = $this->am->update($actividad, $dto, false);
        return $this->json($actividad, 200, [], [
            'groups' => ['actividad:detail', 'programa:rel', 'tipoActividad:rel']
        ]);
    }

    // ---------------------------
    // PATCH
    // ---------------------------
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(
        Actividad $actividad,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateActividadDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateActividadDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $actividad = $this->am->update($actividad, $dto, true);
        return $this->json($actividad, 200, [], [
            'groups' => ['actividad:detail', 'programa:rel', 'tipoActividad:rel']
        ]);
    }

    // ---------------------------
    // DELETE (soft-delete)
    // ---------------------------
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Actividad $actividad): JsonResponse
    {
        $this->am->delete($actividad);
        return new JsonResponse(null, 204);
    }
}
