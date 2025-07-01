<?php

namespace App\Repository;

use App\Entity\Queue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Queue::class);
    }

    public function findTodayQueue()
    {
        $today = \App\Service\TimezoneService::startOfDay();
        $tomorrow = \App\Service\TimezoneService::startOfDay('tomorrow');

        return $this->createQueryBuilder('q')
            ->andWhere('q.queueDateTime >= :today')
            ->andWhere('q.queueDateTime < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('q.queueNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextQueueNumber(): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $result = $this->createQueryBuilder('q')
            ->select('MAX(q.queueNumber)')
            ->andWhere('q.queueDateTime >= :today')
            ->andWhere('q.queueDateTime < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? $result + 1 : 1;
    }

    public function findByDate(string $date)
    {
        $startOfDay = \App\Service\TimezoneService::startOfDay($date);
        $endOfDay = \App\Service\TimezoneService::endOfDay($date);

        return $this->createQueryBuilder('q')
            ->leftJoin('q.patient', 'p')
            ->leftJoin('q.doctor', 'd')
            ->andWhere('q.queueDateTime >= :startOfDay')
            ->andWhere('q.queueDateTime <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->orderBy('q.queueNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
