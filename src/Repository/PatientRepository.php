<?php

namespace App\Repository;

use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    public function findBySearchTerm(string $searchTerm)
    {
        return $this->createOptimizedSearchQuery($searchTerm)
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTermPaginated(string $searchTerm, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createOptimizedSearchQuery($searchTerm)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countBySearchTerm(string $searchTerm): int
    {
        return (int) $this->createOptimizedSearchQuery($searchTerm)
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->useQueryCache(true)
            ->setQueryCacheLifetime(300) // 5 minutes cache
            ->getResult();
    }

    /**
     * Create optimized search query with proper indexing
     */
    private function createOptimizedSearchQuery(string $searchTerm): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');
        
        // Use LIKE with leading wildcard for flexible search but still indexed
        // Order conditions by likelihood of match for optimization
        $qb->where($qb->expr()->orX(
            $qb->expr()->like('p.name', ':term_start'),      // Most specific first
            $qb->expr()->like('p.nric', ':term_start'),      // NRIC exact matches
            $qb->expr()->like('p.phone', ':term_start'),     // Phone number starts
            $qb->expr()->like('p.name', ':term'),            // Name contains
            $qb->expr()->like('p.nric', ':term'),            // NRIC contains
            $qb->expr()->like('p.phone', ':term')            // Phone contains
        ))
        ->setParameter('term_start', $searchTerm . '%')      // Starts with (uses index)
        ->setParameter('term', '%' . $searchTerm . '%')      // Contains (slower)
        ->orderBy('p.name', 'ASC');

        return $qb;
    }

    /**
     * Fast count for pagination without loading entities
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->getQuery()
            ->useQueryCache(true)
            ->setQueryCacheLifetime(600) // 10 minutes cache for counts
            ->getSingleScalarResult();
    }

    /**
     * Get patients with optimized loading for dashboard
     */
    public function findRecentPatients(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.name, p.nric, p.phone, p.dateOfBirth, p.gender')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->useQueryCache(true)
            ->setQueryCacheLifetime(300)
            ->getArrayResult();
    }

    /**
     * Find by NRIC with exact match for faster lookup
     */
    public function findByNricExact(string $nric): ?Patient
    {
        return $this->createQueryBuilder('p')
            ->where('p.nric = :nric')
            ->setParameter('nric', $nric)
            ->getQuery()
            ->useQueryCache(true)
            ->setQueryCacheLifetime(3600) // 1 hour cache for NRIC lookups
            ->getOneOrNullResult();
    }
}
