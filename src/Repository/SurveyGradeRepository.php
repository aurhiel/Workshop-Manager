<?php

namespace App\Repository;

use App\Entity\SurveyGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SurveyGrade|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyGrade|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyGrade[]    findAll()
 * @method SurveyGrade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyGrade::class);
    }

    // /**
    //  * @return SurveyGrade[] Returns an array of SurveyGrade objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SurveyGrade
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
