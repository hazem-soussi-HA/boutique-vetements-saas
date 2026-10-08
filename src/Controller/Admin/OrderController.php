<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commandes/')]
class OrderController extends AbstractController
{
    private const PER_PAGE = 15;

    #[Route('', name: 'admin_order_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $status = $request->query->get('statut');
        $search = trim((string) $request->query->get('q'));
        $page = max(1, (int) $request->query->get('page', 1));

        $qb = $em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->orderBy('o.createdAt', \SortDirection::Descending);

        if ($status && isset(Order::STATUSES[$status])) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }
        if ('' !== $search) {
            $qb->andWhere('LOWER(o.reference) LIKE :q OR LOWER(o.customerEmail) LIKE :q OR LOWER(o.customerLastName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($search).'%');
        }

        $total = (int) (clone $qb)->select('COUNT(o.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $orders = $qb->setMaxResults(self::PER_PAGE)
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->getQuery()->getResult();

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'status' => $status,
            'search' => $search,
        ]);
    }

    #[Route('{id}', name: 'admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
            'statuses' => Order::STATUSES,
        ]);
    }

    #[Route('{id}/statut', name: 'admin_order_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('status'.$order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
        }

        $status = (string) $request->request->get('status');
        $order->setStatus($status);
        $em->flush();

        $this->addFlash('success', sprintf('Commande %s : statut mis à jour (%s).', $order->getReference(), $order->getStatusLabel()));

        return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
    }

    #[Route('{id}/supprimer', name: 'admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_order_index');
        }

        $reference = $order->getReference();
        $em->remove($order);
        $em->flush();
        $this->addFlash('success', sprintf('Commande %s supprimée.', $reference));

        return $this->redirectToRoute('admin_order_index');
    }
}