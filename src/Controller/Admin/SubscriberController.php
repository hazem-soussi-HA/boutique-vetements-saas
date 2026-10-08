<?php

namespace App\Controller\Admin;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/newsletter/')]
class SubscriberController extends AbstractController
{
    #[Route('', name: 'admin_subscriber_index', methods: ['GET'])]
    public function index(NewsletterSubscriberRepository $repo): Response
    {
        return $this->render('admin/subscriber/index.html.twig', [
            'subscribers' => $repo->findAllOrdered(),
            'activeCount' => $repo->countActive(),
        ]);
    }

    #[Route('{id}/basculer', name: 'admin_subscriber_toggle', methods: ['POST'])]
    public function toggle(Request $request, NewsletterSubscriber $subscriber, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$subscriber->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_subscriber_index');
        }

        $subscriber->setIsActive(!$subscriber->isActive());
        $em->flush();

        return $this->redirectToRoute('admin_subscriber_index');
    }

    #[Route('{id}/supprimer', name: 'admin_subscriber_delete', methods: ['POST'])]
    public function delete(Request $request, NewsletterSubscriber $subscriber, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$subscriber->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_subscriber_index');
        }

        $email = $subscriber->getEmail();
        $em->remove($subscriber);
        $em->flush();
        $this->addFlash('success', sprintf('Abonné %s supprimé.', $email));

        return $this->redirectToRoute('admin_subscriber_index');
    }
}