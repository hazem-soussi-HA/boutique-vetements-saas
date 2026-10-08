<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Guards against leaking demo credentials in a non-debug rendering.
 *
 * `createClient()` accepts server params, not kernel options, so the
 * non-debug kernel is booted directly through `createKernel()`.
 */
class LoginPageProductionTest extends WebTestCase
{
    public function testDemoCredentialsAreAbsentWithoutDebug(): void
    {
        $kernel = static::createKernel(['environment' => 'test', 'debug' => false]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        $em = $container->get(EntityManagerInterface::class);

        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $container->get(\App\DataFixtures\AppFixtures::class)->load($em);

        $client = $container->get('test.client');
        $client->request('GET', '/connexion');

        $this->assertSame(200, $client->getInternalResponse()->getStatusCode());

        $html = (string) $client->getInternalResponse()->getContent();

        $this->assertStringNotContainsString('admin1234', $html);
        $this->assertStringNotContainsString('Démo', $html);

        $tool->dropSchema($metadata);

        $kernel->shutdown();
    }
}