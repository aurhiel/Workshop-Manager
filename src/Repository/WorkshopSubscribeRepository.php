<?php

namespace App\Repository;

use App\Entity\WorkshopSubscribe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WorkshopSubscribe|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkshopSubscribe|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkshopSubscribe[]    findAll()
 * @method WorkshopSubscribe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkshopSubscribeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WorkshopSubscribe::class);
    }

    public function findSubscribedByWorkshop($workshop)
    {
        return $this->createQueryBuilder('sub')
            ->andWhere('sub.status = 1')         // 1 = SUBSCRIBED
            ->andWhere('sub.workshop = :workshop')
            ->setParameter('workshop', $workshop)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByUser($user)
    {
        return $this->createQueryBuilder('sub')
            // Join relations
            ->leftJoin('sub.workshop', 'w')
            ->addSelect('w')
            ->leftJoin('w.theme', 't')
            ->addSelect('t')
            // Join relations
            // WHERE workshop's date >= Today
            // ->andWhere('workshop.date_start >= CURRENT_DATE()')
            // WHERE user
            ->andWhere('sub.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.date_start', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByUserVSI($userVSI)
    {
        return $this->createQueryBuilder('sub')
            // Join relations
            ->leftJoin('sub.workshop', 'w')
            ->addSelect('w')
            ->leftJoin('w.theme', 't')
            ->addSelect('t')
            // Join relations
            // WHERE workshop's date >= Today
            // ->andWhere('workshop.date_start >= CURRENT_DATE()')
            // WHERE user
            ->andWhere('sub.userVSI = :user_vsi')
            ->setParameter('user_vsi', $userVSI)
            ->orderBy('w.date_start', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return WorkshopSubscribe[] Returns an array of WorkshopSubscribe objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WorkshopSubscribe
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
