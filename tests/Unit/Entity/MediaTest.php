<?php

declare(strict_types=1);

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaTest extends TestCase
{

    /**
     * Vérifie qu'une nouvelle entité Media n'a pas d'id
     * tant qu'elle n'est pas persistée en base.
     */
    public function testGetIdReturnsNullByDefault(): void
    {
        $media = new Media();
        self::assertNull($media->getId());
    }

    /**
     * Vérifie que title est correctement stocké et retourné.
     */
    public function testGetAndSetTitle(): void
    {
        $media = new Media();
        $title = 'Ma photo';

        $media->setTitle($title);

        self::assertSame($title, $media->getTitle());
    }

    /**
     * Vérifie que l'on peut associer un User à Media et récupérer le User.
     */
    public function testGetAndSetUser(): void
    {
        $media = new Media();
        $user = $this->createMock(User::class);

        $media->setUser($user);

        self::assertSame($user, $media->getUser());
    }

    /**
     * Vérifie qu'un média peut être défini et récupéré.
     */
    public function testGetAndSetFile(): void
    {
        $media = new Media();
        $file = $this->createMock(UploadedFile::class);

        $media->setFile($file);

        self::assertSame($file, $media->getFile());
    }

    /**
     * Vérifie que path est stocké et retourné.
     */
    public function testGetAndSetPath(): void
    {
        $media = new Media();
        $path = 'uploads/media/photo.webp';

        $media->setPath($path);

        self::assertSame($path, $media->getPath());
    }




    /**
     * Vérifie que l'on peut associer un Album à Media et le récupérer.
     */
    public function testGetAndSetAlbum(): void
    {
        $media = new Media();
        $album = $this->createMock(Album::class);

        $media->setAlbum($album);

        self::assertSame($album, $media->getAlbum());
    }
}
