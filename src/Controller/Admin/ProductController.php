<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/produits/')]
class ProductController extends AbstractController
{
    private const PER_PAGE = 15;

    #[Route('', name: 'admin_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products, CategoryRepository $categories, BrandRepository $brands): Response
    {
        $search = trim((string) $request->query->get('q'));
        $category = $request->query->get('categorie');
        $status = $request->query->get('statut');
        $page = max(1, (int) $request->query->get('page', 1));

        $qb = $products->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.brand', 'b')->addSelect('b')
            ->orderBy('p.createdAt', \SortDirection::Descending);

        if ('' !== $search) {
            $qb->andWhere('LOWER(p.name) LIKE :q OR LOWER(p.sku) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($search).'%');
        }
        if ($category) {
            $qb->andWhere('c.slug = :cat')->setParameter('cat', $category);
        }
        if ('actif' === $status) {
            $qb->andWhere('p.isActive = true');
        } elseif ('inactif' === $status) {
            $qb->andWhere('p.isActive = false');
        } elseif ('rupture' === $status) {
            $qb->andWhere('p.stock = 0');
        }

        $total = (int) (clone $qb)->select('COUNT(DISTINCT p.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $items = $qb->setMaxResults(self::PER_PAGE)
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->getQuery()->getResult();

        return $this->render('admin/product/index.html.twig', [
            'products' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'categories' => $categories->findAllOrdered(),
            'search' => $search,
            'category' => $category,
            'status' => $status,
        ]);
    }

    #[Route('nouveau', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $product->setSku('MS-'.strtoupper(bin2hex(random_bytes(3))));

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->touch();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', sprintf('Le produit « %s » a été créé et est visible sur le site.', $product->getName()));

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'title' => 'Nouveau produit',
        ]);
    }

    #[Route('{id}/modifier', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->touch();
            $em->flush();

            $this->addFlash('success', sprintf('Le produit « %s » a été mis à jour.', $product->getName()));

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'title' => 'Modifier le produit',
        ]);
    }

    #[Route('{id}/supprimer', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_product_index');
        }

        $name = $product->getName();
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', sprintf('Le produit « %s » a été supprimé.', $name));

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('{id}/basculer', name: 'admin_product_toggle', methods: ['POST'])]
    public function toggle(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$product->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_product_index');
        }

        $product->setIsActive(!$product->isActive());
        $product->touch();
        $em->flush();

        $this->addFlash('success', $product->isActive()
            ? sprintf('« %s » est désormais visible sur le site.', $product->getName())
            : sprintf('« %s » est masqué du site.', $product->getName()));

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin_product_index'));
    }
}