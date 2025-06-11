<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.paymentDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.paymentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getSummary()
    {
        $now = new \DateTime();
        $today = new \DateTime('today');
        $monthStart = new \DateTime('first day of this month');
        $yearStart = new \DateTime('first day of January this year');

        $qb = $this->createQueryBuilder('p');

        // Today's stats
        $todayStats = $qb->select('COUNT(p.id) as transactions, SUM(p.amount) as income')
            ->andWhere('p.paymentDate >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getOneOrNullResult();

        // Month's stats
        $monthStats = $qb->select('COUNT(p.id) as transactions, SUM(p.amount) as income')
            ->andWhere('p.paymentDate >= :monthStart')
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getOneOrNullResult();

        // Year's stats
        $yearStats = $qb->select('COUNT(p.id) as transactions, SUM(p.amount) as income')
            ->andWhere('p.paymentDate >= :yearStart')
            ->setParameter('yearStart', $yearStart)
            ->getQuery()
            ->getOneOrNullResult();

        // Payment methods distribution
        $methodStats = $qb->select('p.paymentMethod, COUNT(p.id) as count')
            ->groupBy('p.paymentMethod')
            ->getQuery()
            ->getResult();

        $total = array_sum(array_column($methodStats, 'count'));
        $methodPercentages = [];
        foreach ($methodStats as $stat) {
            $methodPercentages[$stat['paymentMethod']] = $total > 0 
                ? round(($stat['count'] / $total) * 100) 
                : 0;
        }

        return [
            'today' => [
                'transactions' => (int)$todayStats['transactions'] ?? 0,
                'income' => (float)$todayStats['income'] ?? 0,
            ],
            'month' => [
                'transactions' => (int)$monthStats['transactions'] ?? 0,
                'income' => (float)$monthStats['income'] ?? 0,
            ],
            'year' => [
                'transactions' => (int)$yearStats['transactions'] ?? 0,
                'income' => (float)$yearStats['income'] ?? 0,
            ],
            'paymentMethods' => $methodPercentages
        ];
    }

    public function findPaginated(int $page, int $limit, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.consultation', 'c')
            ->leftJoin('c.patient', 'patient')
            ->leftJoin('c.doctor', 'doctor');

        if (isset($criteria['start_date'])) {
            $qb->andWhere('p.paymentDate >= :start_date')
               ->setParameter('start_date', $criteria['start_date']);
        }

        if (isset($criteria['end_date'])) {
            $qb->andWhere('p.paymentDate <= :end_date')
               ->setParameter('end_date', $criteria['end_date']);
        }

        return $qb->orderBy('p.paymentDate', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if (isset($criteria['start_date'])) {
            $qb->andWhere('p.paymentDate >= :start_date')
               ->setParameter('start_date', $criteria['start_date']);
        }

        if (isset($criteria['end_date'])) {
            $qb->andWhere('p.paymentDate <= :end_date')
               ->setParameter('end_date', $criteria['end_date']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
