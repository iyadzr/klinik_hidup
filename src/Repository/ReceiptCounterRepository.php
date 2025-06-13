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
            // Create initial counter starting from 773300
            $counter = new ReceiptCounter();
            $counter->setCounterName('main');
            $counter->setCurrentNumber(773300);
            $this->getEntityManager()->persist($counter);
            $this->getEntityManager()->flush();
            return 773300;
        }
        
        $nextNumber = $counter->incrementAndGet();
        $this->getEntityManager()->flush();
        
        return $nextNumber;
    }
} 