<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Album;
use PHPUnit\Framework\TestCase;

final class AlbumTest extends TestCase
{
    /**
     * Vérifie qu'un nouvel Album n'a pas d'id
     * tant qu'il n'est pas persisté en base de données.
     */
    public function testGetIdReturnsNullByDefault(): void
    {
        $album = new Album();

        self::assertNull($album->getId());
    }

    /**
     * Vérifie que le nom 
     */
    public function testGetAndSetName(): void
    {
        $album = new Album();
        $name = 'Album 2026';

        $album->setName($name);

        self::assertSame($name, $album->getName());
    }
}
