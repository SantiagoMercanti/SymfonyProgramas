<?php

namespace App\Dto\Encuentro;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateEncuentroDTO
{
    // Todos opcionales para PATCH; con PUT el Manager puede exigir los faltantes.

    #[Assert\Type(type: 'integer', message: 'El id de la comisión debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la comisión debe ser positivo.')]
    public ?int $comisionId = null;

    #[Assert\Type(type: 'integer', message: 'El id de la modalidad de encuentro debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la modalidad de encuentro debe ser positivo.')]
    public ?int $modalidadEncuentroId = null;

    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'El nombre del encuentro debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre del encuentro no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $encuentro = null;

    // Formato ISO-8601 si vienen
    #[Assert\DateTime(format: \DateTime::ATOM, message: 'fechaHoraInicio debe estar en formato ISO-8601.')]
    public ?string $fechaHoraInicio = null;

    #[Assert\DateTime(format: \DateTime::ATOM, message: 'fechaHoraFin debe estar en formato ISO-8601.')]
    public ?string $fechaHoraFin = null;

    #[Assert\Type(type: 'bool', message: 'El campo activo debe ser booleano.')]
    public ?bool $activo = null;

    // Si vienen ambas, validar intervalo
    #[Assert\Callback]
    public function validateInterval(ExecutionContextInterface $context): void
    {
        if ($this->fechaHoraInicio === null || $this->fechaHoraFin === null) {
            return;
        }
        try {
            $ini = new \DateTimeImmutable($this->fechaHoraInicio);
            $fin = new \DateTimeImmutable($this->fechaHoraFin);
            if ($fin < $ini) {
                $context->buildViolation('La fecha/hora de fin debe ser igual o posterior a la de inicio.')
                    ->atPath('fechaHoraFin')
                    ->addViolation();
            }
        } catch (\Exception) {
            // ya lo valida Assert\DateTime
        }
    }
}
