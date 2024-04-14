<?php

namespace App\Repository;

use App\Entity\SurveyStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SurveyStep|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyStep|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyStep[]    findAll()
 * @method SurveyStep[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyStep::class);
    }

    // /**
    //  * @return SurveyStep[] Returns an array of SurveyStep objects
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
    public function findOneBySomeField($value): ?SurveyStep
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
