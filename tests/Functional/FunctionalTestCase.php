<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User as EntityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = static::createClient();
        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->createQuery('DELETE FROM App\Entity\Media r 
                                 WHERE r.user IN (
                                     SELECT u FROM App\Entity\User u 
                                     WHERE u.email = :email
                                 )')
            ->setParameter('email', 'user+0@email.com')
            ->execute();

        $entityManager->createQuery('DELETE FROM App\Entity\User u 
                                 WHERE u.name = :name')
            ->setParameter('name', 'user 1')
            ->execute();

        $entityManager->clear();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    protected function service(string $id): object
    {
        /** @var null|T $service */
        $service = $this->user->getContainer()->get($id);
        self::assertNotNull($service);
        return $service;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function get(string $uri, array $parameters = []): Crawler
    {
        return $this->user->request('GET', $uri, $parameters);
    }

    protected function login(string $email = 'user+0@email.com'): void
    {
        $user = $this->getEntityManager()->getRepository(EntityUser::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        $this->user->loginUser($user);
    }

    /**
     * @param array<string, mixed> $formData
     */
    protected function submit(string $button, array $formData = [], string $method = 'POST'): Crawler
    {
        return $this->user->submitForm($button, $formData, $method);
    }
}
