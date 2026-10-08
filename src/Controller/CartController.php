<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('panier/')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function view(ShopService $shop, CartService $cart): Response
    {
        return $this->render('cart/index.html.twig', [
            'settings' => $shop->settings(),
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'shipping' => $cart->shipping(),
            'total' => $cart->total(),
        ]);
    }

    #[Route('ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProductRepository $products,
        CartService $cart,
        #[Autowire(service: 'limiter.cart')] RateLimiterFactory $limiter,
    ): Response {
        $product = $products->find($id);

        if (!$this->isCsrfTokenValid('cart_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');

            return $this->redirectToRoute('app_shop');
        }

        $product = $products->find($id);

        if (!$product || !$product->isActive()) {
            $this->addFlash('error', 'Ce produit n’est plus disponible.');

            return $this->redirectToRoute('app_shop');
        }

        try {
            $limiter->create($request->getClientIp() ?? 'anon')->consume(1);
        } catch (RateLimitExceededException) {
            $this->addFlash('error', 'Trop d’ajouts rapides. Patientez quelques secondes.');

            return $this->redirectToRoute('app_shop');
        }

        $size = $request->request->get('size') ?: null;
        $color = $request->request->get('color') ?: null;
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $cart->add($product, $quantity, $size, $color);
        $this->addFlash('success', sprintf('%s a été ajouté à votre panier.', $product->getName()));

        if ($request->request->getBoolean('buy_now')) {
            return $this->redirectToRoute('app_checkout');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('modifier', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');

            return $this->redirectToRoute('app_cart');
        }

        $key = (string) $request->request->get('line');
        $quantity = (int) $request->request->get('quantity', 1);
        $cart->update($key, $quantity);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('supprimer', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Request $request, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');

            return $this->redirectToRoute('app_cart');
        }

        $cart->remove((string) $request->request->get('line'));
        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart');
    }
}