<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\ShopService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('commande/')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cart,
        ShopService $shop,
        EntityManagerInterface $em,
        SessionInterface $session,
    ): Response {
        if ($cart->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $form = $this->createForm(CheckoutType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reference = 'MS-'.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

            $finalOrder = $cart->toOrder($reference);
            $finalOrder->setCustomerFirstName($order->getCustomerFirstName());
            $finalOrder->setCustomerLastName($order->getCustomerLastName());
            $finalOrder->setCustomerEmail($order->getCustomerEmail());
            $finalOrder->setCustomerPhone($order->getCustomerPhone());
            $finalOrder->setShippingCity($order->getShippingCity());
            $finalOrder->setShippingWilaya($order->getShippingWilaya());
            $finalOrder->setShippingAddress($order->getShippingAddress());
            $finalOrder->setPaymentMethod($order->getPaymentMethod());
            $finalOrder->setNotes($order->getNotes());

            $em->persist($finalOrder);
            $em->flush();

            $cart->clear();
            $session->set('last_order_reference', $finalOrder->getReference());

            $this->addFlash('success', 'Commande confirmée ! Merci pour votre confiance.');

            return $this->redirectToRoute('app_checkout_success');
        }

        return $this->render('checkout/index.html.twig', [
            'settings' => $shop->settings(),
            'form' => $form,
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'shipping' => $cart->shipping(),
            'total' => $cart->total(),
        ]);
    }

    #[Route('confirmation', name: 'app_checkout_success', methods: ['GET'])]
    public function success(ShopService $shop, SessionInterface $session): Response
    {
        $reference = $session->get('last_order_reference');

        if (!$reference) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('checkout/success.html.twig', [
            'settings' => $shop->settings(),
            'reference' => $reference,
        ]);
    }
}