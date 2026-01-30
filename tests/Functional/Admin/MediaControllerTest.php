<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\FunctionalTestCase;

/**
 * Tests fonctionnels pour le contrôleur d'administration des médias.
 * Vérifie les règles de visibilité et de permissions :
 * - Les utilisateurs simples ne voient que leurs propres médias
 * - Les admins voient tous les médias
 * - Les utilisateurs peuvent supprimer leurs propres médias
 * - Les utilisateurs ne peuvent pas supprimer les médias des autres
 * - Les admins peuvent supprimer tous les médias
 */
final class MediaControllerTest extends FunctionalTestCase
{
    /**
     * Vérifie qu'un utilisateur non-admin peut accéder à la liste des médias.
     * Contrairement aux autres sections admin, /admin/media est accessible aux utilisateurs simples.
     */
    public function testNonAdminCanAccessMedia(): void
    {
        // Arrange : connexion en tant qu'utilisateur simple
        $this->login();

        // Act : accès à la page des médias
        $this->user->request('GET', '/admin/media');

        // Assert : accès autorisé
        self::assertResponseIsSuccessful();
    }

    /**
     * Vérifie qu'un utilisateur non-admin ne voit que ses propres médias dans la liste.
     * Les médias des autres utilisateurs ne doivent pas être visibles.
     */
    public function testNonAdminCanOnlySeeTheirOwnMedia(): void
    {
        // Arrange : connexion en tant qu'user1
        $this->login('user1@test.com');

        // Création d'un média pour user1
        $media1 = new Media();
        $media1->setTitle('test Media User 1');
        $media1->setPath('uploads/test_user1.jpg');
        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);
        $media1->setUser($user1);
        $this->getEntityManager()->persist($media1);

        // Création d'un média pour user2
        $media2 = new Media();
        $media2->setTitle('test Media User 2');
        $media2->setPath('uploads/test_user2.jpg');
        $user2 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user2@test.com']);
        $media2->setUser($user2);
        $this->getEntityManager()->persist($media2);

        $this->getEntityManager()->flush();

        // Act : accès à la liste des médias
        $this->user->request('GET', '/admin/media');

        // Assert : user1 voit uniquement son média, pas celui de user2
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();

