<?php

namespace App\Repository;

use App\Entity\SurveyToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SurveyToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyToken[]    findAll()
 * @method SurveyToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyToken::class);
    }

    public function findOneByToken($token)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->setParameter('token', $token)
            // Join relations
            ->join('t.survey', 'survey')
            ->addSelect('survey')
            ->join('t.userVSI', 'userVSI')
            ->addSelect('userVSI')
            // Join relations
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return SurveyToken[] Returns an array of SurveyToken objects
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
    public function findOneBySomeField($value): ?SurveyToken
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
