<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Disponibilite> */
class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * @return Disponibilite[]
     */
    public function findWithCreneauxByCabinet(?int $cabinetId = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.cabinet', 'c')->addSelect('c')
            ->leftJoin('d.creneaux', 'cr')->addSelect('cr')
            ->orderBy('d.jour', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC');

        if ($cabinetId !== null) {
            $qb->andWhere('c.id = :cabinetId')->setParameter('cabinetId', $cabinetId);
        }

        return $qb->getQuery()->getResult();
    }
}
