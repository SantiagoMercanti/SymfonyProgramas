<?php

namespace App\Dto\Programa;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProgramaDTO
{
    // Opcional: si viene, validamos longitud (vacío "" fallará por min=3)
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'El nombre debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $programa = null;

    // Opcional: si viene, solo limitamos longitud máxima
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La descripción no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $descripcion = null;

    // Opcional: puede venir true/false
    public ?bool $vigente = null;
}
