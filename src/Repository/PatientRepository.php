<?php

namespace App\Repository;

use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    public function findBySearchTerm(string $searchTerm)
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :term')
            ->orWhere('p.email LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTermPaginated(string $searchTerm, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('p')
            ->where('p.name LIKE :term')
            ->orWhere('p.nric LIKE :term')
            ->orWhere('p.phone LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('p.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countBySearchTerm(string $searchTerm): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.name LIKE :term')
            ->orWhere('p.nric LIKE :term')
            ->orWhere('p.phone LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
