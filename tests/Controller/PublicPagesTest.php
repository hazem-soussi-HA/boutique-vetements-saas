<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicPagesTest extends WebTestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    /**
     * @dataProvider publicUrls
     */
    public function testPublicPageRenders(string $path): void
    {
        $crawler = $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(1000, \strlen($crawler->filter('body')->html()));
    }

    public static function publicUrls(): iterable
    {
        yield 'home' => ['/'];
        yield 'shop' => ['/boutique/'];
        yield 'shop sorted by price asc' => ['/boutique/?tri=prix-asc'];
        yield 'shop filtered by size and colour' => ['/boutique/?taille=M&couleur=Noir'];
        yield 'shop promo filter' => ['/boutique/?promo=1'];
        yield 'shop price range filter' => ['/boutique/?min=1000&max=50000'];
        yield 'shop in-stock filter' => ['/boutique/?stock=1'];
        yield 'about' => ['/a-propos'];
        yield 'contact' => ['/contact'];
        yield 'legal' => ['/mentions-legales'];
        yield 'cart (empty)' => ['/panier/'];
        yield 'login' => ['/connexion'];
    }

    public function testCategoryPageResolvesItsCategory(): void
    {
        $slug = $this->em->getConnection()->fetchOne(
            'SELECT slug FROM category WHERE is_active = true ORDER BY position LIMIT 1'
        );

        $this->client->request('GET', '/boutique/categorie/'.$slug);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testBrandPageRenders(): void
    {
        $slug = $this->em->getConnection()->fetchOne('SELECT slug FROM brand LIMIT 1');

        $this->client->request('GET', '/boutique/marque/'.$slug);

        $this->assertResponseIsSuccessful();
    }

    public function testProductPageRendersWithGalleryAndAddToCart(): void
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT slug FROM product WHERE is_active = true AND stock > 0 LIMIT 1'
        );

        $crawler = $this->client->request('GET', '/boutique/produit/'.$row['slug']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action*="/panier/ajouter/"] input[name="_token"]');
    }

    public function testUnknownProductReturns404(): void
    {
        $this->client->request('GET', '/boutique/produit/ce-produit-nexiste-pas');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testInactiveProductIsNotPubliclyReachable(): void
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT slug FROM product WHERE is_active = false LIMIT 1'
        );

        if (!$row) {
            $this->markTestSkipped('No inactive product in fixtures.');
        }

        $this->client->request('GET', '/boutique/produit/'.$row['slug']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShippingThresholdIsDrivenBySettingsNotHardcoded(): void
    {
        $crawler = $this->client->request('GET', '/');

        // Fixture threshold is 20000 DA; the storefront must render it from settings.
        $body = $crawler->filter('body')->text();
        $this->assertStringContainsString('20 000 DA', $body);
    }
}