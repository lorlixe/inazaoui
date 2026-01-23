<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $user;
    private static bool $fixturesLoaded = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = static::createClient();

        if (!self::$fixturesLoaded) {
            $this->loadFixtures();
            self::$fixturesLoaded = true;
        }

        $this->cleanupTestData();
    }

    private function loadFixtures(): void
    {
        $entityManager = $this->getEntityManager();

        // Tout nettoyer
        $entityManager->createQuery('DELETE FROM App\Entity\Media')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $entityManager->clear();

        // Recharger les fixtures
        $fixtures = new AppFixtures($this->service(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class));
        $fixtures->load($entityManager);

        $entityManager->flush();
        $entityManager->clear();
    }

    private function cleanupTestData(): void
    {
        $entityManager = $this->getEntityManager();

        // Supprimer uniquement les utilisateurs créés pendant les tests
        $entityManager->createQuery('DELETE FROM App\Entity\Media m 
                                 WHERE m.user IN (
                                     SELECT u FROM App\Entity\User u 
                                     WHERE u.email LIKE :pattern
                                 )')
            ->setParameter('pattern', '%guest_test%')
            ->execute();

        $entityManager->createQuery('DELETE FROM App\Entity\User u 
                                 WHERE u.email LIKE :pattern')
            ->setParameter('pattern', '%guest_test%')
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

    protected function login(string $email = 'user1@test.com'): void
    {
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        self::assertNotNull($user, "User with email $email not found");
        $this->user->loginUser($user);
    }

    protected function loginAsAdmin(): User
    {
        $admin = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@test.com']);

        self::assertNotNull($admin, 'Admin not found - check your fixtures');
        $this->user->loginUser($admin);

        return $admin;
    }

    /**
     * @param array<string, mixed> $formData
     */
    protected function submit(string $button, array $formData = [], string $method = 'POST'): Crawler
    {
        return $this->user->submitForm($button, $formData, $method);
    }
}
