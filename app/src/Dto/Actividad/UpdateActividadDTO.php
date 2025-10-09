<?php

namespace App\Dto\Actividad;

use Symfony\Component\Validator\Constraints as Assert;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateActividadDTO',
    // Como es parcial, no marcamos required
    properties: [
        new OA\Property(
            property: 'programaId',
            type: 'integer',
            nullable: true,
            example: 5,
            description: 'ID del programa asociado. Opcional.'
        ),
        new OA\Property(
            property: 'tipoActividadId',
            type: 'integer',
            nullable: true,
            example: 2,
            description: 'ID del tipo de actividad asociado. Opcional.'
        ),
        new OA\Property(
            property: 'actividad',
            type: 'string',
            nullable: true,
            minLength: 3,
            maxLength: 200,
            example: 'Curso de Symfony actualizado',
            description: 'Nombre de la actividad. Opcional.'
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            nullable: true,
            maxLength: 2000,
            example: 'Nueva descripción del curso.',
            description: 'Descripción de la actividad. Opcional.'
        ),
    ]
)]
class UpdateActividadDTO
{
    /**
     * Permite cambiar el Programa asociado (opcional).
     */
    #[Assert\Positive(message: 'El programa debe ser un ID positivo.')]
    public ?int $programaId = null;

    /**
     * Permite cambiar el Tipo de Actividad asociado (opcional).
     */
    #[Assert\Positive(message: 'El tipo de actividad debe ser un ID positivo.')]
    public ?int $tipoActividadId = null;

    /**
     * Nombre de la actividad (opcional).
     * Si viene vacío "", fallará por min=3.
     */
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'La actividad debe tener al menos 3 caracteres.',
        maxMessage: 'La actividad no puede superar los 200 caracteres.'
    )]
    public ?string $actividad = null;

    /**
     * Descripción (opcional).
     */
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La descripción no puede superar los 2000 caracteres.'
    )]
    public ?string $descripcion = null;

    /**
     * Flag opcional para habilitar/deshabilitar, puede llegar a servir si se quiere permitir dar de alta.
     * (DELETE seguirá siendo el camino recomendado para baja lógica).
     */
    // #[Assert\Type(type: 'bool', message: 'El campo activo debe ser booleano.')]
    // public ?bool $activo = null;
}
