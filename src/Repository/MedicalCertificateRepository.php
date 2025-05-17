<?php

namespace App\Repository;

use App\Entity\MedicalCertificate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MedicalCertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalCertificate::class);
    }

    public function generateCertificateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastCert = $this->createQueryBuilder('mc')
            ->select('mc.certificateNumber')
            ->where('mc.certificateNumber LIKE :prefix')
            ->setParameter('prefix', "MC-$year$month%")
            ->orderBy('mc.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastCert) {
            $sequence = '001';
        } else {
            $lastSequence = substr($lastCert['certificateNumber'], -3);
            $sequence = str_pad((int)$lastSequence + 1, 3, '0', STR_PAD_LEFT);
        }

        return "MC-$year$month-$sequence";
    }
}
