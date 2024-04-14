<?php

namespace App\Repository;

use App\Entity\Workshop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Workshop|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workshop|null findOneBy(array $criteria, array $orderBy = null)
 * @method Workshop[]    findAll()
 * @method Workshop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkshopRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Workshop::class);
    }

    public function findAll()
    {
        return $this->createQueryBuilder('w')
            // Join relations
            ->leftJoin('w.theme', 'theme')
            ->addSelect('theme')
            ->leftJoin('w.subscribes', 'subscribes')
            ->addSelect('subscribes')
            ->leftJoin('subscribes.user', 'user')
            ->addSelect('user')
            ->leftJoin('user.resetPassword', 'reset_password')
            ->addSelect('reset_password')
            // Join relations
            ->orderBy('w.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWorkshopsToConfirm()
    {
        return $this->createQueryBuilder('w')
            ->andWhere('DATE(w.date_start) = DATE_ADD(CURRENT_DATE(), :nb_day, \'DAY\')')
            ->setParameter('nb_day', Workshop::DAYS_BEFORE_STOPPING_SUBSCRIBE)
            ->andWhere('w.is_VSI_type IS NULL OR w.is_VSI_type = false')
            ->leftJoin('w.subscribes', 'subscribes')
            ->addSelect('subscribes')
            ->leftJoin('subscribes.user', 'user')
            ->addSelect('user')
            // Join relations
            ->orderBy('w.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWorkshopsByDate($date_start, $date_end)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.is_VSI_type IS NULL OR w.is_VSI_type = false')
            ->andWhere('DATE(w.date_start) >= :start')
            ->setParameter('start', $date_start)
            ->andWhere('DATE(w.date_end) <= :end')
            ->setParameter('end', $date_end)
            // Join relations
            ->leftJoin('w.theme', 'theme')
            ->addSelect('theme')
            ->leftJoin('w.subscribes', 'subscribes')
            ->addSelect('subscribes')
            ->leftJoin('subscribes.user', 'user')
            ->addSelect('user')
            // Join relations
            ->orderBy('w.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Workshop[] Returns an array of Workshop objects
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
    public function findOneBySomeField($value): ?Workshop
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
