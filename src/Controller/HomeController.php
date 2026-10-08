<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Form\ContactMessageType;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Service\CartService;
use App\Service\ShopService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
class HomeController extends AbstractController
{
    #[Route('', name: 'app_home', methods: ['GET'])]
    public function index(
        ShopService $shop,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        CartService $cart,
    ): Response {
        return $this->render('home/index.html.twig', [
            'settings' => $shop->settings(),
            'featured' => $shop->featuredProducts(8),
            'newArrivals' => $shop->newArrivals(8),
            'onSale' => $shop->onSale(4),
            'categories' => $categoryRepository->findActiveOrdered(),
            'brands' => $brandRepository->findFeatured(),
            'cartCount' => $cart->itemCount(),
        ]);
    }

    #[Route('a-propos', name: 'app_about', methods: ['GET'])]
    public function about(ShopService $shop, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/about.html.twig', [
            'settings' => $shop->settings(),
            'categories' => $categoryRepository->findActiveOrdered(),
        ]);
    }

    #[Route('contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        ShopService $shop,
        EntityManagerInterface $em,
        #[Autowire(service: 'limiter.contact_form')] RateLimiterFactory $limiter,
    ): Response {
        $message = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $limiter->create($request->getClientIp() ?? 'anon')->consume(1);
            } catch (RateLimitExceededException) {
                $this->addFlash('error', 'Trop de messages envoyés. Réessayez dans quelques instants.');

                return $this->redirectToRoute('app_contact');
            }

            $em->persist($message);
            $em->flush();

            $this->addFlash('success', 'Merci ! Votre message a bien été envoyé, nous vous répondons sous 24h.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('home/contact.html.twig', [
            'settings' => $shop->settings(),
            'form' => $form,
        ]);
    }

    #[Route('newsletter', name: 'app_newsletter', methods: ['POST'])]
    public function newsletter(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $repo,
        #[Autowire(service: 'limiter.newsletter')] RateLimiterFactory $limiter,
    ): Response {
        if (!$this->isCsrfTokenValid('newsletter', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');

            return $this->redirectToRoute('app_home');
        }

        $email = trim((string) $request->request->get('email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Une adresse e-mail valide est requise.');

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
        }

        try {
            $limiter->create($request->getClientIp() ?? 'anon')->consume(1);
        } catch (RateLimitExceededException) {
            $this->addFlash('error', 'Trop d’inscriptions depuis ce réseau. Réessayez plus tard.');

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
        }

        $existing = $repo->findOneByEmail($email);

        if (null !== $existing) {
            $existing->setIsActive(true);
            $em->flush();
            $this->addFlash('success', 'Vous êtes déjà inscrit à notre newsletter.');
        } else {
            $subscriber = new NewsletterSubscriber();
            $subscriber->setEmail($email);
            $em->persist($subscriber);
            $em->flush();
            $this->addFlash('success', 'Inscription confirmée ! -10% sur votre première commande.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function legal(ShopService $shop): Response
    {
        return $this->render('home/legal.html.twig', ['settings' => $shop->settings()]);
    }
}