        self::assertStringContainsString('test Media User 1', $content);
        self::assertStringNotContainsString('test Media User 2', $content);
    }

    /**
     * Vérifie qu'un admin peut voir tous les médias, quel que soit leur propriétaire.
     */
    public function testAdminCanSeeAllMedia(): void
    {
        // Arrange : connexion admin et création de médias pour différents utilisateurs
        $this->loginAsAdmin();

        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);
        $user2 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user2@test.com']);

        $media1 = new Media();
        $media1->setTitle('Media User 1');
        $media1->setPath('uploads/admin_test_user1.jpg');
        $media1->setUser($user1);
        $this->getEntityManager()->persist($media1);

        $media2 = new Media();
        $media2->setTitle('Media User 2');
        $media2->setPath('uploads/admin_test_user2.jpg');
        $media2->setUser($user2);
        $this->getEntityManager()->persist($media2);

        $this->getEntityManager()->flush();

        // Act : accès à la liste des médias
        $this->user->request('GET', '/admin/media');

        // Assert : l'admin voit les médias de tous les utilisateurs
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();

        self::assertStringContainsString('Media User 1', $content);
        self::assertStringContainsString('Media User 2', $content);
    }

    /**
     * Vérifie que la pagination fonctionne correctement sur la liste des médias.
     */
    public function testMediaIndexPagination(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : accès à la page 2 de la liste
        $this->user->request('GET', '/admin/media?page=2');

        // Assert : la page de pagination est accessible
        self::assertResponseIsSuccessful();
    }

    /**
     * Vérifie qu'un utilisateur non-admin peut accéder au formulaire d'ajout de média.
     */
    public function testNonAdminCanOpenAddMediaForm(): void
    {
        // Arrange : connexion utilisateur simple
        $this->login();

        // Act : accès au formulaire d'ajout
        $this->user->request('GET', '/admin/media/add');

        // Assert : réponse réussie et présence du formulaire
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }

    /**
     * Vérifie qu'un admin peut accéder au formulaire d'ajout de média.
     */
    public function testAdminCanOpenAddMediaForm(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : accès au formulaire d'ajout
        $this->user->request('GET', '/admin/media/add');

        // Assert : réponse réussie et présence du formulaire
        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }

    /**
     * Vérifie qu'un admin peut supprimer n'importe quel média, même ceux d'autres utilisateurs.
     */
    public function testAdminCanDeleteAnyMedia(): void
    {
        // Arrange : connexion admin et création d'un média appartenant à user1
        $this->loginAsAdmin();

        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);

        $media = new Media();
        $media->setTitle('Media to delete');
        $media->setPath('uploads/delete_test.jpg');
        $media->setUser($user1);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Création d'un fichier fictif pour éviter les erreurs de suppression fichier
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        // Act : suppression du média
        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        // Assert : redirection et vérification que le média est supprimé en base
        self::assertResponseRedirects('/admin/media');
        $this->user->followRedirect();

        $this->getEntityManager()->clear();
        $deletedMedia = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNull($deletedMedia);
    }

    /**
     * Vérifie qu'un utilisateur non-admin peut supprimer ses propres médias.
     */
    public function testNonAdminCanDeleteTheirOwnMedia(): void
    {
        // Arrange : connexion user1 et création d'un média lui appartenant
        $this->login('user1@test.com');

        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);

        $media = new Media();
        $media->setTitle('My Media');
        $media->setPath('uploads/my_media.jpg');
        $media->setUser($user1);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Création d'un fichier fictif
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        // Act : suppression de son propre média
        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        // Assert : redirection et vérification que le média est supprimé
        self::assertResponseRedirects('/admin/media');
        $this->user->followRedirect();

        $this->getEntityManager()->clear();
        $deletedMedia = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNull($deletedMedia);
    }

    /**
     * Vérifie qu'un utilisateur non-admin ne peut PAS supprimer les médias d'autres utilisateurs.
     * Cette tentative doit retourner une erreur 403 Forbidden.
     */
    public function testNonAdminCannotDeleteOthersMedia(): void
    {
        // Arrange : connexion user1 et création d'un média appartenant à user2
        $this->login('user1@test.com');

        $user2 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user2@test.com']);

        $media = new Media();
        $media->setTitle('User2_Media');
        $media->setPath('uploads/user2_media.jpg');
        $media->setUser($user2);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Création d'un fichier fictif
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        // Act : tentative de suppression du média d'un autre utilisateur
        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        // Assert : accès refusé (403 Forbidden) et média toujours présent en base
        self::assertResponseStatusCodeSame(403);

        $this->getEntityManager()->clear();
        $media = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNotNull($media);
    }

    /**
     * Vérifie qu'une tentative de suppression d'un média inexistant retourne une erreur 404.
     */
    public function testDeleteNonExistingMediaReturns404(): void
    {
        // Arrange : connexion admin
        $this->loginAsAdmin();

        // Act : tentative de suppression d'un ID inexistant
        $this->user->request('GET', '/admin/media/delete/999999');

        // Assert : erreur 404 Not Found
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Nettoyage après chaque test : suppression des médias de test et de leurs fichiers.
     * Recherche tous les médias créés dans les tests (par pattern sur le path)
     * et supprime à la fois les fichiers et les entrées en base.
     */
    protected function tearDown(): void
    {
        $em = $this->getEntityManager();

        // Récupération des médias de test via leurs paths caractéristiques
        $testMedias = $em->getRepository(Media::class)->createQueryBuilder('m')
            ->where('m.path LIKE :test1 OR m.path LIKE :test2 OR m.path LIKE :test3 OR m.path LIKE :test4')
            ->setParameter('test1', '%test_user%')      // uploads/test_user1.jpg, uploads/test_user2.jpg
            ->setParameter('test2', '%admin_test%')     // uploads/admin_test_user1.jpg
            ->setParameter('test3', '%delete_test%')    // uploads/delete_test.jpg
            ->setParameter('test4', '%my_media%')       // uploads/my_media.jpg, uploads/user2_media.jpg
            ->setParameter('test4', '%User2_Media%')    // uploads/user2_media.jpg
            ->getQuery()
            ->getResult();

        // Suppression des fichiers et des entrées en base
        foreach ($testMedias as $media) {
            if (file_exists($media->getPath())) {
                @unlink($media->getPath());
            }
            $em->remove($media);
        }

        $em->flush();

        parent::tearDown();
    }
}
