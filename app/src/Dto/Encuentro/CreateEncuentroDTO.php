<?php

namespace App\Dto\Encuentro;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CreateEncuentroDTO
{
    // FK: Comisión (obligatoria)
    #[Assert\NotNull(message: 'El id de la comisión es obligatorio.')]
    #[Assert\Type(type: 'integer', message: 'El id de la comisión debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la comisión debe ser positivo.')]
    public ?int $comisionId = null;

    // FK: Modalidad de Encuentro (obligatoria)
    #[Assert\NotNull(message: 'El id de la modalidad de encuentro es obligatorio.')]
    #[Assert\Type(type: 'integer', message: 'El id de la modalidad de encuentro debe ser un número entero.')]
    #[Assert\Positive(message: 'El id de la modalidad de encuentro debe ser positivo.')]
    public ?int $modalidadEncuentroId = null;

    // Nombre / título del encuentro (opcional)
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'El nombre del encuentro debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre del encuentro no puede superar los {{ limit }} caracteres.'
    )]
    public ?string $encuentro = null;

    // Fechas en ISO-8601 (ej: 2025-09-20T14:30:00-03:00)
    #[Assert\NotNull(message: 'La fecha/hora de inicio es obligatoria.')]
    #[Assert\DateTime(format: \DateTime::ATOM, message: 'fechaHoraInicio debe estar en formato ISO-8601.')]
    public ?string $fechaHoraInicio = null;

    #[Assert\NotNull(message: 'La fecha/hora de fin es obligatoria.')]
    #[Assert\DateTime(format: \DateTime::ATOM, message: 'fechaHoraFin debe estar en formato ISO-8601.')]
    public ?string $fechaHoraFin = null;

    // Validación de intervalo (fin >= inicio)
    #[Assert\Callback]
    public function validateInterval(ExecutionContextInterface $context): void
    {
        if (!$this->fechaHoraInicio || !$this->fechaHoraFin) {
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
