<?php
namespace App\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Tools\Pagination\Paginator;

class UserRepository extends EntityRepository implements UserLoaderInterface
{
    public function findAll()
    {
        return $this->findBy(array(), array(
            'roles'     => 'ASC',
            'lastname'  => 'ASC',
            'firstname' => 'ASC'
        ));
    }


    public function findByRole($role)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%')
            ->orderBy('u.roles', 'ASC')
            ->orderBy('u.lastname', 'ASC')
            ->orderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findOutOfDate()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = 1')
            ->andWhere('u.registerEndDate <= CURRENT_TIMESTAMP()')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"ROLE_USER"%')
            ->getQuery()
            ->getResult();
    }


    public function findToArchive()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = 0')
            ->andWhere('u.isArchived = 0')
            ->andWhere('DATE_ADD(DATE(u.registerEndDate), 3, \'MONTH\') <= CURRENT_DATE()')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"ROLE_USER"%')
            ->getQuery()
            ->getResult();
    }


    public function archiveUsersDisabled()
    {
        return $this->createQueryBuilder('u')
            ->update('App\Entity\User', 'u')
            ->set('u.isArchived', 1)
            ->andWhere('u.isActive = 0')
            ->andWhere('DATE_ADD(DATE(u.registerEndDate), 3, \'MONTH\') <= CURRENT_DATE()')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"ROLE_USER"%')
            ->getQuery()
            ->execute();
    }


    public function findConsultant($get_results = false, $only_active = false)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isConsultant = 1')
            // Order on theme name
            ->addOrderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname',  'ASC');

        if ($only_active === true)
          $qb->andWhere('u.isActive = 1');

        if ($get_results === true)
          return $qb->getQuery()->getResult();

        return $qb;
    }


    public function findLecturer($get_results = false)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.resetPassword', 'reset_password')
            ->addSelect('reset_password')
            // Get users with Admin's or Publisher's roles
            ->orWhere('u.roles LIKE :role_admin')
            ->setParameter('role_admin', '%"ROLE_ADMIN"%')
            ->orWhere('u.roles LIKE :role_publisher')
            ->setParameter('role_publisher', '%"ROLE_PUBLISHER"%')
            // Order by Last and First name
            ->addOrderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        if ($get_results === true)
          return $qb->getQuery()->getResult();

        return $qb;
    }


    public function findWithoutReferentConsultant()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.referentConsultant IS NULL')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"ROLE_USER"%')
            ->orderBy('u.lastname', 'ASC')
            ->orderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByRoleAndStartLastnameOrFirstname($role, $start_lastname, $start_firstname)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%')
            ->andWhere('u.lastname LIKE :start_lastname')
            ->setParameter('start_lastname', $start_lastname . '%')
            ->andWhere('u.firstname LIKE :start_firstname')
            ->setParameter('start_firstname', $start_firstname . '%')
            ->andWhere('u.isArchived = 0')
            ->addOrderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function getNbUsersByRoleOnLastnameFirstLetter($role)
    {
        return $this->createQueryBuilder('u')
            ->select('SUBSTRING(UPPER(u.lastname), 1, 1) AS users_alpha, COUNT(u.id) AS users_count')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%')
            ->andWhere('u.isArchived = 0')
            ->groupBy('users_alpha')
            ->getQuery()
            ->getResult();
    }


    public function getUsersByRoleAndLastnameLetter($role, $lastname_letter, $page, $max_results = 20)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isArchived = 0')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%')
            ->andWhere('u.lastname LIKE :lastname_letter')
            ->setParameter('lastname_letter', $lastname_letter . '%')
            ->addOrderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setFirstResult(($page - 1) * $max_results)
            ->setMaxResults($max_results);

        $pag = new Paginator($qb);
        return $pag->getQuery()->getResult();
    }


    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function disableUsersOutOfDate()
    {
        return $this->createQueryBuilder('u')
            ->update('App\Entity\User', 'u')
            ->set('u.isActive', 0)
            ->andWhere('u.isActive = 1')
            ->andWhere('u.registerEndDate <= CURRENT_TIMESTAMP()')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"ROLE_USER"%')
            ->getQuery()
            ->execute();
    }
}
