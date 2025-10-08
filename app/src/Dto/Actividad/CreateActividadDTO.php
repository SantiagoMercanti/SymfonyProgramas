<?php

namespace App\Dto\Actividad;

use Symfony\Component\Validator\Constraints as Assert;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateActividadDTO',
    required: ['programaId','tipoActividadId','actividad'],
    properties: [
        new OA\Property(property: 'programaId', type: 'integer', minimum: 1, example: 10),
        new OA\Property(property: 'tipoActividadId', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'actividad', type: 'string', minLength: 3, maxLength: 200, example: 'Curso de Symfony'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, maxLength: 2000, example: 'Introducción práctica a Symfony'),
    ]
)]

class CreateActividadDTO
{
    /**
     * ID del Programa al que pertenece la Actividad.
     */
    #[Assert\NotBlank(message: 'El programa es requerido.')]
    #[Assert\Positive(message: 'El programa debe ser un ID positivo.')]
    public int $programaId;

    /**
     * ID del Tipo de Actividad.
     */
    #[Assert\NotBlank(message: 'El tipo de actividad es requerido.')]
    #[Assert\Positive(message: 'El tipo de actividad debe ser un ID positivo.')]
    public int $tipoActividadId;

    /**
     * Nombre de la actividad.
     */
    #[Assert\NotBlank(message: 'La actividad es requerida.')]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'La actividad debe tener al menos 3 caracteres.',
        maxMessage: 'La actividad no puede superar los 200 caracteres.'
    )]
    public string $actividad;

    /**
     * Descripción opcional.
     */
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La descripción no puede superar los 2000 caracteres.'
    )]
    public ?string $descripcion = null;

    // "activo" NO se expone en create; el Manager lo setea en true por defecto.
}
