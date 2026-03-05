<?php

namespace App\Repository;

use App\Entity\PlayerGame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerGame::class);
    }

    public function findBySessionAndSlot(string $sessionId, string $slotId): ?PlayerGame
    {
        return $this->findOneBy([
            'sessionId' => $sessionId,
            'slotId' => $slotId,
        ]);
    }

    public function getOrCreate(string $sessionId, string $slotId): PlayerGame
    {
        $game = $this->findBySessionAndSlot($sessionId, $slotId);

        if (!$game) {
            $game = new PlayerGame($sessionId, $slotId);
            $this->getEntityManager()->persist($game);
        }

        return $game;
    }
}