<?php

namespace App\Dto\Actividad;

use Symfony\Component\Validator\Constraints as Assert;

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
