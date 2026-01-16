<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-passwords',
    description: 'Génère des mots de passe pour tous les utilisateurs',
)]
class GeneratePasswordsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('default-password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe par défaut pour tous les utilisateurs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $defaultPassword = $input->getOption('default-password');

        $users = $this->userRepository->findAll();
        $count = 0;

        foreach ($users as $user) {
            // Traiter TOUS les utilisateurs sans exception
            if ($defaultPassword) {
                // Utiliser le mot de passe par défaut
                $plainPassword = $defaultPassword;
            } else {
                // Générer un mot de passe aléatoire
                $plainPassword = bin2hex(random_bytes(8)); // 16 caractères
            }

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $plainPassword
            );

            $user->setPassword($hashedPassword);

            $io->writeln(sprintf(
                'Utilisateur: %s (%s) - Mot de passe: %s',
                $user->getName(),
                $user->getEmail(),
                $plainPassword
            ));

            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d mot(s) de passe généré(s)', $count));

        return Command::SUCCESS;
    }
}
