<?php

namespace App\Repository;

use App\Entity\Medication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medication>
 */
class MedicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medication::class);
    }

    public function findByNameLike(string $searchTerm): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.category = :category')
            ->setParameter('category', $category)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 