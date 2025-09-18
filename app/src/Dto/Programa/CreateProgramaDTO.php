<?php

namespace App\Dto\Programa;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProgramaDTO
{
    #[Assert\NotBlank(message: 'El nombre del programa es requerido.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'El nombre debe tener al menos 3 caracteres.',
        maxMessage: 'El nombre no puede superar los 100 caracteres.'
    )]
    public string $programa;

    #[Assert\NotBlank(message: 'La descripción es requerida.')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La descripción no puede superar los 1000 caracteres.'
    )]
    public string $descripcion;
}
