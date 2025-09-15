<?php

namespace App\Entity;

use App\Repository\EncuentroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EncuentroRepository::class)]
#[ORM\Table(name: 'encuentro')]
class Encuentro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_encuentro', type: 'integer')]
    private ?int $id = null;

    // FK: id_comision (NOT NULL)
    #[ORM\ManyToOne(targetEntity: Comision::class, inversedBy: 'encuentros')]
    #[ORM\JoinColumn(name: 'id_comision', referencedColumnName: 'id_comision', nullable: false)]
    private ?Comision $comision = null;

    // FK: id_modalidad_encuentro (NOT NULL)
    #[ORM\ManyToOne(targetEntity: ModalidadEncuentro::class, inversedBy: 'encuentros')]
    #[ORM\JoinColumn(name: 'id_modalidad_encuentro', referencedColumnName: 'id_modalidad_encuentro', nullable: false)]
    private ?ModalidadEncuentro $modalidadEncuentro = null;

    #[ORM\Column(name: 'encuentro', type: 'string', length: 200, nullable: true)]
    private ?string $encuentro = null;

    #[ORM\Column(name: 'fecha_hora_inicio', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaHoraInicio = null;

    #[ORM\Column(name: 'fecha_hora_fin', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaHoraFin = null;

    // Soft-delete: default true (no nullable)
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    private bool $activo = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComision(): ?Comision
    {
        return $this->comision;
    }

    public function setComision(?Comision $comision): static
    {
        $this->comision = $comision;

        return $this;
    }

    public function getModalidadEncuentro(): ?ModalidadEncuentro
    {
        return $this->modalidadEncuentro;
    }

    public function setModalidadEncuentro(?ModalidadEncuentro $modalidadEncuentro): static
    {
        $this->modalidadEncuentro = $modalidadEncuentro;

        return $this;
    }

    public function getEncuentro(): ?string
    {
        return $this->encuentro;
    }

    public function setEncuentro(?string $encuentro): static
    {
        $this->encuentro = $encuentro;

        return $this;
    }

    public function getFechaHoraInicio(): ?\DateTimeInterface
    {
        return $this->fechaHoraInicio;
    }

    public function setFechaHoraInicio(?\DateTimeInterface $fechaHoraInicio): static
    {
        $this->fechaHoraInicio = $fechaHoraInicio;
        return $this;
    }

    public function getFechaHoraFin(): ?\DateTimeInterface
    {
        return $this->fechaHoraFin;
    }

    public function setFechaHoraFin(?\DateTimeInterface $fechaHoraFin): static
    {
        $this->fechaHoraFin = $fechaHoraFin;
        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }
}
