<?php

namespace App\Entity;

use App\Repository\TipoActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipoActividadRepository::class)]
#[ORM\Table(name: 'tipo_actividad')] // ajustar el nombre de la tabla en MySQL
class TipoActividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_tipo_actividad', type: 'integer')] // ajustar el nombre de la columna en MySQL
    private ?int $id = null;

    #[ORM\Column(name: 'tipo_actividad', type: 'string', length: 100, nullable: false)]
    private string $tipoActividad;

    // Soft-delete: activo = true por defecto (no nullable)
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    private bool $activo = true;

    /**
     * @var Collection<int, Actividad>
     */
    #[ORM\OneToMany(targetEntity: Actividad::class, mappedBy: 'tipoActividad')]
    private Collection $actividades;

    public function __construct()
    {
        $this->actividades = new ArrayCollection();
    }

    // --- Getters/Setters ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipoActividad(): ?string
    {
        return $this->tipoActividad;
    }

    public function setTipoActividad(string $tipoActividad): static
    {
        $this->tipoActividad = $tipoActividad;

        return $this;
    }

    public function isActivo(): bool
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
            $actividad->setTipoActividad($this);
        }
        return $this;
    }

    public function removeActividad(Actividad $actividad): static
    {
        if ($this->actividades->removeElement($actividad)) {
            if ($actividad->getTipoActividad() === $this) {
                $actividad->setTipoActividad(null); // ojo: esto solo si el JoinColumn es nullable
            }
        }
        return $this;
    }
}
