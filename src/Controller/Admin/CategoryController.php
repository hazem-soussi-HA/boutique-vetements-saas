<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories/')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categories): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories->findAllOrdered(),
        ]);
    }

    #[Route('nouvelle', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', sprintf('La catégorie « %s » a été créée.', $category->getName()));

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle catégorie',
            'category' => $category,
        ]);
    }

    #[Route('{id}/modifier', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie mise à jour.');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier la catégorie',
            'category' => $category,
        ]);
    }

    #[Route('{id}/supprimer', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$category->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_category_index');
        }

        if (count($category->getProducts()) > 0) {
            $this->addFlash('error', 'Impossible : cette catégorie contient encore des produits.');

            return $this->redirectToRoute('admin_category_index');
        }

        $name = $category->getName();
        $em->remove($category);
        $em->flush();
        $this->addFlash('success', sprintf('Catégorie « %s » supprimée.', $name));

        return $this->redirectToRoute('admin_category_index');
    }
}