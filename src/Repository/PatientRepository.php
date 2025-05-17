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
            ->where('p.firstName LIKE :term')
            ->orWhere('p.lastName LIKE :term')
            ->orWhere('p.email LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('p.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
