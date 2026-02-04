<?php

namespace App\Repository;

use App\Entity\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Word::class);
    }

    public function getRandomWord(): ?Word
    {
        $count = (int) $this->createQueryBuilder('w')
        ->select('COUNT(w.id)')
        ->where('w.isActive = :active')
        ->setParameter('active', true)
        ->getQuery()
        ->getSingleScalarResult();

        if ($count === 0) {
            return null;
        }

        $offset = random_int(0, $count - 1);

        return $this->createQueryBuilder('w')
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.id', 'ASC') 
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function wordExists(string $word): bool
    {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.name = :word')
            ->setParameter('word', strtoupper($word))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}