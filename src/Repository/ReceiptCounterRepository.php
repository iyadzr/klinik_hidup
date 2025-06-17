<?php

namespace App\Repository;

use App\Entity\ReceiptCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReceiptCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReceiptCounter::class);
    }

    public function getNextReceiptNumber(): int
    {
        $counter = $this->findOneBy(['counterName' => 'main']);
        
        if (!$counter) {
            // Get starting number from settings
            $settingRepo = $this->getEntityManager()->getRepository(\App\Entity\Setting::class);
            $startingSetting = $settingRepo->findOneBy(['settingKey' => 'system.receipt_number_start']);
            $startingNumber = $startingSetting ? (int)$startingSetting->getSettingValue() : 1;
            
            // Check if this starting number already exists in consultations
            $consultationRepo = $this->getEntityManager()->getRepository(\App\Entity\Consultation::class);
            $existingReceipt = $consultationRepo->createQueryBuilder('c')
                ->select('MAX(CAST(c.receiptNumber AS UNSIGNED)) as maxReceipt')
                ->where('c.receiptNumber IS NOT NULL')
                ->getQuery()
                ->getSingleScalarResult();
            
            // Use the higher of: configured starting number or (max existing + 1)
            $nextNumber = max($startingNumber, ($existingReceipt ?? 0) + 1);
            
            // Create initial counter
            $counter = new ReceiptCounter();
            $counter->setCounterName('main');
            $counter->setCurrentNumber($nextNumber - 1); // Set to one less since incrementAndGet will add 1
            $this->getEntityManager()->persist($counter);
            $this->getEntityManager()->flush();
            
            return $nextNumber;
        }
        
        $nextNumber = $counter->incrementAndGet();
        $this->getEntityManager()->flush();
        
        return $nextNumber;
    }
} 