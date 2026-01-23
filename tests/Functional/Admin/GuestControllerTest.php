<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use App\Tests\Functional\FunctionalTestCase;

final class GuestControllerTest extends FunctionalTestCase
{
    public function testNonAdminCannotAccessGuestIndex(): void
    {
        $this->login();
        $this->user->request('GET', '/admin/guest');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanOpenAddGuestForm(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/guest/add');

        self::assertResponseIsSuccessful();
        $content = $this->user->getResponse()->getContent();
        self::assertNotFalse($content, 'Response content should not be false');
        self::assertStringContainsString('<form', $content);
    }

    public function testAdminCanAddGuest(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/guest/add');
        self::assertResponseIsSuccessful();

        $this->user->submitForm('Ajouter', [
            'user[name]' => 'guest_test',
            'user[email]' => 'guest_test@example.com',
            'user[password]' => 'password123',
        ]);

        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        // Vérifie en base que l'utilisateur existe
        $created = $this->getentityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'guest_test@example.com']);

        self::assertNotNull($created);

        // Et que le mot de passe a bien été hashé (donc différent du plain)
        self::assertNotSame('password123', $created->getPassword());
    }

    public function testAdminBlockNonExistingUserReturns404(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/guest/block/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanBlockGuest(): void
    {
        $this->loginAsAdmin();


        $em = $this->getentityManager();
        // Récupérer un utilisateur qui n'est PAS un admin
        $guest = $em->getRepository(User::class)->findOneBy([
            'email' => 'user2@test.com',
            'admin' => false
        ]);

        self::assertNotNull($guest, 'Guest user not found');
        $id = $guest->getId();


        $this->user->request('GET', '/admin/guest/block/' . $id);
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $reloaded = $em->getRepository(User::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isBlocked());

        $this->user->request('GET', '/admin/guest/block/' . $id);
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $reloaded2 = $em->getRepository(User::class)->find($id);
        self::assertNotNull($reloaded2);
        self::assertFalse($reloaded2->isBlocked());
    }

    public function testAdminCannotBlockHimself(): void
    {

        $admin = $this->loginAsAdmin();



        $this->user->request('GET', '/admin/guest/block/' . $admin->getId());


        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em = $this->getEntityManager();
        $em->clear();
        $adminReloaded = $em->getRepository(User::class)->find($admin->getId());
        self::assertNotNull($adminReloaded);
        self::assertFalse($adminReloaded->isBlocked());
    }

    public function testAdminCanDeleteGuest(): void
    {
        $this->loginAsAdmin();

        $em = $this->getEntityManager();

        $guest = $em->getRepository(User::class)->findOneBy([
            'email' => 'user3@test.com',
            'admin' => false
        ]);

        self::assertNotNull($guest);
        $id = $guest->getId();

        $this->user->request('GET', '/admin/guest/delete/' . $id);
        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em->clear();
        $deleted = $em->getRepository(User::class)->find($id);
        self::assertNull($deleted);
    }

    public function testAdminCannotDeleteHimself(): void
    {
        $admin = $this->loginAsAdmin();

        $this->user->request('GET', '/admin/guest/delete/' . $admin->getId());

        self::assertResponseRedirects('/admin/guest');
        $this->user->followRedirect();

        $em = $this->getEntityManager();
        $em->clear();
        $adminReloaded = $em->getRepository(User::class)->find($admin->getId());
        self::assertNotNull($adminReloaded);
    }

    public function testAdminDeleteNonExistingUserReturns404(): void
    {
        $this->loginAsAdmin();

        $this->user->request('GET', '/admin/guest/delete/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
