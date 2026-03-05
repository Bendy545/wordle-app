<?php

namespace App\Entity;

use App\Repository\PlayerGameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerGameRepository::class)]
#[ORM\Table(name: 'player_game')]
#[ORM\UniqueConstraint(name: 'uniq_session_slot', columns: ['session_id', 'slot_id'])]
class PlayerGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    private string $sessionId;

    #[ORM\Column(length: 20)]
    private string $slotId;

    #[ORM\Column(type: 'json')]
    private array $guesses = [];

    #[ORM\Column]
    private bool $gameOver = false;

    #[ORM\Column]
    private bool $won = false;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $answer = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $sessionId, string $slotId)
    {
        $this->sessionId = $sessionId;
        $this->slotId = $slotId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSessionId(): string { return $this->sessionId; }

    public function getSlotId(): string { return $this->slotId; }

    public function getGuesses(): array { return $this->guesses; }

    public function addGuess(array $guess): static
    {
        $this->guesses[] = $guess;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAttemptCount(): int { return count($this->guesses); }

    public function isGameOver(): bool { return $this->gameOver; }
    public function setGameOver(bool $v): static { $this->gameOver = $v; $this->updatedAt = new \DateTimeImmutable(); return $this; }

    public function hasWon(): bool { return $this->won; }
    public function setWon(bool $v): static { $this->won = $v; return $this; }

    public function getAnswer(): ?string { return $this->answer; }
    public function setAnswer(?string $v): static { $this->answer = $v; return $this; }
}