<?php

namespace App\Twig;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private ?array $navCategories = null;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly BrandRepository $brandRepository,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->formatMoney(...)),
            new TwigFilter('price_range', $this->formatPriceRange(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nav_categories', $this->navCategories(...)),
            new TwigFunction('all_brands', $this->allBrands(...)),
            new TwigFunction('size_labels', $this->sizeLabels(...)),
        ];
    }

    /**
     * Cents -> "12 900,00 DA".
     */
    public function formatMoney(int $cents, bool $withDecimals = false): string
    {
        $amount = $cents / 100;

        return number_format($amount, $withDecimals ? 2 : 0, ',', ' ').' DA';
    }

    public function formatPriceRange(int $min, int $max): string
    {
        if ($min === $max) {
            return $this->formatMoney($min);
        }

        return $this->formatMoney($min).' – '.$this->formatMoney($max);
    }

    /**
     * @return array<int, Category>
     */
    public function navCategories(): array
    {
        if (null === $this->navCategories) {
            $this->navCategories = $this->categoryRepository->findActiveOrdered();
        }

        return $this->navCategories;
    }

    /**
     * @return array<int, \App\Entity\Brand>
     */
    public function allBrands(): array
    {
        return $this->brandRepository->findFeatured();
    }

    /**
     * @return array<string, string>
     */
    public function sizeLabels(): array
    {
        return [
            'XS' => 'XS',
            'S' => 'S',
            'M' => 'M',
            'L' => 'L',
            'XL' => 'XL',
            'XXL' => '2XL',
            '3XL' => '3XL',
        ];
    }

    public function stockBadge(Product $product): string
    {
        if (!$product->isInStock()) {
            return 'Rupture';
        }
        if ($product->isLowStock()) {
            return 'Plus que '.$product->getStock();
        }

        return 'En stock';
    }
}