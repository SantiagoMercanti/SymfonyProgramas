<?php

namespace App\Dto\Comision;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateComisionDTO
{
    // Todos opcionales para soportar PATCH; con PUT, tu Manager puede exigir completar faltantes si querés.

    // Permite mover la comisión a otra Actividad
    #[Assert\Type(type: 'integer', message: 'El id de la actividad debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la actividad debe ser positivo.')]
    public ?int $actividadId = null;

    // Cambiar el nombre de la comisión
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'El nombre de la comisión debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre de la comisión no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $comision = null;

    // Permitir reactivar/desactivar (opcional)
    #[Assert\Type(type: 'bool', message: 'El campo activo debe ser booleano.')]
    public ?bool $activo = null;
}
