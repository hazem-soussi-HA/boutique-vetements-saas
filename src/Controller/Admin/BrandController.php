<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/marques/')]
class BrandController extends AbstractController
{
    #[Route('', name: 'admin_brand_index', methods: ['GET'])]
    public function index(BrandRepository $brands): Response
    {
        return $this->render('admin/brand/index.html.twig', [
            'brands' => $brands->findAllOrdered(),
        ]);
    }

    #[Route('nouvelle', name: 'admin_brand_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $brand = new Brand();
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($brand);
            $em->flush();
            $this->addFlash('success', sprintf('La marque « %s » a été créée.', $brand->getName()));

            return $this->redirectToRoute('admin_brand_index');
        }

        return $this->render('admin/brand/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle marque',
            'brand' => $brand,
        ]);
    }

    #[Route('{id}/modifier', name: 'admin_brand_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Brand $brand, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Marque mise à jour.');

            return $this->redirectToRoute('admin_brand_index');
        }

        return $this->render('admin/brand/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier la marque',
            'brand' => $brand,
        ]);
    }

    #[Route('{id}/supprimer', name: 'admin_brand_delete', methods: ['POST'])]
    public function delete(Request $request, Brand $brand, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$brand->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_brand_index');
        }

        $name = $brand->getName();
        $em->remove($brand);
        $em->flush();
        $this->addFlash('success', sprintf('Marque « %s » supprimée.', $name));

        return $this->redirectToRoute('admin_brand_index');
    }
}