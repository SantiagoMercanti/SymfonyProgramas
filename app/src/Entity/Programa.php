<?php

namespace App\Entity;

use App\Repository\ProgramaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProgramaRepository::class)]
#[ORM\Table(name: 'programa')] // ajustar el nombre de la tabla en MySQL
class Programa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_programa', type: 'integer')]
    #[Groups(['programa:list'])]
    private ?int $id = null;

    #[ORM\Column(name: 'programa', type: 'string', length: 100, nullable: true)]
    #[Groups(['programa:list'])]
    private ?string $programa = null;

    #[ORM\Column(name: 'descripcion', type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'vigente', type: 'boolean', nullable: true, options: ['default' => true])]
    #[Groups(['programa:list'])]
    private ?bool $vigente = true;

    // Soft-delete: activo = true por defecto
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    #[Groups(['programa:list'])]
    private bool $activo = true;

    /**
     * @var Collection<int, Actividad>
     */
    #[ORM\OneToMany(targetEntity: Actividad::class, mappedBy: 'programa')]
    private Collection $actividades;

    public function __construct()
    {
        $this->actividades = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrograma(): ?string
    {
        return $this->programa;
    }

    public function setPrograma(?string $programa): static
    {
        $this->programa = $programa;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function isVigente(): ?bool
    {
        return $this->vigente;
    }

    public function setVigente(?bool $vigente): static
    {
        $this->vigente = $vigente;

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

    /**
     * @return Collection<int, Actividad>
     */
    public function getActividades(): Collection
    {
        return $this->actividades;
    }

    public function addActividad(Actividad $actividad): static
    {
        if (!$this->actividades->contains($actividad)) {
            $this->actividades->add($actividad);
            $actividad->setPrograma($this);
        }

        return $this;
    }

    public function removeActividad(Actividad $actividad): static
    {
        if ($this->actividades->removeElement($actividad)) {
            if ($actividad->getPrograma() === $this) {
                $actividad->setPrograma(null);
            }
        }

        return $this;
    }
}
