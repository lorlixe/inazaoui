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
        $admin->setName('ina');
        $admin->setEmail('admin@test.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setAdmin(true);
        $admin->setDescription('Administrateur de test');
        $manager->persist($admin);

        // Créer quelques utilisateurs de test
        $users = [];
        for ($i = 1; $i <= 300; $i++) {
            $user = new User();
            $user->setName("User Test $i");
            $user->setEmail("user$i@test.com");
            $user->setPassword($this->passwordHasher->hashPassword($user, 'user-password'));
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

        // Récupérer les images existantes
        $existingImages = glob('public/uploads/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $imageCount = count($existingImages);

        if ($imageCount === 0) {
            echo " Aucune image trouvée dans public/uploads/\n";
        } else {
            echo " $imageCount images trouvées\n";

            // Créer des médias avec les images existantes
            for ($i = 0; $i < $imageCount; $i++) {
                $media = new Media();

                // Utiliser les vraies images
                $imagePath = str_replace('public/', '', $existingImages[$i]);
                $imageFilename = basename($imagePath);

                $media->setTitle(pathinfo($imageFilename, PATHINFO_FILENAME));
                $media->setPath($imagePath);

                // Attribuer aléatoirement à un utilisateur ou à l'admin
                if (rand(0, 1)) {
                    $media->setUser($admin);
                } else {
                    $media->setUser($users[array_rand($users)]);
                }

                // Attribuer aléatoirement à un album (50% de chance)
                if (rand(0, 1)) {
                    $media->setAlbum($albums[array_rand($albums)]);
                }

                $manager->persist($media);
            }
        }

        $manager->flush();

        echo "\n Fixtures chargées avec succès !\n";
        echo "   - 1 admin (ina / password)\n";
        echo "   - 300 utilisateurs (password: user-password)\n";
        echo "   - 5 albums\n";
        echo "   - $imageCount médias\n";
    }
}
