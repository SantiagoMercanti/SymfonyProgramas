<?php

namespace App\Entity;

use App\Repository\ModalidadEncuentroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ModalidadEncuentroRepository::class)]
#[ORM\Table(name: 'modalidad_encuentro')]
class ModalidadEncuentro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_modalidad_encuentro', type: 'integer')]
    #[Groups(['modalidadEncuentro:list', 'modalidadEncuentro:detail', 'modalidadEncuentro:rel'])]
    #[SerializedName('id_modalidad_encuentro')]
    private ?int $id = null;

    #[ORM\Column(name: 'modalidad_encuentro', type: 'string', length: 50, nullable: true)]
    #[Groups(['modalidadEncuentro:list', 'modalidadEncuentro:detail', 'modalidadEncuentro:rel'])]
    private ?string $modalidadEncuentro = null;

    // Soft-delete: activo = true por defecto (no nullable)
    #[ORM\Column(name: 'activo', type: 'boolean', options: ['default' => true])]
    #[Groups(['modalidadEncuentro:detail'])]
    private bool $activo = true;

    /**
     * @var Collection<int, Encuentro>
     */
    #[ORM\OneToMany(targetEntity: Encuentro::class, mappedBy: 'modalidadEncuentro')]
    #[Groups(['modalidadEncuentro:detail'])]
    private Collection $encuentros;

    public function __construct()
    {
        $this->encuentros = new ArrayCollection();
    }

    // --- Getters/Setters ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModalidadEncuentro(): ?string
    {
        return $this->modalidadEncuentro;
    }

    public function setModalidadEncuentro(?string $modalidadEncuentro): static
    {
        $this->modalidadEncuentro = $modalidadEncuentro;

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
            $encuentro->setModalidadEncuentro($this);
        }

        return $this;
    }

    public function removeEncuentro(Encuentro $encuentro): static
    {
        if ($this->encuentros->removeElement($encuentro)) {
            // set the owning side to null (unless already changed)
            if ($encuentro->getModalidadEncuentro() === $this) {
                $encuentro->setModalidadEncuentro(null);
            }
        }

        return $this;
    }
}
