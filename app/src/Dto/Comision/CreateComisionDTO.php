<?php

namespace App\Dto\Comision;

use Symfony\Component\Validator\Constraints as Assert;

class CreateComisionDTO
{
    // FK requerida: id de Actividad a la que pertenece la Comisión
    #[Assert\NotNull(message: 'El id de la actividad es obligatorio.')]
    #[Assert\Type(type: 'integer', message: 'El id de la actividad debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la actividad debe ser positivo.')]
    public ?int $actividadId = null;

    // Nombre de la comisión
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'El nombre de la comisión debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre de la comisión no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $comision = null;
}
