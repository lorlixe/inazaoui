<?php

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;

/**
 * Tests fonctionnels pour le HomeController
 * Vérifie le bon fonctionnement des pages publiques du site
 */
final class HomeControllerTest extends FunctionalTestCase
{
    /**
     * Test que la page d'accueil est accessible
     */
    public function testHomePageIsAccessible(): void
    {
        // Effectue une requête GET sur la page d'accueil
        $this->user->request('GET', '/');

        // Vérifie que la réponse est un succès (code 200)
        self::assertResponseIsSuccessful();
    }

    /**
     * Test que la page des invités affiche uniquement les invités non bloqués
     */
    public function testGuestsPageDisplaysNonBlockedGuests(): void
    {
        // Vérifie qu'il y a des invités non bloqués en base
        $em = $this->getEntityManager();
        $guestsCount = $em->getRepository(User::class)->count([
            'admin' => false,
            'blocked' => false
        ]);

        self::assertGreaterThan(0, $guestsCount, 'Il devrait y avoir au moins un invité non bloqué');

        // Effectue une requête GET sur la page des invités
        $this->user->request('GET', '/guests');

        // Vérifie que la réponse est un succès
        self::assertResponseIsSuccessful();

        // Récupère le contenu HTML de la réponse
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content);

        // Vérifie que le titre "Invités" est présent
        self::assertStringContainsString('Invités', $content);
    }

    /**
     * Test que la page d'un invité spécifique affiche ses détails
     */
    public function testGuestPageDisplaysGuestDetails(): void
    {
        // Récupère l'entity manager
        $em = $this->getEntityManager();

        // Recherche un invité non admin et non bloqué en base
        $guest = $em->getRepository(User::class)->findOneBy([
            'email' => 'user2@test.com',
            'admin' => false,
            'blocked' => false
        ]);

        // Vérifie que l'invité existe
        self::assertNotNull($guest);

        // Effectue une requête GET sur la page de l'invité
        $this->user->request('GET', '/guest/' . $guest->getId());

        // Vérifie que la réponse est un succès
        self::assertResponseIsSuccessful();
    }

    /**
     * Test qu'un utilisateur bloqué retourne une erreur 404
     */
    public function testGuestPageReturns404ForBlockedUser(): void
    {
        $em = $this->getEntityManager();

        // Crée un utilisateur bloqué pour le test
        $blockedGuest = new User();
        $blockedGuest->setName('Blocked User')
            ->setEmail('blocked@test.com')
            ->setPassword('test')
            ->setBlocked(true); // Important : utilisateur bloqué

        // Enregistre l'utilisateur bloqué en base
        $em->persist($blockedGuest);
        $em->flush();

        // Tente d'accéder à la page de l'utilisateur bloqué
        $this->user->request('GET', '/guest/' . $blockedGuest->getId());

        // Vérifie que la réponse est une erreur 404 (introuvable)
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Test que la page portfolio affiche tous les médias
     */
    public function testPortfolioPageDisplaysAllMedia(): void
    {
        // Effectue une requête GET sur la page portfolio (sans album spécifique)
        $this->user->request('GET', '/portfolio');

        // Vérifie que la réponse est un succès
        self::assertResponseIsSuccessful();
    }

    /**
     * Test que la page portfolio peut afficher les médias d'un album spécifique
     */
    public function testPortfolioPageDisplaysAlbumMedia(): void
    {
        $em = $this->getEntityManager();

        // Recherche un album en base
        $album = $em->getRepository(Album::class)->findOneBy([]);

        // Si un album existe
        if ($album) {
            // Effectue une requête GET sur la page portfolio de cet album
            $this->user->request('GET', '/portfolio/' . $album->getId());
            self::assertResponseIsSuccessful();
        } else {
            // Si aucun album n'existe, on saute le test
            self::markTestSkipped('No album in database');
        }
    }

    /**
     * Test que la page "À propos" est accessible
     */
    public function testAboutPageIsAccessible(): void
    {
        // Effectue une requête GET sur la page "À propos"
        $this->user->request('GET', '/about');

        // Vérifie que la réponse est un succès
        self::assertResponseIsSuccessful();
    }

    /**
     * Test que la pagination fonctionne sur la page des invités
     */
    public function testGuestsPaginationWorks(): void
    {
        // Teste la page 1
        $this->user->request('GET', '/guests?page=1');
        self::assertResponseIsSuccessful();

        // Teste la page 2
        $this->user->request('GET', '/guests?page=2');
        self::assertResponseIsSuccessful();
    }
}
