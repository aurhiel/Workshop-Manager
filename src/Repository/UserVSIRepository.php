<?php

namespace App\Repository;

use App\Entity\UserVSI;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method UserVSI|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserVSI|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserVSI[]    findAll()
 * @method UserVSI[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserVSIRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserVSI::class);
    }

    public function countByConsultantAndSurveySlugAndDateBetween($id_consultant, $survey_slug, $date_start, $date_end, $id_cohort = 'all')
    {
        $qb = $this->createQueryBuilder('u_vsi');

        // JOIN : Token & Survey
        $qb->leftJoin('u_vsi.surveyTokens', 'st')
            ->addSelect('st');
        $qb->leftJoin('st.survey', 's')
            ->addSelect('s');

        // WHERE : survey slug = "vsi-1" for example
        if ($survey_slug != null)
            $qb->andWhere('s.slug = :survey_slug')
                ->setParameter('survey_slug', $survey_slug);

        // WHERE : if $id_consultant is valid
        if ($id_consultant > 0)
            $qb->andWhere('u_vsi.referentConsultant = :id_consultant')
                ->setParameter('id_consultant', $id_consultant);

        // WHERE : workshop end date between 2 dates
        $qb->andWhere('DATE(u_vsi.workshopEndDate) >= :start')
            ->setParameter('start', $date_start)
            ->andWhere('DATE(u_vsi.workshopEndDate) <= :end')
            ->setParameter('end', $date_end);
        // $qb->andWhere('FROM_UNIXTIME(st.expiresAt) >= DATE(:start)')
        //     ->setParameter('start', $date_start)
        //     ->andWhere('FROM_UNIXTIME(st.expiresAt) <= DATE(:end)')
        //     ->setParameter('end', $date_end);

        // WHERE : if we need to retrieve a specific cohort
        if ($id_cohort != 'all')
            $qb->andWhere('u_vsi.idCohort = :id_cohort')
                ->setParameter('id_cohort', $id_cohort);

        return count($qb->getQuery()
            ->getResult());
    }

    public function findByConsultantAndSurveySlugAndDateBetween($id_consultant, $survey_slug, $date_start, $date_end, $id_cohort = 'all', $page = 1, $max_results = 20)
    {
        $qb = $this->createQueryBuilder('u_vsi')
            ->select('u_vsi AS userVSI');

        // JOIN : Tokens & Survey
        $qb->leftJoin('u_vsi.surveyTokens', 'st')
            ->addSelect('st');
        $qb->leftJoin('st.survey', 's')
            ->addSelect('s');
        // JOIN : User Answers to count them
        $qb->leftJoin('u_vsi.surveyAnswers', 'sa')
            ->addSelect('COUNT(sa) AS answers_count');

        // WHERE : if $id_consultant is valid
        if ($id_consultant > 0)
            $qb->andWhere('u_vsi.referentConsultant = :id_consultant')
                ->setParameter('id_consultant', $id_consultant);

        // WHERE : survey slug = "vsi-1" for example
        if ($survey_slug != null)
            $qb->andWhere('s.slug = :survey_slug')
                ->setParameter('survey_slug', $survey_slug);

        // WHERE : workshop end date between 2 dates
        $qb->andWhere('DATE(u_vsi.workshopEndDate) >= :start')
            ->setParameter('start', $date_start)
            ->andWhere('DATE(u_vsi.workshopEndDate) <= :end')
            ->setParameter('end', $date_end);
        // $qb->andWhere('FROM_UNIXTIME(st.expiresAt) >= DATE(:start)')
        //     ->setParameter('start', $date_start)
        //     ->andWhere('FROM_UNIXTIME(st.expiresAt) <= DATE(:end)')
        //     ->setParameter('end', $date_end);

        // WHERE : if we need to retrieve a specific cohort
        if ($id_cohort != 'all')
            $qb->andWhere('u_vsi.idCohort = :id_cohort')
                ->setParameter('id_cohort', $id_cohort);

        // GROUP BY
        $qb->groupBy('u_vsi.id, s.id, st.id');

        // ORDER BY
        $qb->addOrderBy('u_vsi.lastname', 'ASC')
           ->addOrderBy('u_vsi.firstname', 'ASC');

        // PAGINATOR
        if (!is_null($page) && !is_null($max_results)) {
            $qb->setFirstResult(($page - 1) * $max_results)
                ->setMaxResults($max_results);

            $pag = new Paginator($qb);
            $results = $pag->getQuery()->getResult();
        } else {
            $results = $qb->getQuery()->getResult();
        }

        $usersVSI = array();
        foreach ($results as $result) {
            $userVSI = $result['userVSI'];

            // Set answers count into user entity
            $userVSI->setSurveyAnswersCount(isset($result['answers_count']) ? $result['answers_count'] : 0);

            $usersVSI[] = $userVSI;
        }

        return $usersVSI;
    }

    public function findToNotifySurvey()
    {
        $qb = $this->createQueryBuilder('u_vsi');

        // JOIN : Tokens & Survey
        $qb->leftJoin('u_vsi.surveyTokens', 'st')
            ->addSelect('st');
        $qb->leftJoin('st.survey', 's')
            ->addSelect('s');

        // WHERE
        $qb->andWhere('u_vsi.workshopEndDate <= CURRENT_TIMESTAMP()');

        return $qb->getQuery()
            ->getResult();
    }

    public function findLastIdCohort()
    {
      return $this->createQueryBuilder('u_vsi')
          ->select('u_vsi.idCohort, SIGNED(u_vsi.idCohort) AS numCohort')
          ->setMaxResults(1)
          ->orderBy('SIGNED(u_vsi.idCohort)', 'DESC')
          ->getQuery()
          ->getSingleResult();
    }

    public function findLastIdCohortByIdConsultantAndSurveySlugAndDateBetween($id_consultant, $survey_slug, $date_start, $date_end, $id_cohort = 'all')
    {
        $qb = $this->createQueryBuilder('u_vsi')
            ->select('u_vsi AS userVSI, u_vsi.idCohort, SIGNED(u_vsi.idCohort) AS numCohort')
            ->where('u_vsi.referentConsultant = :id_consultant')
            ->setParameter('id_consultant', $id_consultant);

        // JOIN : Tokens & Survey
        $qb->leftJoin('u_vsi.surveyTokens', 'st')
            ->addSelect('st');
        $qb->leftJoin('st.survey', 's')
            ->addSelect('s');

        // WHERE : workshop end date between 2 dates
        $qb->andWhere('DATE(u_vsi.workshopEndDate) >= :start')
            ->setParameter('start', $date_start)
            ->andWhere('DATE(u_vsi.workshopEndDate) <= :end')
            ->setParameter('end', $date_end);

        // WHERE : survey slug = "vsi-1" for example
        // $qb->andWhere('s.slug = :survey_slug')
        //     ->setParameter('survey_slug', $survey_slug);

        if ($id_cohort != 'all')
        // WHERE : if we need to retrieve a specific cohort
            $qb->andWhere('u_vsi.idCohort = :id_cohort')
                ->setParameter('id_cohort', $id_cohort);

        // MAX & ORDER
        $qb->setMaxResults(1)
            ->orderBy('SIGNED(u_vsi.idCohort)', 'DESC');

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStartLastnameOrFirstname($start_lastname, $start_firstname)
    {
        return $this->createQueryBuilder('u_vsi')
            ->andWhere('u_vsi.lastname LIKE :start_lastname')
            ->setParameter('start_lastname', $start_lastname . '%')
            ->andWhere('u_vsi.firstname LIKE :start_firstname')
            ->setParameter('start_firstname', $start_firstname . '%')
            ->addOrderBy('u_vsi.lastname', 'ASC')
            ->addOrderBy('u_vsi.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
