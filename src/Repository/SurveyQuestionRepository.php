<?php

namespace App\Repository;

use App\Entity\SurveyQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SurveyQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyQuestion[]    findAll()
 * @method SurveyQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyQuestion::class);
    }

    public function countByStep($survey_id)
    {
        return $this->createQueryBuilder('sq')
            ->select('ss.id AS survey_step_id,
              ss.position AS survey_step_position,
                COUNT(sq.id) AS question_count')
            // JOIN
            ->leftJoin('sq.surveyStep', 'ss')
            ->leftJoin('ss.survey', 's')
            // GROUP BY : for COUNT()
            ->groupBy('sq.surveyStep')
            // WHERE
            ->andWhere('s.id = :survey_id')
            ->setParameter('survey_id', $survey_id)
            // ORDER BY
            ->orderBy('ss.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return SurveyQuestion[] Returns an array of SurveyQuestion objects
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
    public function findOneBySomeField($value): ?SurveyQuestion
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
