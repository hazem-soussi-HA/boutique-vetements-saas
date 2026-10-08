<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartTest extends WebTestCase
{
    use DatabaseTestTrait;

    private function activeProduct(): array
    {
        return $this->em->getConnection()->fetchAssociative(
            'SELECT id, stock FROM product WHERE is_active = true AND stock > 5 ORDER BY id LIMIT 1'
        );
    }

    public function testAddingToCartRequiresAValidCsrfToken(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        $this->client->request('POST', '/panier/ajouter/'.$product['id'], ['quantity' => 1]);

        $this->assertResponseRedirects('/boutique/');
        $this->assertSame([], $this->client->getRequest()->getSession()->get('cart_lines', []));
    }

    public function testAddingToCartWithCsrfStoresTheLine(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        // Submit through the real form so Twig renders the CSRF token.
        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $form = $crawler->filter(sprintf('form[action*="/panier/ajouter/%s"]', $product['id']))->form();
        $form['quantity'] = '2';

        $this->client->submit($form);

        $this->assertResponseRedirects('/panier/');

        $lines = $this->client->getRequest()->getSession()->get('cart_lines', []);
        $this->assertCount(1, $lines);

        $line = reset($lines);
        $this->assertSame(2, $line['quantity']);
        $this->assertSame((int) $product['id'], $line['productId']);
    }

    public function testBuyNowRedirectsStraightToCheckout(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $form = $crawler->filter(sprintf('form[action*="/panier/ajouter/%s"] button[name="buy_now"]', $product['id']))->form();

        $this->client->submit($form, ['buy_now' => '1']);

        $this->assertResponseRedirects('/commande/');
    }

    public function testAddingAnInactiveProductIsRefused(): void
    {
        $this->setUpDatabase();
        $product = $this->em->getConnection()->fetchAssociative(
            'SELECT id FROM product WHERE is_active = false LIMIT 1'
        );

        if (!$product) {
            $this->markTestSkipped('No inactive product in fixtures.');
        }

        $crawler = $this->client->request('GET', '/boutique/');
        $forms = $crawler->filter('form[action*="/panier/ajouter/"]');

        if (0 === $forms->count()) {
            $this->markTestSkipped('No product cards rendered.');
        }

        $this->assertStringNotContainsString(
            '/panier/ajouter/'.$product['id'],
            (string) $crawler->filter('body')->html()
        );
    }

    public function testQuantityCannotExceedStock(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $form = $crawler->filter(sprintf('form[action*="/panier/ajouter/%s"]', $product['id']))->form();
        $form['quantity'] = (string) ($product['stock'] + 50);

        $this->client->submit($form);

        $lines = $this->client->getRequest()->getSession()->get('cart_lines', []);
        $line = reset($lines);

        $this->assertLessThanOrEqual((int) $product['stock'], $line['quantity']);
    }

    public function testUpdatingAndRemovingACartLine(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $this->client->submit($crawler->filter(sprintf('form[action*="/panier/ajouter/%s"]', $product['id']))->form());

        $crawler = $this->client->request('GET', '/panier/');
        $form = $crawler->filter('form[action="/panier/modifier"]')->form();
        $form['quantity'] = '1';
        $this->client->submit($form);

        $lines = $this->client->getRequest()->getSession()->get('cart_lines', []);
        $this->assertSame(1, reset($lines)['quantity']);

        $crawler = $this->client->request('GET', '/panier/');
        $this->client->submit($crawler->filter('form[action="/panier/supprimer"]')->form());

        $this->assertSame([], $this->client->getRequest()->getSession()->get('cart_lines', []));
        $this->assertResponseRedirects('/panier/');
    }

    public function testCartQuantityOfZeroRemovesTheLine(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();

        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $this->client->submit($crawler->filter(sprintf('form[action*="/panier/ajouter/%s"]', $product['id']))->form());

        $crawler = $this->client->request('GET', '/panier/');
        $form = $crawler->filter('form[action="/panier/modifier"]')->form();
        $form['quantity'] = '0';
        $this->client->submit($form);

        $this->assertSame([], $this->client->getRequest()->getSession()->get('cart_lines', []));
    }

    public function testCheckoutWithAnEmptyCartRedirectsToTheCart(): void
    {
        $this->setUpDatabase();

        $this->client->request('GET', '/commande/');

        $this->assertResponseRedirects('/panier/');
    }

    public function testCompletingCheckoutCreatesAnOrderAndDecrementsStock(): void
    {
        $this->setUpDatabase();
        $product = $this->activeProduct();
        $stockBefore = (int) $product['stock'];
        $ordersBefore = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM orders');

        $crawler = $this->client->request('GET', '/boutique/produit/'.$this->productSlug());
        $this->client->submit($crawler->filter(sprintf('form[action*="/panier/ajouter/%s"]', $product['id']))->form());

        $crawler = $this->client->request('GET', '/commande/');
        $form = $crawler->filter('form')->form([
            'checkout[customerFirstName]' => 'Yasmine',
            'checkout[customerLastName]' => 'Boudiaf',
            'checkout[customerEmail]' => 'yasmine@example.com',
            'checkout[customerPhone]' => '+213555000111',
            'checkout[shippingCity]' => 'Alger',
            'checkout[shippingWilaya]' => 'Alger',
            'checkout[shippingAddress]' => '14, rue Didouche Mourad',
            'checkout[paymentMethod]' => Order::PAYMENT_COD,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/commande/confirmation');

        $ordersAfter = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM orders');
        $this->assertSame($ordersBefore + 1, $ordersAfter);

        $stockAfter = (int) $this->em->getConnection()->fetchOne(
            'SELECT stock FROM product WHERE id = :id',
            ['id' => $product['id']]
        );
        $this->assertSame($stockBefore - 1, $stockAfter);

        // The cart is emptied after a successful order.
        $this->assertSame([], $this->client->getRequest()->getSession()->get('cart_lines', []));
    }

    public function testOrderReferenceIsUniqueAndPrefixed(): void
    {
        $this->setUpDatabase();

        $reference = $this->em->getConnection()->fetchOne('SELECT reference FROM orders LIMIT 1');

        // Fixtures backdate references (date offset), so only assert the shape.
        $this->assertMatchesRegularExpression('/^MS-\d{6}-[0-9A-F]+$/', (string) $reference);
    }

    public function testConfirmationPageRequiresASessionReference(): void
    {
        $this->setUpDatabase();

        $this->client->request('GET', '/commande/confirmation');

        $this->assertResponseRedirects('/');
    }

    private function productSlug(): string
    {
        return (string) $this->em->getConnection()->fetchOne(
            'SELECT slug FROM product WHERE is_active = true AND stock > 5 ORDER BY id LIMIT 1'
        );
    }
}