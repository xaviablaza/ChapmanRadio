<?php
// Copyright 2017, Michael Pollind <polli104@mail.chapman.edu>, All Right Reserved
namespace CoreBundle\Repository;
use CoreBundle\Entity\Role;
use CoreBundle\Entity\User;
use CoreBundle\Entity\UserRole;
use CoreBundle\Helper\Datatable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository implements UserProviderInterface ,UserLoaderInterface
{

    public function getUsersByRole($role)
    {
        $users = $this->createQueryBuilder('u')
            ->innerJoin('CoreBundle:Role','co','WITH','co.user_id = u.userid')
            ->groupBy('u.user_id')
            ->getQuery()
            ->getResult();
        return $users;
    }

    public function getByToken($token)
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findOneByUsernameOrEmail($username);
        if (!$user) {
            throw new UsernameNotFoundException('No user found for username '.$username);
        }
        return $user;
    }

    public  function  findOneByUsernameOrEmail($username)
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :username')
            ->setParameter('username',$username)
            ->getQuery()
            ->getSingleResult();

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        /** @var User $u */
        $u = $user;
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                $class
            ));
        }
        if (!$refreshedUser = $this->find($u->getId())) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($u->getId())));
        }

        return $refreshedUser;
    }


    private function _filter(Request $request)
    {
        $qb = $this->createQueryBuilder('s');
        if($name = $request->get('name',null))
        {
            $qb->where($qb->expr()->like('name',':name'))
                ->setParameter('name','%' .$name.'%');
        }
        $qb->leftJoin('s.profile','p',"WITH");

        if($firstName = $request->get('firstName',null))
        {
            $qb->andWhere($qb->expr()->like('p.firstName',':firstName'))
                ->setParameter('firstName','%' .$firstName.'%');
        }

        if($lastName = $request->get('lastName',null))
        {
            $qb->andWhere($qb->expr()->like('p.lastName',':lastName'))
                ->setParameter('lastName','%' .$lastName.'%');
        }

        if($mail = $request->get('email',null))
        {
            $qb->andWhere($qb->expr()->like('email',':email'))
                ->setParameter('email','%' .$mail.'%');
        }

        if($username = $request->get('username',null))
        {
            $qb->andWhere($qb->expr()->like('username',':username'))
                ->setParameter('username','%' .$username.'%');
        }

        if($roles = $request->get('roles',null))
        {
            $qb->join('s.roles','r',"WITH");
            if(!is_array($roles))
                $roles = array($roles);

            foreach ($roles as $role)
            {
                $qb->where($qb->expr()->eq('r.name',':role'))
                    ->setParameter('role',$role);
            }
        }


        return $qb;
    }


    public function dataTableFilter(Request $request)
    {
        $qb = $this->_filter($request);
        $dataTable = new Datatable();
        $dataTable->handleSort($request,['name','token','createdAt','updatedAt','username','firstName','lastName']);
        foreach ($dataTable->getSort() as $key => $value)
        {
            switch ($key)
            {
                case 'firstName':
                    $qb->orderBy('p.' . $key,$value);
                    break;
                case 'lastName':
                    $qb->orderBy('p.' . $key,$value);
                    break;
                default:
                    $qb->orderBy('s.' . $key,$value);
                    break;
            }
        }
        $paginator = $this->paginator($qb->getQuery(),
            (int)$request->get('page',0),
            (int)$request->get('entries',10),200);

        $dataTable->setPayload($paginator);
        return $dataTable;
    }

    /**
     * @param Query $query
     * @param $page
     * @param $perPage
     * @param int $limit
     * @return Paginator
     */
    public function  paginator(Query $query,$page,$perPage,$limit = 10)
    {

        $pagination = new Paginator($query);
        $num = $perPage > $limit ? $perPage :  $limit;
        $pagination->getQuery()->setMaxResults($num);
        $pagination->getQuery()->setFirstResult($num * $page);
        return $pagination;
    }

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
            || is_subclass_of($class, $this->getEntityName());
    }
}
