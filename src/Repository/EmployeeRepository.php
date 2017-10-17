<?php

namespace KejawenLab\Application\SemartHris\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use KejawenLab\Application\SemartHris\Component\Address\Repository\AddressRepositoryInterface;
use KejawenLab\Application\SemartHris\Component\Employee\Model\EmployeeAddressInterface;
use KejawenLab\Application\SemartHris\Component\Employee\Model\EmployeeInterface;
use KejawenLab\Application\SemartHris\Component\Employee\Repository\EmployeeRepositoryInterface;
use KejawenLab\Application\SemartHris\Component\Employee\Repository\SupervisorRepositoryInterface;
use KejawenLab\Application\SemartHris\Component\Job\Model\JobLevelInterface;
use KejawenLab\Application\SemartHris\Component\Security\Model\UserInterface;
use KejawenLab\Application\SemartHris\Component\Security\Repository\UserRepositoryInterface;
use KejawenLab\Application\SemartHris\Entity\Employee;
use KejawenLab\Application\SemartHris\Entity\EmployeeAddress;
use KejawenLab\Application\SemartHris\Entity\JobLevel;
use KejawenLab\Application\SemartHris\Util\StringUtil;
use KejawenLab\Library\PetrukUsername\Repository\UsernameInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@kejawenlab.com>
 */
class EmployeeRepository extends AddressRepository implements EmployeeRepositoryInterface, SupervisorRepositoryInterface, UserRepositoryInterface, AddressRepositoryInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface       $session
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->initialize($this->entityManager, Employee::class);
    }

    /**
     * @param string $jobLevelId
     *
     * @return array
     */
    public function findSupervisorByJobLevel(string $jobLevelId): array
    {
        $jobLevel = $this->entityManager->getRepository(JobLevel::class)->find($jobLevelId);
        /** @var JobLevelInterface $parentLevel */
        $parentLevel = $jobLevel->getParent();
        if (!($jobLevel && $parentLevel)) {
            return [];
        }

        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository($this->entityClass);

        $queryBuilder = $repository->createQueryBuilder('e');
        $queryBuilder->addSelect('e.id');
        $queryBuilder->addSelect('e.code');
        $queryBuilder->addSelect('e.fullName');
        $queryBuilder->orWhere($queryBuilder->expr()->eq('e.jobLevel', $queryBuilder->expr()->literal($parentLevel->getId())));
        $queryBuilder->orWhere($queryBuilder->expr()->eq('e.jobLevel', $queryBuilder->expr()->literal($jobLevel->getId())));

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $id
     *
     * @return EmployeeInterface
     */
    public function find(string $id): ? EmployeeInterface
    {
        return $this->entityManager->getRepository($this->entityClass)->find($id);
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function isExist(string $username): bool
    {
        if ($this->findByUsername($username)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $characters
     *
     * @return int
     */
    public function countUsage(string $characters): int
    {
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository($this->entityClass);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $repository->createQueryBuilder('o');
        $queryBuilder->select('COUNT(1)');
        $queryBuilder->andWhere($queryBuilder->expr()->like('o.username', $queryBuilder->expr()->literal(sprintf('%%%s%%', $characters))));

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param UsernameInterface $username
     */
    public function save(UsernameInterface $username): void
    {
        throw new \RuntimeException('This method is not implemented');
    }

    /**
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function findByUsername(string $username): ? UserInterface
    {
        return $this->entityManager->getRepository($this->entityClass)->findOneBy(['username' => $username]);
    }

    /**
     * @param string $employeeAddressId
     *
     * @return null|EmployeeAddressInterface
     */
    public function findEmployeeAddress(string $employeeAddressId): ? EmployeeAddressInterface
    {
        return $this->entityManager->getRepository($this->getEntityClass())->find($employeeAddressId);
    }

    /**
     * @param null $sortField
     * @param null $sortDirection
     * @param null $dqlFilter
     * @param bool $useEmployeeFilter
     *
     * @return QueryBuilder
     */
    public function createEmployeeQueryBuilder($sortField = null, $sortDirection = null, $dqlFilter = null, $useEmployeeFilter = true)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('entity');
        $queryBuilder->from($this->entityClass, 'entity');

        return $queryBuilder;
    }

    /**
     * @param string $searchQuery
     * @param null   $sortField
     * @param null   $sortDirection
     * @param null   $dqlFilter
     * @param bool   $useEmployeeFilter
     *
     * @return QueryBuilder
     */
    public function createSearchEmployeeQueryBuilder($searchQuery, $sortField = null, $sortDirection = null, $dqlFilter = null, $useEmployeeFilter = true)
    {
        $queryBuilder = $this->createEmployeeQueryBuilder($sortField, $sortDirection, $dqlFilter, $useEmployeeFilter);
        $queryBuilder->orWhere('LOWER(entity.code) LIKE :query');
        $queryBuilder->orWhere('LOWER(entity.fullName) LIKE :query');
        $queryBuilder->setParameter('query', sprintf('%%%s%%', StringUtil::lowercase($searchQuery)));

        return $queryBuilder;
    }

    /**
     * @param null $sortField
     * @param null $sortDirection
     * @param null $dqlFilter
     * @param bool $useEmployeeFilter
     *
     * @return QueryBuilder
     */
    public function createEmployeeAddressQueryBuilder($sortField = null, $sortDirection = null, $dqlFilter = null, $useEmployeeFilter = true)
    {
        return $this->buildEmployeeSearch($this->getEntityClass(), $sortField, $sortDirection, $dqlFilter, $useEmployeeFilter);
    }

    /**
     * @param string $searchQuery
     * @param null   $sortField
     * @param null   $sortDirection
     * @param null   $dqlFilter
     * @param bool   $useEmployeeFilter
     *
     * @return QueryBuilder
     */
    public function createSearchEmployeeAddressQueryBuilder($searchQuery, $sortField = null, $sortDirection = null, $dqlFilter = null, $useEmployeeFilter = true)
    {
        $queryBuilder = $this->createEmployeeAddressQueryBuilder($sortField, $sortDirection, $dqlFilter, $useEmployeeFilter);

        return $this->createSearchAddressQueryBuilder($queryBuilder, $searchQuery);
    }

    /**
     * @param string $entityClass
     * @param null   $sortField
     * @param null   $sortDirection
     * @param null   $dqlFilter
     * @param bool   $useEmployeeFilter
     *
     * @return QueryBuilder
     */
    private function buildEmployeeSearch(string $entityClass, $sortField = null, $sortDirection = null, $dqlFilter = null, $useEmployeeFilter = true)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('entity');
        $queryBuilder->from($entityClass, 'entity');

        if ($useEmployeeFilter && $employeeId = $this->session->get('employeeId')) {
            $queryBuilder->orWhere('entity.employee = :query')->setParameter('query', $this->find($employeeId));
        }

        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.'.$sortField, $sortDirection ?: 'DESC');
        }

        return $queryBuilder;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return EmployeeAddress::class;
    }
}