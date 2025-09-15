<?php

namespace App\Entity;

use App\Repository\ComisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComisionRepository::class)]
#[ORM\Table(name: 'comision')]
class Comision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_comision', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comisiones')]
    #[ORM\JoinColumn(name: 'id_actividad', referencedColumnName: 'id_actividad', nullable: false)]
    private ?Actividad $actividad = null;

    #[ORM\Column(name: 'comision', type: 'string', length: 200, nullable: true)]
    private ?string $comision = null;

    // Soft-delete: default true
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    private bool $activo = true;

    /**
     * @var Collection<int, Encuentro>
     */
    #[ORM\OneToMany(targetEntity: Encuentro::class, mappedBy: 'comision')]
    private Collection $encuentros;

    public function __construct()
    {
        $this->encuentros = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActividad(): ?Actividad
    {
        return $this->actividad;
    }

    public function setActividad(?Actividad $actividad): static
    {
        $this->actividad = $actividad;

        return $this;
    }

    public function getComision(): ?string
    {
        return $this->comision;
    }

    public function setComision(?string $comision): static
    {
        $this->comision = $comision;

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
     * @return Collection<int, Encuentro>
     */
    public function getEncuentros(): Collection
    {
        return $this->encuentros;
    }

    public function addEncuentro(Encuentro $encuentro): static
    {
        if (!$this->encuentros->contains($encuentro)) {
            $this->encuentros->add($encuentro);
            $encuentro->setComision($this);
        }

        return $this;
    }

    public function removeEncuentro(Encuentro $encuentro): static
    {
        if ($this->encuentros->removeElement($encuentro)) {
            // set the owning side to null (unless already changed)
            if ($encuentro->getComision() === $this) {
                $encuentro->setComision(null);
            }
        }

        return $this;
    }
}
