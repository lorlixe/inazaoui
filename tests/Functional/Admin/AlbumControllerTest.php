<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Album;
use App\Tests\Functional\FunctionalTestCase;

final class AlbumControllerTest extends FunctionalTestCase
{

    public function testNonAdminCannotAccessAlbumIndex(): void
    {
        $this->login();
        $this->user->request('GET', '/admin/album');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanSeeAlbumList(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Vacances', $this->user->getResponse()->getContent());
        self::assertStringContainsString('Famille', $this->user->getResponse()->getContent());
    }

    public function testAdminCanAddAlbumList(): void
    {

        $this->loginAsAdmin();
        $crawler = $this->user->request('GET', '/admin/album/add');
        self::assertResponseIsSuccessful();

        $this->user->submitForm('Ajouter', [
            'album[name]' => 'Nouvel album',
        ]);

        self::assertResponseRedirects('/admin/album');
        $this->user->followRedirect();

        $album = $this->getEntityManager()->getRepository(Album::class)->findOneBy(['name' => 'Nouvel album']);
        self::assertNotNull($album);

        self::assertStringContainsString('Nouvel album', $this->user->getResponse()->getContent());
    }
    public function testAdminCanOpenUpdateAlbumForm(): void
    {
        $this->loginAsAdmin();

        $album = $this->getentityManager()->getRepository(Album::class)->findOneBy([]);
        self::assertNotNull($album, 'Il faut au moins un album en base (fixtures).');

        $this->user->request('GET', '/admin/album/update/' . $album->getId());

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
        self::assertStringContainsString('Modifier', $this->user->getResponse()->getContent());
    }

    public function testUpdateNonExistingAlbumReturns404(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/album/update/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanUpdateAlbum(): void
    {
        $this->loginAsAdmin();

        $em = $this->getEntityManager();

        $album = new Album();
        $album->setName('Album à modifier');
        $em->persist($album);
        $em->flush();

        $id = $album->getId();
        self::assertNotNull($id);

        try {
            $this->user->request('GET', '/admin/album/update/' . $id);
            self::assertResponseIsSuccessful();

            $this->user->submitForm('Modifier', [
                'album[name]' => 'Album renommé',
            ]);

            self::assertResponseRedirects('/admin/album');
            $this->user->followRedirect();

            $em->clear();
            $updated = $em->getRepository(Album::class)->find($id);

            self::assertNotNull($updated);
            self::assertSame('Album renommé', $updated->getName());
        } finally {
            $em->clear();
            $toDelete = $em->getRepository(Album::class)->find($id);
            if ($toDelete) {
                $em->remove($toDelete);
                $em->flush();
            }
        }
    }

    public function testDeleteNonExistingAlbumReturns404(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/album/delete/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanDeleteAlbum(): void
    {
        $this->loginAsAdmin();

        $em = $this->getEntityManager();

        $album = new Album();
        $album->setName('Album à supprimer');
        $em->persist($album);
        $em->flush();

        $id = $album->getId();
        self::assertNotNull($id);

        // Act : suppression
        $this->user->request('GET', '/admin/album/delete/' . $id);

        // Assert : redirect vers index
        self::assertResponseRedirects('/admin/album');
        $this->user->followRedirect();

        // Assert : plus en base
        $em->clear();
        $deleted = $em->getRepository(Album::class)->find($id);
        self::assertNull($deleted);

        // Optionnel : plus affiché
        self::assertStringNotContainsString('Album à supprimer', $this->user->getResponse()->getContent());
    }
}
