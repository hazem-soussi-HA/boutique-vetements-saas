<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $product): void
    {
        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();
    }

    public function remove(Product $product): void
    {
        $em = $this->getEntityManager();
        $em->remove($product);
        $em->flush();
    }

    /**
     * Public catalogue query builder: only active products.
     */
    public function publicQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.brand', 'b')->addSelect('b')
            ->andWhere('p.isActive = true')
            ->orderBy('p.position', \SortDirection::Ascending)
            ->addOrderBy('p.createdAt', \SortDirection::Descending);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function searchPublic(array $criteria): QueryBuilder
    {
        $qb = $this->publicQuery();

        if (!empty($criteria['category'])) {
            $qb->andWhere('c.slug = :category')->setParameter('category', $criteria['category']);
        }
        if (!empty($criteria['brand'])) {
            $qb->andWhere('b.slug = :brand')->setParameter('brand', $criteria['brand']);
        }
        if (!empty($criteria['gender'])) {
            $qb->andWhere('p.gender = :gender')->setParameter('gender', $criteria['gender']);
        }
        if (!empty($criteria['size'])) {
            $qb->andWhere("JSON_TEXT(p.sizes) LIKE :size")->setParameter('size', '%"'.$criteria['size'].'"%');
        }
        if (!empty($criteria['color'])) {
            $qb->andWhere("JSON_TEXT(p.colors) LIKE :color")->setParameter('color', '%"'.$criteria['color'].'"%');
        }
        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('COALESCE(p.salePrice, p.price) >= :minPrice')->setParameter('minPrice', $criteria['minPrice']);
        }
        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('COALESCE(p.salePrice, p.price) <= :maxPrice')->setParameter('maxPrice', $criteria['maxPrice']);
        }
        if (!empty($criteria['onSale'])) {
            $qb->andWhere('p.salePrice IS NOT NULL')->andWhere('p.salePrice < p.price');
        }
        if (!empty($criteria['inStock'])) {
            $qb->andWhere('p.stock > 0');
        }
        if (!empty($criteria['featured'])) {
            $qb->andWhere('p.isFeatured = true');
        }
        if (!empty($criteria['search'])) {
            $qb->andWhere('LOWER(p.name) LIKE :search OR LOWER(COALESCE(p.shortDescription, \'\')) LIKE :search OR LOWER(p.sku) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower((string) $criteria['search']).'%');
        }

        return $qb;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findPublic(array $criteria, int $limit, int $offset = 0): array
    {
        return $this->searchPublic($criteria)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function countPublic(array $criteria): int
    {
        $qb = $this->searchPublic($criteria)
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findActiveBySlug(string $slug): ?Product
    {
        return $this->findOneBy(['slug' => $slug, 'isActive' => true]);
    }

    /**
     * @return array<int, string>
     */
    public function distinctSizes(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.sizes')
            ->andWhere('p.isActive = true')
            ->getQuery()
            ->getScalarResult();

        $sizes = [];
        foreach ($rows as $row) {
            foreach ((array) ($row['sizes'] ?? []) as $size) {
                $sizes[$size] = true;
            }
        }

        $result = array_keys($sizes);
        usort($result, static fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function distinctColors(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.colors')
            ->andWhere('p.isActive = true')
            ->getQuery()
            ->getScalarResult();

        $colors = [];
        foreach ($rows as $row) {
            foreach ((array) ($row['colors'] ?? []) as $color) {
                $colors[$color] = true;
            }
        }

        return array_keys($colors);
    }

    /**
     * @return array<int, int>
     */
    public function priceRange(): array
    {
        $row = $this->createQueryBuilder('p')
            ->select('MIN(COALESCE(p.salePrice, p.price)) AS minPrice', 'MAX(COALESCE(p.salePrice, p.price)) AS maxPrice')
            ->andWhere('p.isActive = true')
            ->getQuery()
            ->getSingleResult();

        return [
            (int) ($row['minPrice'] ?? 0),
            (int) ($row['maxPrice'] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function stockSummary(): array
    {
        $total = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isActive = true')
            ->getQuery()->getSingleScalarResult();

        $low = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isActive = true')
            ->andWhere('p.stock > 0')
            ->andWhere('p.stock <= 5')
            ->getQuery()->getSingleScalarResult();

        $out = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock = 0')
            ->getQuery()->getSingleScalarResult();

        return ['total' => $total, 'low' => $low, 'out' => $out];
    }
}