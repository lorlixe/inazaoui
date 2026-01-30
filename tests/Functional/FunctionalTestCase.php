<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Classe de base abstraite pour les tests fonctionnels.
 * 
 * Fournit des méthodes utilitaires pour :
 * - Charger les fixtures une seule fois pour tous les tests (performance)
 * - Nettoyer les données de test créées pendant les tests
 * - Faciliter l'authentification (utilisateur simple ou admin)
 * - Accéder aux services du container
 * - Effectuer des requêtes HTTP et soumettre des formulaires
 * 
 * Toutes les classes de tests fonctionnels doivent étendre cette classe.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * Client HTTP pour effectuer les requêtes dans les tests.
     */
    protected KernelBrowser $user;

    /**
     * Flag statique pour ne charger les fixtures qu'une seule fois pour tous les tests.
     * Améliore significativement les performances des tests.
     */
    private static bool $fixturesLoaded = false;

    /**
     * Initialisation avant chaque test :
     * - Crée un nouveau client HTTP
     * - Charge les fixtures (une seule fois pour toute la suite de tests)
     * - Nettoie les données de test créées précédemment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Création du client pour effectuer les requêtes HTTP
        $this->user = static::createClient();

        // Chargement unique des fixtures pour optimiser les performances
        if (!self::$fixturesLoaded) {
            $this->loadFixtures();
            self::$fixturesLoaded = true;
        }

        // Nettoyage des données de test créées lors des tests précédents
        $this->cleanupTestData();
    }

    /**
     * Charge les fixtures initiales en base de données.
     * 
     * Processus :
     * 1. Supprime toutes les données existantes (Media, Album, User)
     * 2. Recharge les fixtures via AppFixtures
     * 3. Clear l'EntityManager pour éviter les problèmes de cache
     * 
     * Cette méthode n'est appelée qu'une seule fois par suite de tests.
     */
    private function loadFixtures(): void
    {
        $entityManager = $this->getEntityManager();

        // Nettoyage complet de la base (ordre important : entités dépendantes d'abord)
        $entityManager->createQuery('DELETE FROM App\Entity\Media')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Album')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $entityManager->clear();

        // Rechargement des fixtures via la classe AppFixtures
        $fixtures = new AppFixtures($this->service(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class));
        $fixtures->load($entityManager);

        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * Nettoie les données de test créées pendant l'exécution des tests.
     * 
     * Supprime uniquement les entités créées dans les tests (pattern 'guest_test'),
     * sans toucher aux fixtures de base. Cela permet d'éviter les conflits entre tests
     * tout en préservant les performances (pas de rechargement complet).
     */
    private function cleanupTestData(): void
    {
        $entityManager = $this->getEntityManager();

        // Suppression des médias associés aux utilisateurs de test
        $entityManager->createQuery('DELETE FROM App\Entity\Media m 
                                 WHERE m.user IN (
                                     SELECT u FROM App\Entity\User u 
                                     WHERE u.email LIKE :pattern
                                 )')
            ->setParameter('pattern', '%guest_test%')
            ->execute();

        // Suppression des utilisateurs de test
        $entityManager->createQuery('DELETE FROM App\Entity\User u 
                                 WHERE u.email LIKE :pattern')
            ->setParameter('pattern', '%guest_test%')
            ->execute();

        $entityManager->clear();
    }

    /**
     * Récupère l'EntityManager depuis le container de services.
     * 
     * @return EntityManagerInterface Instance de l'EntityManager Doctrine
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->service(EntityManagerInterface::class);
    }

    /**
     * Récupère un service depuis le container.
     * 
     * Méthode générique avec vérification d'existence et typage strict.
     * 
     * @template T of object
     * @param class-string<T> $id Identifiant du service (nom de classe complet)
     * @return T Instance du service demandé
     */
    protected function service(string $id): object
    {
        /** @var null|T $service */
        $service = $this->user->getContainer()->get($id);
        self::assertNotNull($service);
        return $service;
    }

    /**
     * Effectue une requête GET et retourne le Crawler pour inspecter la réponse.
     * 
     * @param string $uri URI à requêter
     * @param array<string, mixed> $parameters Paramètres GET optionnels
     * @return Crawler Crawler pour parser et analyser la réponse HTML
     */
    protected function get(string $uri, array $parameters = []): Crawler
    {
        return $this->user->request('GET', $uri, $parameters);
    }

    /**
     * Connecte un utilisateur pour les tests.
     * 
     * Par défaut, connecte 'user1@test.com' (utilisateur simple non-admin).
     * 
     * @param string $email Email de l'utilisateur à connecter
     * @throws \PHPUnit\Framework\AssertionFailedError Si l'utilisateur n'existe pas
     */
    protected function login(string $email = 'user1@test.com'): void
    {
        // Récupération de l'utilisateur en base
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        self::assertNotNull($user, "User with email $email not found");

        // Authentification de l'utilisateur dans le client de test
        $this->user->loginUser($user);
    }

    /**
     * Connecte l'utilisateur admin pour les tests nécessitant des privilèges administrateur.
     * 
     * @return User Instance de l'admin connecté (utile pour récupérer son ID dans les tests)
     * @throws \PHPUnit\Framework\AssertionFailedError Si l'admin n'existe pas en base
     */
    protected function loginAsAdmin(): User
    {
        // Récupération de l'admin en base (doit être présent dans les fixtures)
        $admin = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@test.com']);

        self::assertNotNull($admin, 'Admin not found - check your fixtures');

        // Authentification de l'admin
        $this->user->loginUser($admin);

        return $admin;
    }

    /**
     * Soumet un formulaire et retourne le Crawler de la réponse.
     * 
     * @param string $button Texte du bouton de soumission ou son attribut name
     * @param array<string, mixed> $formData Données du formulaire à soumettre
     * @param string $method Méthode HTTP (POST par défaut)
     * @return Crawler Crawler pour analyser la réponse après soumission
     */
    protected function submit(string $button, array $formData = [], string $method = 'POST'): Crawler
    {
        return $this->user->submitForm($button, $formData, $method);
    }
}
