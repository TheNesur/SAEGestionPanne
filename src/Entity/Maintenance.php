<?php

namespace App\Entity;

use App\Repository\MaintenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 100)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $creation_date = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    private ?User $technician = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    /**
     * @var Collection<int, Breakdown>
     */
    #[ORM\OneToMany(targetEntity: Breakdown::class, mappedBy: 'maintenance', orphanRemoval: true)]
    private Collection $breakdowns;

    public function __construct()
    {
        $this->breakdowns = new ArrayCollection();
        $this->setStatus("en attente");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTimeInterface $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getTechnician(): ?user
    {
        return $this->technician;
    }

    public function setTechnician(?user $technician): static
    {
        $this->technician = $technician;

        return $this;
    }

    public function getCreator(): ?user
    {
        return $this->creator;
    }

    public function setCreator(?user $creator): static
    {
        $this->creator = $creator;

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
            $breakdown->setMaintenance($this);
        }

        return $this;
    }

    public function removeBreakdown(Breakdown $breakdown): static
    {
        if ($this->breakdowns->removeElement($breakdown)) {
            // set the owning side to null (unless already changed)
            if ($breakdown->getMaintenance() === $this) {
                $breakdown->setMaintenance(null);
            }
        }

        return $this;
    }
    public function __toString(): string {

        return $this->id;
    }
}
