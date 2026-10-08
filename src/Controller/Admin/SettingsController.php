<?php

namespace App\Controller\Admin;

use App\Form\ShopSettingsType;
use App\Repository\ShopSettingsRepository;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/parametres/')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, ShopService $shop, ShopSettingsRepository $repo): Response
    {
        $settings = $repo->getSingleton() ?? $shop->settings();

        $form = $this->createForm(ShopSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shop->saveSettings($settings);
            $this->addFlash('success', 'Les paramètres de la boutique ont été enregistrés.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings/index.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }
}