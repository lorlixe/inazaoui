<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use App\Tests\Functional\FunctionalTestCase;

/**
 * Tests fonctionnels pour le contrôleur d'administration des invités (guests).
 * Vérifie les opérations de gestion des utilisateurs : création, blocage/déblocage, suppression
 * et les règles de protection (un admin ne peut pas se bloquer/supprimer lui-même).
 */
final class GuestControllerTest extends FunctionalTestCase
{
    /**
     * Vérifie qu'un utilisateur non-admin ne peut pas 
     * accéder à la liste des invités.
     */
    public function testNonAdminCannotAccessGuestIndex(): void
    {
        // Arrange : connexion en tant qu'utilisateur simple
        $this->login();

        // Act : tentative d'accès à la page admin des invités
        $this->user->request('GET', '/admin/guest');

        // Assert : accès refusé (403 Forbidden)
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Vérifie qu'un admin peut accéder au formulaire d'ajout d'un invité.
     */
    public function testAdminCanOpenAddGuestForm(): void
    {
        // Arrange : connexion en tant qu'admin
        $this->loginAsAdmin();

        // Act : accès au formulaire d'ajout
        $this->user->request('GET', '/admin/guest/add');

        // Assert : réponse réussie et présence du formulaire
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }

    /**
     * Vérifie qu'un admin peut créer un nouvel invité et que le mot de passe est correctement hashé.
     */
    public function testAdminCanAddGuest(): void
    {
        // Arrange : connexion admin et accès au formulaire
        $this->loginAsAdmin();
        $this->user->request('GET', '/admin/guest/add');
        self::assertResponseIsSuccessful();

        // Act : soumission du formulaire avec les données du nouvel invité
        $this->user->submitForm('Ajouter', [
            'user[name]' => 'guest_test',
            'user[email]' => 'guest_test@example.com',
            'user[password]' => 'password123',
        ]);

        // Assert : redirection vers la liste
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        // Vérification que l'utilisateur a bien été créé en base
        $created = $this->getentityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'guest_test@example.com']);

        self::assertNotNull($created);

        // Vérification que le mot de passe a bien été hashé (différent du plain text)
        self::assertNotSame('password123', $created->getPassword());
    }

    /**
     * Vérifie qu'une tentative de blocage d'un utilisateur inexistant retourne une erreur 404.
     */
    public function testAdminBlockNonExistingUserReturns404(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : tentative de blocage d'un ID inexistant
        $this->user->request('GET', '/admin/guest/block/999999');

        // Assert : erreur 404 Not Found
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie qu'un admin peut bloquer et débloquer un invité (toggle du statut).
     * Le premier appel bloque, le second débloque.
     */
    public function testAdminCanBlockGuest(): void
    {
        // Arrange : connexion et récupération d'un utilisateur non-admin
        $this->loginAsAdmin();
        $em = $this->getentityManager();

        $guest = $em->getRepository(User::class)->findOneBy([
            'email' => 'user2@test.com',
            'admin' => false
        ]);

        self::assertNotNull($guest, 'Guest user not found');
        $id = $guest->getId();

        // Act : premier appel pour bloquer l'utilisateur
        $this->user->request('GET', '/admin/guest/block/' . $id);

        // Assert : redirection et vérification du statut bloqué
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $reloaded = $em->getRepository(User::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isBlocked());

        // Act : second appel pour débloquer l'utilisateur
        $this->user->request('GET', '/admin/guest/block/' . $id);

        // Assert : redirection et vérification du statut débloqué
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $reloaded2 = $em->getRepository(User::class)->find($id);
        self::assertNotNull($reloaded2);
        self::assertFalse($reloaded2->isBlocked());
    }

    /**
     * Vérifie qu'un admin ne peut pas se bloquer lui-même (protection contre l'auto-blocage).
     */
    public function testAdminCannotBlockHimself(): void
    {
        // Arrange : connexion admin et récupération de l'admin connecté
        $admin = $this->loginAsAdmin();

        // Act : tentative de blocage de soi-même
        $this->user->request('GET', '/admin/guest/block/' . $admin->getId());

        // Assert : redirection mais l'admin reste débloqué
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em = $this->getEntityManager();
        $em->clear();
        $adminReloaded = $em->getRepository(User::class)->find($admin->getId());
        self::assertNotNull($adminReloaded);
        self::assertFalse($adminReloaded->isBlocked());
    }

    /**
     * Vérifie qu'un admin peut supprimer un invité de la base de données.
     */
    public function testAdminCanDeleteGuest(): void
    {
        // Arrange : connexion et récupération d'un utilisateur non-admin
        $this->loginAsAdmin();
        $em = $this->getEntityManager();

        $guest = $em->getRepository(User::class)->findOneBy([
            'email' => 'user3@test.com',
            'admin' => false
        ]);

        self::assertNotNull($guest);
        $id = $guest->getId();

        // Act : suppression de l'invité
        $this->user->request('GET', '/admin/guest/delete/' . $id);

        // Assert : redirection et vérification que l'utilisateur n'existe plus
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $deleted = $em->getRepository(User::class)->find($id);
        self::assertNull($deleted);
    }

    /**
     * Vérifie qu'un admin ne peut pas se supprimer lui-même (protection contre l'auto-suppression).
     */
    public function testAdminCannotDeleteHimself(): void
    {
        // Arrange : connexion admin
        $admin = $this->loginAsAdmin();

        // Act : tentative de suppression de soi-même
        $this->user->request('GET', '/admin/guest/delete/' . $admin->getId());

        // Assert : redirection mais l'admin existe toujours en base
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em = $this->getEntityManager();
        $em->clear();
        $adminReloaded = $em->getRepository(User::class)->find($admin->getId());
        self::assertNotNull($adminReloaded);
    }

    /**
     * Vérifie qu'une tentative de suppression d'un utilisateur inexistant retourne une erreur 404.
     */
    public function testAdminDeleteNonExistingUserReturns404(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : tentative de suppression d'un ID inexistant
        $this->user->request('GET', '/admin/guest/delete/999999');

        // Assert : erreur 404 Not Found
        self::assertResponseStatusCodeSame(404);
    }
}
