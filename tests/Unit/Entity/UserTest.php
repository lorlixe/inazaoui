<?php

declare(strict_types=1);

use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

final class UserTest extends TestCase
{
    /**
     * Vérifie qu'un nouvel utilisateur n'a pas d'identifiant
     * tant qu'il n'est pas persisté en base de données.
     */
    public function testGedId(): void
    {
        $user = new User();
        self::assertNull($user->getId());
    }

    /**
     * Vérifie que l'email peut être défini et récupéré correctement.
     */
    public function testGetAndSetEmail(): void
    {
        $user = new User();
        $email = 'user@test.com';

        $user->setEmail($email);

        self::assertSame($email, $user->getEmail());
    }

    /**
     * Vérifie que le nom de l'utilisateur peut être défini et retourné.
     */
    public function testGetAndSetName(): void
    {
        $user = new User();
        $name = 'user';

        $user->setName($name);

        self::assertSame($name, $user->getName());
    }

    /**
     * Vérifie que la description de l'utilisateur
     * peut être définie et récupérée.
     */
    public function testGetAndSetDescripton(): void
    {
        $user = new User();
        $description = 'description';

        $user->setDescription($description);

        self::assertSame($description, $user->getDescription());
    }

    /**
     * Vérifie que l'utilisateur peut avoir un mot de passe
     * et que celui-ci est correctement stocké.
     */
    public function testUserCanHavePassword(): void
    {
        $user = new User();
        $password = 'password_123';

        $user->setPassword($password);

        self::assertSame($password, $user->getPassword());
    }

    /**
     * Vérifie qu'un média peut être ajouté à un utilisateur
     * et qu'il est bien présent dans la collection.
     */
    public function testGetandAddImage(): void
    {
        $user = new User();
        $media = $this->createMock(Media::class);

        $user->addMedia($media);

        self::assertCount(1, $user->getMedias());
        self::assertNotEmpty($user->getMedias());
        self::assertTrue($user->getMedias()->contains($media));
    }

    /**
     * Vérifie qu'un média peut être supprimé de l'utilisateur
     * et que la collection est bien vide après suppression.
     */
    public function testRemouveImage(): void
    {
        $user = new User();
        $media = $this->createMock(Media::class);

        $user->addMedia($media);
        $user->removeMedia($media);

        self::assertCount(0, $user->getMedias());
        self::assertEmpty($user->getMedias());
        self::assertFalse($user->getMedias()->contains($media));
    }

    /**
     * Vérifie que la méthode getMedias retourne bien
     * une collection Doctrine.
     */
    public function testGetMediasReturnsCollection(): void
    {
        $user = new User();
        $medias = $user->getMedias();

        $this->assertInstanceOf(Collection::class, $medias);
    }

    /**
     * Vérifie qu'un utilisateur standard (non admin, non bloqué)
     * possède uniquement le rôle ROLE_USER.
     */
    public function testUserHasRoleUser(): void
    {
        $user = new User();
        $user->setAdmin(false);
        $user->setBlocked(false);

        $roles = $user->getRoles();

        $this->assertIsArray($roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertNotContains('ROLE_ADMIN', $roles);
    }

    /**
     * Vérifie qu'un utilisateur administrateur
     * possède bien le rôle ROLE_ADMIN.
     */
    public function testAdminUserHasRoleAdmin(): void
    {
        $user = new User();
        $user->setAdmin(true);

        $roles = $user->getRoles();

        $this->assertTrue($user->isAdmin());
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Vérifie qu'un administrateur non bloqué
     * ne possède pas le rôle ROLE_BLOCKED.
     */
    public function testAdminUserDoesNotHaveRoleBlocked(): void
    {
        $user = new User();
        $user->setAdmin(true);
        $user->setBlocked(false);

        $roles = $user->getRoles();

        $this->assertNotContains('ROLE_BLOCKED', $roles);
        $this->assertFalse($user->isBlocked());
    }

    /**
     * Vérifie qu'un utilisateur bloqué
     * possède le rôle ROLE_BLOCKED.
     */
    public function testBlockedUserHasRoleBlocked(): void
    {
        $user = new User();
        $user->setBlocked(true);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_BLOCKED', $roles);
        $this->assertTrue($user->isBlocked());
    }
}
