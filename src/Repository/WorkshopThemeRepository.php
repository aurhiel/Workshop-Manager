<?php

namespace App\Repository;

use App\Entity\WorkshopTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\Tools\Pagination\Paginator;

use Doctrine\ORM\Query\Expr;

/**
 * @method WorkshopTheme|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkshopTheme|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkshopTheme[]    findAll()
 * @method WorkshopTheme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkshopThemeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WorkshopTheme::class);
    }

    public function findOneById($id)
    {
        $result = $this->createQueryBuilder('t')
            ->select('t')
            // Joins
            ->leftJoin('t.workshops', 'w')
            ->addSelect('w')
            ->leftJoin('w.subscribes', 's')
            ->addSelect('s')
            // Where
            ->andWhere('t.id = :theme_id')
            ->setParameter('theme_id', $id)
            // Order
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function counter()
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS counter')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getPaging($page, $max_results = 20)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t AS theme')
            // Retrieve workshops count
            ->leftJoin('t.workshops', 'workshops')
            ->addSelect('COUNT(workshops) AS workshops_count')
            ->groupBy('t.id')
            // Order
            ->orderBy('t.name', 'ASC')
            ->setFirstResult(($page - 1) * $max_results)
            ->setMaxResults($max_results);

        $pag = new Paginator($qb);
        $results = $pag->getQuery()->getResult();

        foreach ($results as $result) {
            $theme = $result['theme'];

            // Set workshops count into theme entity
            $theme->setWorkshopsCount($result['workshops_count']);

            $themes[] = $theme;
        }

        return $themes;
    }

//    /**
//     * @return WorkshopTheme[] Returns an array of WorkshopTheme objects
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
    public function findOneBySomeField($value): ?WorkshopTheme
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
