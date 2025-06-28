<?php

namespace App\Repository;

use App\Entity\PrescribedMedication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescribedMedication>
 */
class PrescribedMedicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescribedMedication::class);
    }

    public function findByConsultation(int $consultationId): array
    {
        return $this->createQueryBuilder('pm')
            ->leftJoin('pm.medication', 'm')
            ->where('pm.consultation = :consultationId')
            ->setParameter('consultationId', $consultationId)
            ->orderBy('pm.prescribedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMedicationUsageStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('pm')
            ->select('m.name as medicationName, COUNT(pm.id) as usageCount, SUM(pm.quantity) as totalQuantity')
            ->leftJoin('pm.medication', 'm')
            ->leftJoin('pm.consultation', 'c')
            ->where('c.consultationDate >= :startDate')
            ->andWhere('c.consultationDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('m.id')
            ->orderBy('usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 