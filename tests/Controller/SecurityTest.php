<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    use DatabaseTestTrait;

    private const ADMIN_EMAIL = 'admin@maisonsoussi.dz';
    private const ADMIN_PASSWORD = 'admin1234';

    public function testAdminAreaRedirectsAnonymousVisitorsToLogin(): void
    {
        $this->setUpDatabase();

        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/connexion');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="email"]');
    }

    /**
     * @dataProvider adminSections
     */
    public function testEveryAdminSectionIsProtected(string $path): void
    {
        $this->setUpDatabase();

        $this->client->request('GET', $path);

        $this->assertResponseRedirects('/connexion');
    }

    public static function adminSections(): iterable
    {
        yield ['/admin'];
        yield ['/admin/produits/'];
        yield ['/admin/produits/nouveau'];
        yield ['/admin/categories/'];
        yield ['/admin/marques/'];
        yield ['/admin/commandes/'];
        yield ['/admin/messages/'];
        yield ['/admin/newsletter/'];
        yield ['/admin/parametres/'];
    }

    public function testValidCredentialsGrantAccessToTheDashboard(): void
    {
        $this->setUpDatabase();

        $crawler = $this->client->request('GET', '/connexion');
        $this->client->submit($crawler->selectButton('Se connecter')->form([
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ]));

        $this->assertResponseRedirects('/admin');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#content h1, .page-title, h1', 'Bonjour');
    }

    public function testInvalidCredentialsAreRejected(): void
    {
        $this->setUpDatabase();

        $crawler = $this->client->request('GET', '/connexion');
        $this->client->submit($crawler->selectButton('Se connecter')->form([
            'email' => self::ADMIN_EMAIL,
            'password' => 'wrong-password',
        ]));

        // A failed attempt bounces back to the form with an error message.
        $this->assertResponseRedirects('/connexion');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert--error');

        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/connexion');
    }

    public function testLoginRequiresACsrfToken(): void
    {
        $this->setUpDatabase();

        $this->client->request('POST', '/connexion', [
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ]);

        // The firewall bounces an invalid-token attempt back to the form.
        $this->assertResponseRedirects('/connexion');

        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/connexion');
    }

    public function testLogoutEndsTheSession(): void
    {
        $this->setUpDatabase();
        $this->loginAsAdmin();

        $this->client->request('GET', '/deconnexion');

        $this->assertResponseRedirects('/');
        $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/connexion');
    }

    /**
     * @dataProvider adminPages
     */
    public function testAdminPagesRenderForAnAuthenticatedAdmin(string $path): void
    {
        $this->setUpDatabase();
        $this->loginAsAdmin();

        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    public static function adminPages(): iterable
    {
        yield ['/admin'];
        yield ['/admin/produits/'];
        yield ['/admin/categories/'];
        yield ['/admin/marques/'];
        yield ['/admin/commandes/'];
        yield ['/admin/messages/'];
        yield ['/admin/newsletter/'];
        yield ['/admin/parametres/'];
    }

    private function loginAsAdmin(): void
    {
        $crawler = $this->client->request('GET', '/connexion');

        $this->client->submit($crawler->selectButton('Se connecter')->form([
            'email' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ]));
    }
}