<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ShopSettings;
use App\Repository\ProductRepository;
use App\Repository\ShopSettingsRepository;

class ShopService
{
    private ?ShopSettings $settings = null;

    public function __construct(
        private readonly ShopSettingsRepository $settingsRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function settings(): ShopSettings
    {
        if (null === $this->settings) {
            $this->settings = $this->settingsRepository->getSingleton() ?? new ShopSettings();
        }

        return $this->settings;
    }

    public function saveSettings(ShopSettings $settings): void
    {
        $em = $this->settingsRepository->getEntityManager();
        $em->persist($settings);
        $em->flush();
        $this->settings = $settings;
    }

    /**
     * @return array<int, Product>
     */
    public function featuredProducts(int $limit = 8): array
    {
        return $this->productRepository->findPublic(['featured' => true], $limit);
    }

    /**
     * @return array<int, Product>
     */
    public function newArrivals(int $limit = 8): array
    {
        return $this->productRepository->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.isNew = true')
            ->orderBy('p.createdAt', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Product>
     */
    public function onSale(int $limit = 4): array
    {
        return $this->productRepository->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.salePrice IS NOT NULL')
            ->andWhere('p.salePrice < p.price')
            ->orderBy('p.createdAt', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function shippingFor(int $subtotal): int
    {
        $settings = $this->settings();
        if ($subtotal <= 0) {
            return 0;
        }
        if ($settings->getFreeShippingThreshold() > 0 && $subtotal >= $settings->getFreeShippingThreshold()) {
            return 0;
        }

        return $settings->getShippingFee();
    }
}