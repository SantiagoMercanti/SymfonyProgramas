<?php

namespace App\Entity;

use App\Repository\ActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

// API Platform
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;

#[ORM\Entity(repositoryClass: ActividadRepository::class)]
#[ORM\Table(name: 'actividad')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/actividades',
            normalizationContext: [
                'groups' => ['actividad:list', 'programa:rel', 'tipoActividad:rel']
            ]
        ),
        new Get(
            uriTemplate: '/actividades/{id}',
            normalizationContext: [
                'groups' => ['actividad:detail', 'programa:rel', 'tipoActividad:rel']
            ]
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'actividad' => 'partial',
    'programa.id' => 'exact',
    'tipoActividad.id' => 'exact',
])]
#[ApiFilter(BooleanFilter::class, properties: ['activo'])]
#[ApiFilter(OrderFilter::class, properties: ['actividad', 'id'])]
class Actividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_actividad', type: 'integer')]
    #[Groups(['actividad:list', 'actividad:detail', 'actividad:rel'])]
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
    #[Groups(['actividad:list', 'actividad:detail', 'actividad:rel'])]
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

    public function addComision(Comision $comision): static
    {
        if (!$this->comisiones->contains($comision)) {
            $this->comisiones->add($comision);
            $comision->setActividad($this);
        }

        return $this;
    }

    public function removeComision(Comision $comision): static
    {
        if ($this->comisiones->removeElement($comision)) {
            if ($comision->getActividad() === $this) {
                $comision->setActividad(null);
            }
        }

        return $this;
    }
}
