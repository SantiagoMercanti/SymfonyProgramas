<?php

namespace App\Controller;

use App\Dto\Encuentro\CreateEncuentroDTO;
use App\Dto\Encuentro\UpdateEncuentroDTO;
use App\Entity\Encuentro;
use App\Manager\EncuentroManager;
use App\Service\ValidationErrorFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/encuentros', name: 'api_encuentros_')]
class EncuentroController extends AbstractController
{
    public function __construct(
        private EncuentroManager $emgr,
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
            'search'               => $req->query->get('search'),
            // por defecto solo activos (lo decide el Manager)
            'activo'               => $req->query->get('activo'),
            'comisionId'           => $req->query->getInt('comisionId') ?: null,
            'modalidadEncuentroId' => $req->query->getInt('modalidadEncuentroId') ?: null,
            // filtros de fecha opcionales (ISO-8601) 
            'desde'                => $req->query->get('desde'), // fechaHoraInicio >= desde
            'hasta'                => $req->query->get('hasta'), // fechaHoraFin    <= hasta
            'sort'                 => $req->query->get('sort'),  // id|encuentro|inicio|fin|comision|modalidad
            'dir'                  => $req->query->get('dir'),
            'page'                 => max(1, (int)$req->query->get('page', 1)),
            'perPage'              => max(1, (int)$req->query->get('perPage', 10)),
        ];

        $result = $this->emgr->listadoPaginado($filters);

        $data = [
            'items' => $result['items'] ?? [],
            'meta'  => $result['meta']  ?? ['page' => 1, 'perPage' => 10, 'total' => 0, 'pages' => 0],
        ];

        return $this->json($data, 200, [], [
            'groups' => ['encuentro:list', 'comision:rel', 'modalidadEncuentro:rel']
        ]);
    }

    // ---------------------------
    // DETALLE
    // ---------------------------
    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(Encuentro $encuentro): JsonResponse
    {
        $encuentro = $this->emgr->detail($encuentro);

        return $this->json($encuentro, 200, [], [
            'groups' => ['encuentro:detail', 'comision:rel', 'modalidadEncuentro:rel']
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
            /** @var CreateEncuentroDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), CreateEncuentroDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $encuentro = $this->emgr->create($dto);

        // opcional: Location al detalle 
        // $location = $urlGen->generate('api_encuentros_detail', ['id' => $encuentro->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($encuentro, 200, [], [
            'groups' => ['encuentro:detail', 'comision:rel', 'modalidadEncuentro:rel']
        ]);
    }

    // ---------------------------
    // UPDATE (PUT)
    // ---------------------------
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Encuentro $encuentro,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateEncuentroDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateEncuentroDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $encuentro = $this->emgr->update($encuentro, $dto, false);

        return $this->json($encuentro, 200, [], [
            'groups' => ['encuentro:detail', 'comision:rel', 'modalidadEncuentro:rel']
        ]);
    }

    // ---------------------------
    // PATCH (parcial)
    // ---------------------------
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(
        Encuentro $encuentro,
        Request $req,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var UpdateEncuentroDTO $dto */
            $dto = $this->serializer->deserialize($req->getContent(), UpdateEncuentroDTO::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->json(['message' => 'JSON inválido.'], 400);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($this->errorFormatter->format($errors), 422);
        }

        $encuentro = $this->emgr->update($encuentro, $dto, true);

        return $this->json($encuentro, 200, [], [
            'groups' => ['encuentro:detail', 'comision:rel', 'modalidadEncuentro:rel']
        ]);
    }

    // ---------------------------
    // DELETE (soft-delete)
    // ---------------------------
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Encuentro $encuentro): JsonResponse
    {
        $this->emgr->delete($encuentro);
        return new JsonResponse(null, 204);
    }
}
