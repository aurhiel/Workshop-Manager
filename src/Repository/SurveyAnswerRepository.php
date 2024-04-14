<?php

namespace App\Repository;

use App\Entity\SurveyAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SurveyAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyAnswer[]    findAll()
 * @method SurveyAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyAnswer::class);
    }

    public function countByStep($userVSI)
    {
        return $this->createQueryBuilder('sa')
            ->select('ss.id AS survey_step_id, ss.position AS survey_step_position, COUNT(sa.id) AS answer_count')
            // JOIN
            ->leftJoin('sa.surveyQuestion', 'sq')
            ->leftJoin('sq.surveyStep', 'ss')
            // WHERE
            ->andWhere('sa.userVSI = :user_vsi')
            ->setParameter('user_vsi', $userVSI)
            // GROUP BY : for COUNT()
            ->groupBy('sq.surveyStep')
            // ORDER BY
            ->orderBy('ss.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserVSIAndStepPosition($userVSI, $stepPosition)
    {
        return $this->createQueryBuilder('sa')
            // JOIN
            ->leftJoin('sa.surveyGrade', 'sg')
            ->addSelect('sg')
            ->leftJoin('sa.surveyQuestion', 'sq')
            ->leftJoin('sq.surveyStep', 'ss')
            // WHERE
            ->andWhere('sa.userVSI = :user_vsi')
            ->setParameter('user_vsi', $userVSI)
            ->andWhere('ss.position = :step_position')
            ->setParameter('step_position', $stepPosition)
            // ORDER BY
            ->orderBy('sq.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserVSIAndByWorkshops($userVSI, array $workshop)
    {
        $qb = $this->createQueryBuilder('sa')
            // JOIN
            ->leftJoin('sa.surveyGrade', 'sg')
            ->addSelect('sg')
            ->leftJoin('sa.surveyQuestion', 'sq')
            // WHERE
            ->andWhere('sa.userVSI = :user_vsi')
            ->setParameter('user_vsi', $userVSI);

        // WHERE: Workshops IDs
        $qb->andWhere($qb->expr()->in('sq.workshop', $workshop));

        // ORDER BY
        $qb->orderBy('sq.position', 'ASC');

        return $qb->getQuery()
            ->getResult();
    }

    // /**
    //  * @return SurveyAnswer[] Returns an array of SurveyAnswer objects
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
    public function findOneBySomeField($value): ?SurveyAnswer
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
