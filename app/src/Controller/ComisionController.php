<?php

namespace App\Controller;

use App\Dto\Comision\CreateComisionDTO;
use App\Dto\Comision\UpdateComisionDTO;
use App\Entity\Comision;
use App\Manager\ComisionManager;
use App\Service\ValidationErrorFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comisiones', name: 'api_comisiones_')]
class ComisionController extends AbstractController
{
    public function __construct(
        private ComisionManager $cm,
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
            'search'      => $req->query->get('search'),
            // por defecto solo activas
            'activo'      => $req->query->get('activo'),
            'actividadId' => $req->query->getInt('actividadId') ?: null,
            'sort'        => $req->query->get('sort'),
            'dir'         => $req->query->get('dir'),
            'page'        => max(1, (int)$req->query->get('page', 1)),
            'perPage'     => max(1, (int)$req->query->get('perPage', 10)),
        ];

        $result = $this->cm->listadoPaginado($filters);

        $data = [
            'items' => $result['items'] ?? [],
            'meta'  => $result['meta']  ?? ['page' => 1, 'perPage' => 10, 'total' => 0, 'pages' => 0],
        ];

        return $this->json($data, 200, [], [
            'groups' => ['comision:list', 'actividad:rel']
        ]);
    }

    // ---------------------------
    // DETALLE
    // ---------------------------
    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(Comision $comision): JsonResponse
    {
        $comision = $this->cm->detail($comision);

        return $this->json($comision, 200, [], [
            'groups' => ['comision:detail', 'actividad:rel']
        ]);
    }

    // ---------------------------
    // CREAR
    // ---------------------------
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $req,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGen
    ): JsonResponse {
        try {
            /** @var CreateComisionDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), CreateComisionDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $comision = $this->cm->create($dto);

        return $this->json($comision, 200, [], [
            'groups' => ['comision:detail', 'actividad:rel']
        ]);
    }

    // ---------------------------
    // UPDATE (PUT)
    // ---------------------------
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Comision $comision,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateComisionDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateComisionDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $comision = $this->cm->update($comision, $dto, false);

        return $this->json($comision, 200, [], [
            'groups' => ['comision:detail', 'actividad:rel']
        ]);
    }

    // ---------------------------
    // PATCH (parcial)
    // ---------------------------
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(
        Comision $comision,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateComisionDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateComisionDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $comision = $this->cm->update($comision, $dto, true);

        return $this->json($comision, 200, [], [
            'groups' => ['comision:detail', 'actividad:rel']
        ]);
    }

    // ---------------------------
    // DELETE (soft-delete)
    // ---------------------------
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Comision $comision): JsonResponse
    {
        $this->cm->delete($comision);
        return new JsonResponse(null, 204);
    }
}
