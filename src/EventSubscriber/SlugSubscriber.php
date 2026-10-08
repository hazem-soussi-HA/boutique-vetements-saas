<?php

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class SlugSubscriber
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->handle($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->handle($args->getObject());
    }

    private function handle(object $entity): void
    {
        if (!method_exists($entity, 'getSlug') || !method_exists($entity, 'setSlug')) {
            return;
        }

        $source = '';
        if (method_exists($entity, 'getName') && '' !== trim((string) $entity->getName())) {
            $source = (string) $entity->getName();
        } elseif (method_exists($entity, 'getFullName') && '' !== trim((string) $entity->getFullName())) {
            $source = (string) $entity->getFullName();
        } elseif (method_exists($entity, 'getEmail') && '' !== trim((string) $entity->getEmail())) {
            $source = (string) $entity->getEmail();
        }

        $slug = trim((string) $entity->getSlug());

        if ('' !== $slug) {
            $slug = $this->slugger->slug($slug)->lower()->toString();
        } elseif ('' !== $source) {
            $slug = $this->slugger->slug($source)->lower()->toString();
        }

        if ('' === $slug) {
            $slug = 'element-'.bin2hex(random_bytes(3));
        }

        $entity->setSlug($this->deduplicate($slug, $entity));
    }

    private function deduplicate(string $slug, object $entity): string
    {
        $repository = $this->entityManager->getRepository($entity::class);

        $candidate = $slug;
        $counter = 2;

        while (true) {
            $found = $repository->findOneBy(['slug' => $candidate]);
            if (null === $found || $found === $entity || $found->getId() === $entity->getId()) {
                return $candidate;
            }

            $candidate = $slug.'-'.$counter;
            ++$counter;

            if ($counter > 200) {
                return $slug.'-'.bin2hex(random_bytes(2));
            }
        }
    }
}