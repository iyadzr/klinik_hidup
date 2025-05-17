<?php

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctor::class);
    }

    public function findBySpecialization(string $specialization)
    {
        return $this->createQueryBuilder('d')
            ->where('d.specialization = :specialization')
            ->setParameter('specialization', $specialization)
            ->orderBy('d.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableDoctors(\DateTime $dateTime)
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
