<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->persist($order);
        $em->flush();
    }

    /**
     * @return array<int, Order>
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status', 'COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_keys(Order::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function revenueTotal(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Revenue total grouped by day for the last $days days.
     *
     * @return array<string, int>
     */
    public function revenueByDay(int $days = 14): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        $rows = $this->createQueryBuilder('o')
            ->select("TO_CHAR(o.createdAt, 'YYYY-MM-DD') AS day", 'COALESCE(SUM(o.total), 0) AS total')
            ->andWhere('o.createdAt >= :since')
            ->andWhere('o.status != :cancelled')
            ->setParameter('since', $since)
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->groupBy('day')
            ->getQuery()
            ->getArrayResult();

        $series = [];
        for ($i = $days - 1; $i >= 0; --$i) {
            $series[(new \DateTimeImmutable("-{$i} days"))->format('Y-m-d')] = 0;
        }
        foreach ($rows as $row) {
            $series[$row['day']] = (int) $row['total'];
        }

        return $series;
    }

    /**
     * Top products by quantity sold.
     *
     * @return array<int, array{name: string, quantity: int, total: int}>
     */
    public function topProducts(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('i.productName AS name', 'SUM(i.quantity) AS quantity', 'SUM(i.quantity * i.unitPrice) AS total')
            ->join('o.items', 'i')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->groupBy('i.productName')
            ->orderBy('SUM(i.quantity)', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'name' => $row['name'],
            'quantity' => (int) $row['quantity'],
            'total' => (int) $row['total'],
        ], $rows);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()->getSingleScalarResult();
    }

    public function countNewSince(\DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult();
    }
}