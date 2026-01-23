<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class LoginTest extends FunctionalTestCase
{
    public function testThatLoginShouldSucceeded(): void
    {
        $this->get('/login');

        $this->user->submitForm('Connexion', [
            "_username" => 'User Test 1',
            '_password' => 'test123'
        ]);

        self::assertResponseRedirects();
        $this->user->followRedirect();

        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);
        self::assertTrue($authorizationChecker->isGranted('IS_AUTHENTICATED'));

        $this->get('/logout');

        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
        self::assertResponseRedirects();
    }

    public function testThatLoginShouldFailed(): void
    {
        $this->get('/login');

        $this->user->submitForm('Connexion', [
            "_username" => 'User Test 1',
            '_password' => 'fail'
        ]);

        self::assertResponseRedirects('/login');

        $this->user->followRedirect();

        self::assertResponseIsSuccessful();

        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);
        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
    }
}
