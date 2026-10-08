<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const SESSION_KEY = 'cart_lines';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $productRepository,
        private readonly ShopService $shopService,
    ) {
    }

    private function session(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * Cart lines keyed by "productId|size|color".
     *
     * @return array<string, array{productId: int, size: string|null, color: string|null, quantity: int}>
     */
    private function rawLines(): array
    {
        return $this->session()->get(self::SESSION_KEY, []);
    }

    /**
     * @param array<string, array{productId: int, size: string|null, color: string|null, quantity: int}> $lines
     */
    private function write(array $lines): void
    {
        $this->session()->set(self::SESSION_KEY, $lines);
    }

    private function key(int $productId, ?string $size, ?string $color): string
    {
        return $productId.'|'.($size ?? '').'|'.($color ?? '');
    }

    public function add(Product $product, int $quantity = 1, ?string $size = null, ?string $color = null): void
    {
        $lines = $this->rawLines();
        $key = $this->key((int) $product->getId(), $size, $color);
        $current = $lines[$key]['quantity'] ?? 0;
        $max = max(0, $product->getStock());

        $lines[$key] = [
            'productId' => (int) $product->getId(),
            'size' => $size,
            'color' => $color,
            'quantity' => max(1, min($current + $quantity, max(1, $max))),
        ];

        $this->write($lines);
    }

    public function update(string $lineKey, int $quantity): void
    {
        $lines = $this->rawLines();
        if (!isset($lines[$lineKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($lines[$lineKey]);
            $this->write($lines);

            return;
        }

        $product = $this->productRepository->find($lines[$lineKey]['productId']);
        $max = $product ? max(1, $product->getStock()) : 1;
        $lines[$lineKey]['quantity'] = min($quantity, $max);
        $this->write($lines);
    }

    public function remove(string $lineKey): void
    {
        $lines = $this->rawLines();
        unset($lines[$lineKey]);
        $this->write($lines);
    }

    public function clear(): void
    {
        $this->session()->remove(self::SESSION_KEY);
    }

    /**
     * Hydrated lines: drops products that no longer exist or became inactive.
     *
     * @return array<int, array{key: string, product: Product, size: string|null, color: string|null, quantity: int, unitPrice: int, total: int}>
     */
    public function lines(): array
    {
        $result = [];
        foreach ($this->rawLines() as $key => $line) {
            $product = $this->productRepository->find($line['productId']);
            if (!$product instanceof Product || !$product->isActive()) {
                continue;
            }

            $quantity = max(1, min((int) $line['quantity'], max(1, $product->getStock())));
            $unitPrice = $product->getEffectivePrice();

            $result[] = [
                'key' => $key,
                'product' => $product,
                'size' => $line['size'],
                'color' => $line['color'],
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'total' => $unitPrice * $quantity,
            ];
        }

        return $result;
    }

    public function itemCount(): int
    {
        $count = 0;
        foreach ($this->lines() as $line) {
            $count += $line['quantity'];
        }

        return $count;
    }

    public function subtotal(): int
    {
        $total = 0;
        foreach ($this->lines() as $line) {
            $total += $line['total'];
        }

        return $total;
    }

    public function shipping(): int
    {
        return $this->shopService->shippingFor($this->subtotal());
    }

    public function total(): int
    {
        return $this->subtotal() + $this->shipping();
    }

    public function isEmpty(): bool
    {
        return [] === $this->lines();
    }

    /**
     * Convert the cart into a persisted order. Stock is decremented here.
     */
    public function toOrder(string $reference): Order
    {
        $order = new Order();
        $order->setReference($reference);

        foreach ($this->lines() as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $item = new OrderItem();
            $item->setProduct($product);
            $item->setProductName($product->getName());
            $item->setProductImage($product->getMainImage());
            $item->setSize($line['size']);
            $item->setColor($line['color']);
            $item->setQuantity($line['quantity']);
            $item->setUnitPrice($line['unitPrice']);
            $order->addItem($item);

            $product->setStock(max(0, $product->getStock() - $line['quantity']));
        }

        $order->setSubtotal($this->subtotal());
        $order->setShipping($this->shipping());
        $order->setTotal($this->total());

        return $order;
    }
}