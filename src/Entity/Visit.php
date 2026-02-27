<?php

namespace App\Entity;

use App\Repository\VisitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitRepository::class)]
#[ORM\Table(name: 'visit')]
class Visit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $utmSource = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $utmMedium = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $utmCampaign = null;

    #[ORM\Column(length: 64)]
    private string $ipHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $visitedAt;

    public function __construct()
    {
        $this->visitedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int { return $this->id; }

    public function getUtmSource(): ?string { return $this->utmSource; }
    public function setUtmSource(?string $v): static { $this->utmSource = $v; return $this; }

    public function getUtmMedium(): ?string { return $this->utmMedium; }
    public function setUtmMedium(?string $v): static { $this->utmMedium = $v; return $this; }

    public function getUtmCampaign(): ?string { return $this->utmCampaign; }
    public function setUtmCampaign(?string $v): static { $this->utmCampaign = $v; return $this; }

    public function getIpHash(): string { return $this->ipHash; }
    public function setIpHash(string $v): static { $this->ipHash = $v; return $this; }

    public function getVisitedAt(): \DateTimeImmutable { return $this->visitedAt; }
}