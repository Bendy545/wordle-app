<?php

namespace App\Repository;

use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('v');

        return [
            'total' => (int) $this->createQueryBuilder('v')
                ->select('COUNT(v.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'unique' => (int) $this->createQueryBuilder('v')
                ->select('COUNT(DISTINCT v.ipHash)')
                ->getQuery()
                ->getSingleScalarResult(),
            'bySource' => $this->createQueryBuilder('v')
                ->select("COALESCE(v.utmSource, 'direct') AS label, COUNT(v.id) AS visits, COUNT(DISTINCT v.ipHash) AS uniq")
                ->groupBy('label')
                ->orderBy('visits', 'DESC')
                ->getQuery()
                ->getResult(),
            'byMedium' => $this->createQueryBuilder('v')
                ->select("COALESCE(v.utmMedium, 'none') AS label, COUNT(v.id) AS visits, COUNT(DISTINCT v.ipHash) AS uniq")
                ->groupBy('label')
                ->orderBy('visits', 'DESC')
                ->getQuery()
                ->getResult(),
            'byCampaign' => $this->createQueryBuilder('v')
                ->select("COALESCE(v.utmCampaign, 'none') AS label, COUNT(v.id) AS visits, COUNT(DISTINCT v.ipHash) AS uniq")
                ->groupBy('label')
                ->orderBy('visits', 'DESC')
                ->getQuery()
                ->getResult(),
        ];
    }
}