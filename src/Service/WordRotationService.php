<?php

namespace App\Service;

use App\Entity\GameState;
use App\Repository\GameStateRepository;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;

final class WordRotationService
{
    private const SLOTS = [
        [8, 15],
        [9, 10],
        [10, 10],
        [11, 5],
        [12, 0],
        [12, 55],
        [13, 50],
        [14, 45],
        [15, 40],
    ];

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
            ->setCurrentSlot($slot[0] * 60 + $slot[1])
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

    public function getNextRotationTime(?\DateTimeImmutable $now = null): \DateTimeImmutable
    {
        $tz = new \DateTimeZone($this->timezone);
        $now = $now ?? new \DateTimeImmutable('now', $tz);
        $nowMinutes = (int) $now->format('H') * 60 + (int) $now->format('i');
    
        foreach (self::SLOTS as [$h, $m]) {
            if ($nowMinutes < $h * 60 + $m) {
                return $now->setTime($h, $m, 0);
            }
        }
    
        return $now->modify('+1 day')->setTime(self::SLOTS[0][0], self::SLOTS[0][1], 0);
    }

    private function isSameSlot(GameState $state, array $slot, \DateTimeImmutable $slotDate): bool
    {
        return $state->getCurrentSlot() === $slot[0] * 60 + $slot[1]
            && $state->getSlotDate()->format('Y-m-d') === $slotDate->format('Y-m-d');
    }

    private function resolveSlot(\DateTimeImmutable $now): array
    {
        $nowMinutes = (int) $now->format('H') * 60 + (int) $now->format('i');
        $slotDate = new \DateTimeImmutable($now->format('Y-m-d'), $now->getTimezone());
    
        if ($nowMinutes < self::SLOTS[0][0] * 60 + self::SLOTS[0][1]) {
            $lastSlot = end(self::SLOTS);
            return [$lastSlot, $slotDate->modify('-1 day')];
        }
    
        $current = self::SLOTS[0];
        foreach (self::SLOTS as $slot) {
            $slotMinutes = $slot[0] * 60 + $slot[1];
            if ($nowMinutes >= $slotMinutes) {
                $current = $slot;
            } else {
                break;
            }
        }
    
        return [$current, $slotDate];
    }
}
