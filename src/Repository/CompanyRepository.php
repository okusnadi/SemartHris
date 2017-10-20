<?php

namespace KejawenLab\Application\SemartHris\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use KejawenLab\Application\SemartHris\Component\Address\Repository\AddressRepositoryInterface;
use KejawenLab\Application\SemartHris\Component\Company\Model\CompanyAddressInterface;
use KejawenLab\Application\SemartHris\Component\Company\Model\CompanyInterface;
use KejawenLab\Application\SemartHris\Component\Company\Repository\CompanyRepositoryInterface;
use KejawenLab\Application\SemartHris\Entity\Company;
use KejawenLab\Application\SemartHris\Entity\CompanyAddress;
use KejawenLab\Application\SemartHris\Entity\CompanyDepartment;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@kejawenlab.com>
 */
class CompanyRepository extends Repository implements CompanyRepositoryInterface, AddressRepositoryInterface
{
    use AddressRepositoryTrait;

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
        $this->initialize($this->entityManager, Company::class);
    }

    /**
     * @param string $id
     *
     * @return null|CompanyInterface
     */
    public function find(string $id): ? CompanyInterface
    {
        return $this->entityManager->getRepository($this->entityClass)->find($id);
    }

    /**
     * @param string $companyAddressId
     *
     * @return null|CompanyAddressInterface
     */
    public function findCompanyAddress(string $companyAddressId): ? CompanyAddressInterface
    {
        return $this->entityManager->getRepository($this->getEntityClass())->find($companyAddressId);
    }

    /**
     * @param null $sortField
     * @param null $sortDirection
     * @param null $dqlFilter
     *
     * @return QueryBuilder
     */
    public function createCompanyDepartmentQueryBuilder($sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        return $this->buildSearch(CompanyDepartment::class, $sortField, $sortDirection, $dqlFilter);
    }

    /**
     * @param null $sortField
     * @param null $sortDirection
     * @param null $dqlFilter
     * @param bool $useCompanyFilter
     *
     * @return QueryBuilder
     */
    public function createCompanyAddressQueryBuilder($sortField = null, $sortDirection = null, $dqlFilter = null, $useCompanyFilter = true)
    {
        return $this->buildSearch($this->getEntityClass(), $sortField, $sortDirection, $dqlFilter, $useCompanyFilter);
    }

    /**
     * @param string $searchQuery
     * @param null   $sortField
     * @param null   $sortDirection
     * @param null   $dqlFilter
     * @param bool   $useCompanyFilter
     *
     * @return QueryBuilder
     */
    public function createSearchCompanyAddressQueryBuilder($searchQuery, $sortField = null, $sortDirection = null, $dqlFilter = null, $useCompanyFilter = true)
    {
        $queryBuilder = $this->createCompanyAddressQueryBuilder($sortField, $sortDirection, $dqlFilter, $useCompanyFilter);

        return $this->createSearchAddressQueryBuilder($queryBuilder, $searchQuery);
    }

    /**
     * @param string $entityClass
     * @param null   $sortField
     * @param null   $sortDirection
     * @param null   $dqlFilter
     * @param bool   $useCompanyFilter
     *
     * @return QueryBuilder
     */
    private function buildSearch(string $entityClass, $sortField = null, $sortDirection = null, $dqlFilter = null, $useCompanyFilter = true)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('entity');
        $queryBuilder->from($entityClass, 'entity');

        if ($useCompanyFilter && $companyId = $this->session->get('companyId')) {
            $queryBuilder->orWhere('entity.company = :query')->setParameter('query', $this->find($companyId));
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
        return CompanyAddress::class;
    }
}
