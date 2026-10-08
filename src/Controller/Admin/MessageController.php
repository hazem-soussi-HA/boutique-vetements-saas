<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages/')]
class MessageController extends AbstractController
{
    #[Route('', name: 'admin_message_index', methods: ['GET'])]
    public function index(Request $request, ContactMessageRepository $messages): Response
    {
        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages->findBy([], ['createdAt' => \SortDirection::Descending]),
        ]);
    }

    #[Route('{id}/lire', name: 'admin_message_toggle', methods: ['POST'])]
    public function toggle(Request $request, ContactMessage $message, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$message->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_message_index');
        }

        $message->setIsRead(!$message->isRead());
        $em->flush();

        return $this->redirectToRoute('admin_message_index');
    }

    #[Route('{id}/supprimer', name: 'admin_message_delete', methods: ['POST'])]
    public function delete(Request $request, ContactMessage $message, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$message->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_message_index');
        }

        $em->remove($message);
        $em->flush();
        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('admin_message_index');
    }
}