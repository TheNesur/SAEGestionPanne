<?php

namespace App\Entity;

use App\Repository\MachineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MachineRepository::class)]
class Machine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $serial_number = null;

    #[ORM\Column(length: 100)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $creation_date = null;

    #[ORM\Column(length: 100)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $production_date = null;

    #[ORM\ManyToOne(inversedBy: 'machines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cabinet $cabinet = null;

    /**
     * @var Collection<int, Breakdown>
     */
    #[ORM\OneToMany(targetEntity: Breakdown::class, mappedBy: 'machine' , orphanRemoval: true)]
    private Collection $breakdowns;

    /**
     * @param string|null $status
     */
    public function __construct()
    {
        $this->status = "en marche";
        $this->breakdowns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serial_number;
    }

    public function setSerialNumber(string $serial_number): static
    {
        $this->serial_number = $serial_number;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTimeInterface $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProductionDate(): ?\DateTimeInterface
    {
        return $this->production_date;
    }

    public function setProductionDate(\DateTimeInterface $production_date): static
    {
        $this->production_date = $production_date;

        return $this;
    }

    public function getCabinet(): ?cabinet
    {
        return $this->cabinet;
    }

    public function setCabinet(?cabinet $cabinet): static
    {
        $this->cabinet = $cabinet;

        return $this;
    }

    /**
     * @return Collection<int, Breakdown>
     */
    public function getBreakdowns(): Collection
    {
        return $this->breakdowns;
    }

    public function addBreakdown(Breakdown $breakdown): static
    {
        if (!$this->breakdowns->contains($breakdown)) {
            $this->breakdowns->add($breakdown);
            $breakdown->setMachine($this);
        }

        return $this;
    }

    public function removeBreakdown(Breakdown $breakdown): static
    {
        if ($this->breakdowns->removeElement($breakdown)) {
            // set the owning side to null (unless already changed)
            if ($breakdown->getMachine() === $this) {
                $breakdown->setMachine(null);
            }
        }

        return $this;
    }
    public function __toString(): string {

        return $this->serial_number;
    }
}
