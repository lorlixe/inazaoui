<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\FunctionalTestCase;

final class MediaControllerTest extends FunctionalTestCase
{
    public function testNonAdminCanAccessMedia(): void
    {
        $this->login();
        $this->user->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
    }

    public function testNonAdminCanOnlySeeTheirOwnMedia(): void
    {
        $this->login('user1@test.com');

        // Créer un média pour user1
        $media1 = new Media();
        $media1->setTitle('Media User 1');
        $media1->setPath('uploads/test_user1.jpg');
        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);
        $media1->setUser($user1);
        $this->getEntityManager()->persist($media1);

        // Créer un média pour user2
        $media2 = new Media();
        $media2->setTitle('Media User 2');
        $media2->setPath('uploads/test_user2.jpg');
        $user2 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user2@test.com']);
        $media2->setUser($user2);
        $this->getEntityManager()->persist($media2);

        $this->getEntityManager()->flush();

        $this->user->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();

        // User1 voit son média
        self::assertStringContainsString('Media User 1', $content);
        // User1 ne voit PAS le média de User2
        self::assertStringNotContainsString('Media User 2', $content);
    }

    public function testAdminCanSeeAllMedia(): void
    {
        $this->loginAsAdmin();

        // Créer des médias pour différents utilisateurs
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

        $this->user->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();

        // Admin voit les médias de tous les utilisateurs
        self::assertStringContainsString('Media User 1', $content);
        self::assertStringContainsString('Media User 2', $content);
    }

    public function testMediaIndexPagination(): void
    {
        $this->loginAsAdmin();

        // Tester la pagination avec page 2
        $this->user->request('GET', '/admin/media?page=2');

        self::assertResponseIsSuccessful();
    }

    public function testNonAdminCanOpenAddMediaForm(): void
    {
        $this->login();

        $this->user->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }

    public function testAdminCanOpenAddMediaForm(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }



    public function testAdminCanDeleteAnyMedia(): void
    {
        $this->loginAsAdmin();

        // Créer un média appartenant à user1
        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);

        $media = new Media();
        $media->setTitle('Media to delete');
        $media->setPath('uploads/delete_test.jpg');
        $media->setUser($user1);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Créer un fichier fictif pour éviter l'erreur
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        self::assertResponseRedirects('/admin/media');
        $this->user->followRedirect();

        // Vérifier que le média est supprimé
        $this->getEntityManager()->clear();
        $deletedMedia = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNull($deletedMedia);
    }

    public function testNonAdminCanDeleteTheirOwnMedia(): void
    {
        $this->login('user1@test.com');

        $user1 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user1@test.com']);

        $media = new Media();
        $media->setTitle('My Media');
        $media->setPath('uploads/my_media.jpg');
        $media->setUser($user1);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Créer un fichier fictif
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        self::assertResponseRedirects('/admin/media');
        $this->user->followRedirect();

        // Vérifier que le média est supprimé
        $this->getEntityManager()->clear();
        $deletedMedia = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNull($deletedMedia);
    }

    public function testNonAdminCannotDeleteOthersMedia(): void
    {
        $this->login('user1@test.com');

        // Créer un média appartenant à user2
        $user2 = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'user2@test.com']);

        $media = new Media();
        $media->setTitle('User2 Media');
        $media->setPath('uploads/user2_media.jpg');
        $media->setUser($user2);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        $mediaId = $media->getId();

        // Créer un fichier fictif
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        touch($media->getPath());

        $this->user->request('GET', '/admin/media/delete/' . $mediaId);

        // Doit retourner 403 Forbidden
        self::assertResponseStatusCodeSame(403);

        // Vérifier que le média n'est PAS supprimé
        $this->getEntityManager()->clear();
        $media = $this->getEntityManager()->getRepository(Media::class)->find($mediaId);
        self::assertNotNull($media);
    }

    public function testDeleteNonExistingMediaReturns404(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/media/delete/999999');

        self::assertResponseStatusCodeSame(404);
    }


    protected function tearDown(): void
    {
        // Nettoyer les médias de test
        $em = $this->getEntityManager();
        $testMedias = $em->getRepository(Media::class)->findBy([]);

        foreach ($testMedias as $media) {
            if (str_contains($media->getTitle(), 'Test Media')) {
                // Supprimer le fichier physique
                if (file_exists($media->getPath())) {
                    unlink($media->getPath());
                }
                $em->remove($media);
            }
        }

        $em->flush();

        // Nettoyer les fichiers temporaires
        $testFiles = [
            'uploads/delete_test.jpg',
            'uploads/my_media.jpg',
            'uploads/user2_media.jpg',
        ];

        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
