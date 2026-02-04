<?php

namespace App\Entity;

use App\Repository\GameStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameStateRepository::class)]
#[ORM\Table(name: 'game_state')]

class GameState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Word::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Word $currentWord = null;

    #[ORM\Column(type: 'integer')]
    private int $currentSlot = 16;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $slotDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $today = new \DateTimeImmutable('today');
        $this->slotDate = $today;
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int 
    {
        return $this->id;
    }

    public function getCurrentWord(): ?Word 
    {
        return $this->currentWord;
    
    }
    public function setCurrentWord(Word $word): static 
    {
        $this->currentWord = $word; return $this;
    }

    public function getCurrentSlot(): int 
    {
        return $this->currentSlot;
    }

    public function setCurrentSlot(int $slot): static
    {
        $this->currentSlot = $slot; return $this;
    }

    public function getSlotDate(): \DateTimeImmutable
    {
        return $this->slotDate;
    }

    public function setSlotDate(\DateTimeImmutable $date): static 
    {
        $this->slotDate = $date; return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable 
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $dt): static 
    {
        $this->updatedAt = $dt; return $this;
    }
}