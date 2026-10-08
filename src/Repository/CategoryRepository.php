<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $category): void
    {
        $em = $this->getEntityManager();
        $em->persist($category);
        $em->flush();
    }

    public function remove(Category $category): void
    {
        $em = $this->getEntityManager();
        $em->remove($category);
        $em->flush();
    }

    /**
     * @return array<int, Category>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = true')
            ->orderBy('c.position', \SortDirection::Ascending)
            ->addOrderBy('c.name', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Category>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', \SortDirection::Ascending)
            ->addOrderBy('c.name', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugOrdered(?string $slug): ?Category
    {
        if (null === $slug || '' === $slug) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()->getSingleScalarResult();
    }
}