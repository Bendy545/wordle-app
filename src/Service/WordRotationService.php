<?php

namespace App\Service;

use App\Entity\GameState;
use App\Repository\GameStateRepository;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;

final class WordRotationService
{
    public function __construct(private WordRepository $wordRepository, private GameStateRepository $gameStateRepository, private EntityManagerInterface $entityManager, private string $timezone = 'Europe/Prague',)
    {

    }

    public function rotateIfNeeded(?\DateTimeImmutable $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone($this->timezone));
        [$slot, $slotDate] = $this->resolveSlot($now);

        $state = $this->gameStateRepository->getSingleton();
        $initialized = false;

        if (!$state) {
            $state = new GameState();
            $this->entityManager->persist($state);
            $initialized = true;
        }

        if (!$initialized && $this->isSameSlot($state, $slot, $slotDate)) {
            return [
                'changed' => false,
                'initialized' => false,
                'slot' => $slot,
                'date' => $slotDate,
                'word' => $state->getCurrentWord()?->getName(),
            ];
        }

        $newWord = $this->wordRepository->getRandomWord();
        if (!$newWord) {
            throw new \RuntimeException('No active words found.');
        }

        $state->setCurrentWord($newWord)
            ->setCurrentSlot($slot)
            ->setSlotDate($slotDate)
            ->setUpdatedAt($now);

        $this->entityManager->flush();

        return [
            'changed' => true,
            'initialized' => $initialized,
            'slot' => $slot,
            'date' => $slotDate,
            'word' => $newWord->getName(),
        ];
    }

    private function isSameSlot(GameState $state, int $slot, \DateTimeImmutable $slotDate): bool
    {
        return $state->getCurrentSlot() === $slot
            && $state->getSlotDate()->format('Y-m-d') === $slotDate->format('Y-m-d');
    }

    private function resolveSlot(\DateTimeImmutable $now): array
    {
        $hour = (int) $now->format('H');

        $slot = 16;
        $slotDate = new \DateTimeImmutable($now->format('Y-m-d'), $now->getTimezone());

        if ($hour < 8) {
            $slot = 16;
            $slotDate = $slotDate->modify('-1 day');
        } elseif ($hour < 10) {
            $slot = 8;
        } elseif ($hour < 12) {
            $slot = 10;
        } elseif ($hour < 14) {
            $slot = 12;
        } elseif ($hour < 16) {
            $slot = 14;
        } else {
            $slot = 16;
        }

        return [$slot, $slotDate];
    }
}
