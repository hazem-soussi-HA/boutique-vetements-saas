<?php

namespace App\Controller;

use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('boutique/')]
class ShopController extends AbstractController
{
    private const PER_PAGE = 12;

    #[Route('', name: 'app_shop', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products, ShopService $shop, CategoryRepository $categories, BrandRepository $brands): Response
    {
        return $this->catalogue($request, $products, $shop, $categories, $brands);
    }

    #[Route('categorie/{slug}', name: 'app_shop_category', methods: ['GET'])]
    public function category(string $slug, Request $request, ProductRepository $products, ShopService $shop, CategoryRepository $categories, BrandRepository $brands): Response
    {
        return $this->catalogue($request, $products, $shop, $categories, $brands, ['category' => $slug]);
    }

    private function catalogue(
        Request $request,
        ProductRepository $products,
        ShopService $shop,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        array $overrides = [],
    ): Response {
        $criteria = array_merge([
            'search' => $request->query->get('q'),
            'gender' => $request->query->get('genre'),
            'size' => $request->query->get('taille'),
            'color' => $request->query->get('couleur'),
            'minPrice' => $request->query->get('min') !== null && $request->query->get('min') !== '' ? (int) round((float) $request->query->get('min') * 100) : null,
            'maxPrice' => $request->query->get('max') !== null && $request->query->get('max') !== '' ? (int) round((float) $request->query->get('max') * 100) : null,
            'onSale' => $request->query->getBoolean('promo'),
            'inStock' => $request->query->getBoolean('stock'),
            'brand' => $request->query->get('marque'),
        ], $overrides);

        $page = max(1, (int) $request->query->get('page', 1));
        $sort = $request->query->get('tri', 'recent');

        $qb = $products->searchPublic($criteria);
        match ($sort) {
            'prix-asc' => $qb->orderBy('COALESCE(p.salePrice, p.price)', \SortDirection::Ascending),
            'prix-desc' => $qb->orderBy('COALESCE(p.salePrice, p.price)', \SortDirection::Descending),
            'nom' => $qb->orderBy('p.name', \SortDirection::Ascending),
            default => $qb->orderBy('p.createdAt', \SortDirection::Descending),
        };

        $total = $products->countPublic($criteria);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $items = $qb
            ->setMaxResults(self::PER_PAGE)
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->getQuery()->getResult();

        $currentCategory = $categoryRepository->findOneBySlugOrdered($criteria['category'] ?? null);

        return $this->render('shop/index.html.twig', [
            'settings' => $shop->settings(),
            'products' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'criteria' => $criteria,
            'sort' => $sort,
            'categories' => $categoryRepository->findActiveOrdered(),
            'brands' => $brandRepository->findAllOrdered(),
            'sizes' => $products->distinctSizes(),
            'colors' => $products->distinctColors(),
            'priceRange' => $products->priceRange(),
            'currentCategory' => $currentCategory,
        ]);
    }

    #[Route('produit/{slug}', name: 'app_product', methods: ['GET'])]
    public function product(string $slug, ProductRepository $products, ShopService $shop): Response
    {
        $product = $products->findActiveBySlug($slug);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        // Fetch one extra so excluding the current product still yields a full set.
        $candidates = $products->findPublic([
            'category' => $product->getCategory()?->getSlug(),
            'inStock' => true,
        ], self::PER_PAGE + 1);

        $related = array_slice(
            array_values(array_filter($candidates, static fn ($p) => $p->getId() !== $product->getId())),
            0,
            4
        );

        return $this->render('shop/product.html.twig', [
            'settings' => $shop->settings(),
            'product' => $product,
            'gallery' => $product->getGallery(),
            'related' => $related,
        ]);
    }

    #[Route('marque/{slug}', name: 'app_brand', methods: ['GET'])]
    public function brand(string $slug, Request $request, ProductRepository $products, ShopService $shop, CategoryRepository $categories, BrandRepository $brands): Response
    {
        return $this->catalogue($request, $products, $shop, $categories, $brands, ['brand' => $slug]);
    }
}