<?php

namespace App\Repository;

use App\Entity\GameSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSettings>
 *
 * @method GameSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameSettings[]    findAll()
 * @method GameSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSettings::class);
    }

    //    /**
    //     * @return GameSettings[] Returns an array of GameSettings objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GameSettings
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
