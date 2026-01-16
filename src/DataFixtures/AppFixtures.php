<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Album;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Créer un admin de test
        $admin = new User();
        $admin->setName('Admin Test');
        $admin->setEmail('admin@test.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setAdmin(true);
        $admin->setDescription('Administrateur de test');
        $manager->persist($admin);

        // Créer quelques utilisateurs de test
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setName("User Test $i");
            $user->setEmail("user$i@test.com");
            $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
            $user->setAdmin(false);
            $user->setDescription("Utilisateur de test numéro $i");
            $manager->persist($user);
            $users[] = $user;
        }

        // Créer quelques albums de test
        $albums = [];
        $albumNames = ['Vacances', 'Famille', 'Travail', 'Anniversaire', 'Voyages'];

        foreach ($albumNames as $albumName) {
            $album = new Album();
            $album->setName($albumName);
            $manager->persist($album);
            $albums[] = $album;
        }

        // Créer quelques médias de test
        for ($i = 1; $i <= 20; $i++) {
            $media = new Media();
            $media->setTitle("Photo Test $i");
            $media->setPath("uploads/test_$i.jpg");

            // Attribuer aléatoirement à un utilisateur
            $media->setUser($users[array_rand($users)]);

            // Attribuer aléatoirement à un album (50% de chance)
            if (rand(0, 1)) {
                $media->setAlbum($albums[array_rand($albums)]);
            }

            $manager->persist($media);
        }

        $manager->flush();
    }
}
