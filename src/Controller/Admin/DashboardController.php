<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\ShopService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        ProductRepository $products,
        OrderRepository $orders,
        CategoryRepository $categories,
        ContactMessageRepository $messages,
        NewsletterSubscriberRepository $subscribers,
        EntityManagerInterface $em,
    ): Response {
        $productCount = (int) $em->createQuery('SELECT COUNT(p.id) FROM App\Entity\Product p')->getSingleScalarResult();
        $categoryCount = $categories->countAll();
        $orderCount = $orders->countAll();
        $revenue = $orders->revenueTotal();
        $stock = $products->stockSummary();

        $recentOrders = $orders->findLatest(6);
        $recentMessages = $messages->findLatest(5);
        $lowStock = $em->createQueryBuilder()
            ->select('p')
            ->from(\App\Entity\Product::class, 'p')
            ->andWhere('p.stock <= 5')
            ->orderBy('p.stock', \SortDirection::Ascending)
            ->setMaxResults(6)
            ->getQuery()->getResult();

        $salesSeries = $orders->revenueByDay(14);

        $monthStart = new \DateTimeImmutable('first day of this month');
        $monthRevenue = (int) $em->createQuery('SELECT COALESCE(SUM(o.total), 0) FROM App\Entity\Order o WHERE o.createdAt >= :start AND o.status != :cancelled')
            ->setParameter('start', $monthStart)
            ->setParameter('cancelled', \App\Entity\Order::STATUS_CANCELLED)
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'revenue' => $revenue,
                'monthRevenue' => $monthRevenue,
                'orders' => $orderCount,
                'newOrders' => $orders->countNewSince(new \DateTimeImmutable('-30 days')),
                'products' => $productCount,
                'categories' => $categoryCount,
                'subscribers' => $subscribers->countActive(),
                'unreadMessages' => $messages->countUnread(),
            ],
            'statusCounts' => $orders->statusCounts(),
            'recentOrders' => $recentOrders,
            'recentMessages' => $recentMessages,
            'lowStock' => $lowStock,
            'stock' => $stock,
            'salesSeries' => $salesSeries,
            'topProducts' => $orders->topProducts(5),
        ]);
    }
}