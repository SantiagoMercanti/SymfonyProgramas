<?php

namespace App\Entity;

use App\Repository\ActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ActividadRepository::class)]
#[ORM\Table(name: 'actividad')]
class Actividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_actividad', type: 'integer')]
    #[Groups(['actividad:list', 'actividad:detail'])]
    // Para que JSON exponga "id_actividad" en lugar de "id":
    #[SerializedName('id_actividad')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Programa::class, inversedBy: 'actividades')]
    #[ORM\JoinColumn(name: 'id_programa', referencedColumnName: 'id_programa', nullable: false)]
    // Incluimos el objeto Programa, pero este a su vez solo expondrá lo del grupo programa:rel
    #[Groups(['actividad:list', 'actividad:detail'])]
    private ?Programa $programa = null;

    #[ORM\ManyToOne(targetEntity: TipoActividad::class, inversedBy: 'actividades')]
    #[ORM\JoinColumn(name: 'id_tipo_actividad', referencedColumnName: 'id_tipo_actividad', nullable: false)]
    #[Groups(['actividad:list', 'actividad:detail'])]
    private ?TipoActividad $tipoActividad = null;

    #[ORM\Column(name: 'actividad', type: 'string', length: 200, nullable: false)]
    #[Groups(['actividad:list', 'actividad:detail'])]
    private string $actividad;

    #[ORM\Column(name: 'descripcion', type: Types::TEXT, nullable: true)]
    // Solo en detalle para no sobrecargar el listado
    #[Groups(['actividad:detail'])]
    private ?string $descripcion = null;

    // No exponemos "activo" por defecto
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    private bool $activo = true;

    /** @var Collection<int, Comision> */
    #[ORM\OneToMany(targetEntity: Comision::class, mappedBy: 'actividad')]
    // No la exponemos por defecto para evitar payload grande / recursión.
    private Collection $comisiones;

    public function __construct()
    {
        $this->comisiones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrograma(): ?Programa
    {
        return $this->programa;
    }

    public function setPrograma(?Programa $programa): static
    {
        $this->programa = $programa;

        return $this;
    }

    public function getTipoActividad(): ?TipoActividad
    {
        return $this->tipoActividad;
    }

    public function setTipoActividad(?TipoActividad $tipoActividad): static
    {
        $this->tipoActividad = $tipoActividad;

        return $this;
    }

    public function getActividad(): string
    {
        return $this->actividad;
    }

    public function setActividad(string $actividad): static
    {
        $this->actividad = $actividad;

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

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * @return Collection<int, Comision>
     */
    public function getComisiones(): Collection
    {
        return $this->comisiones;
    }

    public function addComisione(Comision $comisione): static
    {
        if (!$this->comisiones->contains($comisione)) {
            $this->comisiones->add($comisione);
            $comisione->setActividad($this);
        }

        return $this;
    }

    public function removeComisione(Comision $comisione): static
    {
        if ($this->comisiones->removeElement($comisione)) {
            // set the owning side to null (unless already changed)
            if ($comisione->getActividad() === $this) {
                $comisione->setActividad(null);
            }
        }

        return $this;
    }
}
