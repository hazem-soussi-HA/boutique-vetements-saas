<?php

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    public function save(Brand $brand): void
    {
        $em = $this->getEntityManager();
        $em->persist($brand);
        $em->flush();
    }

    public function remove(Brand $brand): void
    {
        $em = $this->getEntityManager();
        $em->remove($brand);
        $em->flush();
    }

    /**
     * @return array<int, Brand>
     */
    public function findFeatured(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isFeatured = true')
            ->orderBy('b.name', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Brand>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()->getSingleScalarResult();
    }
}