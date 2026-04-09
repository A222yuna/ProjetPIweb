<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use App\Entity\User;
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
    public function findForPsychologue(User $psychologue): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.cabinet', 'c')->addSelect('c')
            ->leftJoin('c.psyCabinets', 'pc')
            ->andWhere('pc.psychologue = :psy')->setParameter('psy', $psychologue)
            ->orderBy('d.jour', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isOwnedByPsychologue(Disponibilite $disponibilite, User $psychologue): bool
    {
        $count = (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->leftJoin('d.cabinet', 'c')
            ->leftJoin('c.psyCabinets', 'pc')
            ->andWhere('d.id = :id')->setParameter('id', $disponibilite->getId())
            ->andWhere('pc.psychologue = :psy')->setParameter('psy', $psychologue)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function canManageCabinetId(User $psychologue, int $cabinetId): bool
    {
        $count = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(pc.id)')
            ->from(\App\Entity\PsyCabinet::class, 'pc')
            ->andWhere('IDENTITY(pc.cabinet) = :cabinetId')->setParameter('cabinetId', $cabinetId)
            ->andWhere('pc.psychologue = :psy')->setParameter('psy', $psychologue)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
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
