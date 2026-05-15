<?php

namespace App\Repository;

use App\Entity\Cabinet;
use App\Entity\PsyCabinet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PsyCabinet> */
class PsyCabinetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PsyCabinet::class);
    }

    public function findFirstCabinetForPsychologue(User $psychologue): ?Cabinet
    {
        $link = $this->createQueryBuilder('pc')
            ->where('pc.psychologue = :psy')
            ->setParameter('psy', $psychologue)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $link instanceof PsyCabinet ? $link->getCabinet() : null;
    }
}
