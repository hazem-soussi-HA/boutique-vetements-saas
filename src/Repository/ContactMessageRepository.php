<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    public function save(ContactMessage $message): void
    {
        $em = $this->getEntityManager();
        $em->persist($message);
        $em->flush();
    }

    public function remove(ContactMessage $message): void
    {
        $em = $this->getEntityManager();
        $em->remove($message);
        $em->flush();
    }

    /**
     * @return array<int, ContactMessage>
     */
    public function findLatest(int $limit = 8): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.isRead = false')
            ->getQuery()->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()->getSingleScalarResult();
    }
}