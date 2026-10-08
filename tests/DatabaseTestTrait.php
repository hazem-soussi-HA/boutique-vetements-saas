<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Rebuilds the test database from the entity mapping and loads the demo
 * fixtures before every test, so each test starts from a known state.
 *
 * WebTestCase forbids booting the kernel before createClient(), so the client
 * is created first and the schema is then reset behind it.
 */
trait DatabaseTestTrait
{
    protected EntityManagerInterface $em;

    protected KernelBrowser $client;

    protected function setUpDatabase(): KernelBrowser
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetSchema();

        static::getContainer()->get(\App\DataFixtures\AppFixtures::class)->load($this->em);

        return $this->client;
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->em) && $this->em->isOpen()) {
            $this->resetSchema();
        }

        parent::tearDown();
    }

    private function resetSchema(): void
    {
        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $this->em->clear();
    }
}