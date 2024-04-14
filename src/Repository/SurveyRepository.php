<?php

namespace App\Repository;

use App\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    public function findAll()
    {
        return $this->createQueryBuilder('s')
            // JOIN
            ->leftJoin('s.surveyGrades', 'sg')
            ->addSelect('sg')
            ->leftJoin('s.surveySteps', 'ss')
            ->addSelect('ss')
            ->leftJoin('ss.surveyQuestions', 'sq')
            ->addSelect('sq')
            // ORDER
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById($id)
    {
        return $this->createQueryBuilder('s')
            // JOIN
            ->leftJoin('s.surveyGrades', 'sg')
            ->addSelect('sg')
            ->leftJoin('s.surveySteps', 'ss')
            ->addSelect('ss')
            ->leftJoin('ss.surveyQuestions', 'sq')
            ->addSelect('sq')
            // WHERE
            ->andWhere('s.id = :survey_id')
            ->setParameter('survey_id', $id)
            // ORDER
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneBySlug($slug)
    {
        return $this->createQueryBuilder('s')
            // JOIN
            ->leftJoin('s.surveyGrades', 'sg')
            ->addSelect('sg')
            ->leftJoin('s.surveySteps', 'ss')
            ->addSelect('ss')
            ->leftJoin('ss.surveyQuestions', 'sq')
            ->addSelect('sq')
            // WHERE
            ->andWhere('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function disableAllDefault()
    {
        return $this->createQueryBuilder('s')
            ->update('App\Entity\Survey', 's')
            ->set('s.isDefault', 0)
            ->andWhere('s.isDefault = 1')
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return Survey[] Returns an array of Survey objects
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
    public function findOneBySomeField($value): ?Survey
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
