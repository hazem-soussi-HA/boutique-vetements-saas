<?php

namespace App\Twig;

use App\Entity\ShopSettings;
use App\Repository\ContactMessageRepository;
use App\Service\CartService;
use App\Service\ShopService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig globals available in every template (site chrome + admin).
 */
class GlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ShopService $shop,
        private readonly CartService $cart,
        private readonly ContactMessageRepository $messages,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getGlobals(): array
    {
        $hasRequest = null !== $this->requestStack->getMainRequest();

        return [
            'settings' => $this->shop->settings(),
            'cart_count' => $hasRequest ? $this->cart->itemCount() : 0,
            'unread_count' => $this->isAdminRequest() ? $this->messages->countUnread() : 0,
        ];
    }

    /**
     * The unread counter is an admin-only concern: skip the COUNT query on every
     * public page render.
     */
    private function isAdminRequest(): bool
    {
        $request = $this->requestStack->getMainRequest();

        return null !== $request && str_starts_with($request->getPathInfo(), '/admin');
    }
}