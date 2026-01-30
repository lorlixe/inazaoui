<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Album;
use App\Tests\Functional\FunctionalTestCase;

/**
 * Tests fonctionnels pour le contrôleur d'administration des albums.
 * Vérifie les opérations CRUD (Create, Read, Update, Delete) et les permissions d'accès.
 */
final class AlbumControllerTest extends FunctionalTestCase
{
    /**
     * Vérifie qu'un utilisateur non-admin ne peut pas accéder à la liste des albums.
     */
    public function testNonAdminCannotAccessAlbumIndex(): void
    {
        // Arrange : connexion en tant qu'utilisateur simple
        $this->login();

        // Act : tentative d'accès à la page admin
        $this->user->request('GET', '/admin/album');

        // Assert : accès refusé (403 Forbidden)
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Vérifie qu'un admin peut accéder à la liste des albums et voir les données des fixtures.
     */
    public function testAdminCanSeeAlbumList(): void
    {
        // Arrange : connexion en tant qu'admin
        $this->loginAsAdmin();

        // Act : accès à la page de liste des albums
        $this->user->request('GET', '/admin/album');

        // Assert : réponse réussie et présence des albums des fixtures
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Vacances', $this->user->getResponse()->getContent());
        self::assertStringContainsString('Famille', $this->user->getResponse()->getContent());
    }

    /**
     * Vérifie qu'un admin peut ajouter un nouvel album via le formulaire.
     */
    public function testAdminCanAddAlbumList(): void
    {
        // Arrange : connexion en tant qu'admin
        $this->loginAsAdmin();

        // Act : accès au formulaire d'ajout
        $crawler = $this->user->request('GET', '/admin/album/add');
        self::assertResponseIsSuccessful();

        // Soumission du formulaire avec le nom du nouvel album
        $this->user->submitForm('Ajouter', [
            'album[name]' => 'Nouvel album',
        ]);

        // Assert : redirection vers la liste
        self::assertResponseRedirects('/admin/album');
        $this->user->followRedirect();

        // Vérification que l'album a bien été créé en base
        $entityManager = $this->getEntityManager();
        $album = $this->getEntityManager()->getRepository(Album::class)->findOneBy(['name' => 'Nouvel album']);
        self::assertNotNull($album);

        // Vérification de l'affichage dans la liste
        self::assertStringContainsString('Nouvel album', $this->user->getResponse()->getContent());

        // Cleanup : suppression de l'album de test
        $entityManager->remove($album);
        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * Vérifie qu'un admin peut accéder au formulaire de modification d'un album existant.
     */
    public function testAdminCanOpenUpdateAlbumForm(): void
    {
        // Arrange : connexion et récupération d'un album existant
        $this->loginAsAdmin();
        $album = $this->getentityManager()->getRepository(Album::class)->findOneBy([]);
        self::assertNotNull($album, 'Il faut au moins un album en base (fixtures).');

        // Act : accès au formulaire de modification
        $this->user->request('GET', '/admin/album/update/' . $album->getId());

        // Assert : réponse réussie et présence du formulaire
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
        self::assertStringContainsString('Modifier', $this->user->getResponse()->getContent());
    }

    /**
     * Vérifie qu'une tentative de modification d'un album inexistant retourne une erreur 404.
     */
    public function testUpdateNonExistingAlbumReturns404(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : tentative d'accès à un ID inexistant
        $this->user->request('GET', '/admin/album/update/999999');

        // Assert : erreur 404 Not Found
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie qu'un admin peut modifier le nom d'un album existant.
     */
    public function testAdminCanUpdateAlbum(): void
    {
        // Arrange : connexion et création d'un album de test
        $this->loginAsAdmin();
        $em = $this->getEntityManager();

        $album = new Album();
        $album->setName('Album à modifier');
        $em->persist($album);
        $em->flush();

        $id = $album->getId();
        self::assertNotNull($id);

        try {
            // Act : accès au formulaire de modification
            $this->user->request('GET', '/admin/album/update/' . $id);
            self::assertResponseIsSuccessful();

            // Soumission du formulaire avec le nouveau nom
            $this->user->submitForm('Modifier', [
                'album[name]' => 'Album renommé',
            ]);

            // Assert : redirection vers la liste
            self::assertResponseRedirects('/admin/album');
            $this->user->followRedirect();

            // Vérification de la mise à jour en base
            $em->clear();
            $updated = $em->getRepository(Album::class)->find($id);

            self::assertNotNull($updated);
            self::assertSame('Album renommé', $updated->getName());
        } finally {
            // Cleanup : suppression de l'album de test dans tous les cas
            $em->clear();
            $toDelete = $em->getRepository(Album::class)->find($id);
            if ($toDelete) {
                $em->remove($toDelete);
                $em->flush();
            }
        }
    }

    /**
     * Vérifie qu'une tentative de suppression d'un album inexistant retourne une erreur 404.
     */
    public function testDeleteNonExistingAlbumReturns404(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : tentative de suppression d'un ID inexistant
        $this->user->request('GET', '/admin/album/delete/999999');

        // Assert : erreur 404 Not Found
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie qu'un admin peut supprimer un album et qu'il disparaît de la base et de l'affichage.
     */
    public function testAdminCanDeleteAlbum(): void
    {
        // Arrange : connexion et création d'un album de test
        $this->loginAsAdmin();
        $em = $this->getEntityManager();

        $album = new Album();
        $album->setName('Album à supprimer');
        $em->persist($album);
        $em->flush();

        $id = $album->getId();
        self::assertNotNull($id);

        // Act : suppression de l'album
        $this->user->request('GET', '/admin/album/delete/' . $id);

        // Assert : redirection vers la liste
        self::assertResponseRedirects('/admin/album');
        $this->user->followRedirect();

        // Vérification que l'album n'existe plus en base
        $em->clear();
        $deleted = $em->getRepository(Album::class)->find($id);
        self::assertNull($deleted);

        // Vérification que l'album n'est plus affiché dans la liste
        self::assertStringNotContainsString('Album à supprimer', $this->user->getResponse()->getContent());
    }
}
