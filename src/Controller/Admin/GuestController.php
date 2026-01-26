<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\User;
use App\Form\AlbumType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


/**
 * Contrôleur pour gérer les invités dans l'interface d'administration
 */

class GuestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/admin/guest', name: 'admin_guest_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 25;

        $qb = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $guests = $qb->getQuery()->getResult();

        $total = $this->entityManager->getRepository(User::class)->count([]);

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $guests,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    }
    #[Route('/admin/guest/add', name: 'admin_guest_add')]
    #[IsGranted('ROLE_ADMIN')]
    public function add(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $user->getPassword();
            if ($plainPassword === null) {
                throw new \LogicException('Password cannot be null');
            }
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/guest/block/{id}', name: 'admin_guest_block')]
    #[IsGranted('ROLE_ADMIN')]
    public function block(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Empêcher l'admin de se bloquer lui-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous bloquer vous-même.');
            return $this->redirectToRoute('admin_guest_index');
        }

        // Inverser l'état (bloquer/débloquer)
        $user->setBlocked(!$user->isBlocked());
        $this->entityManager->flush();

        $message = $user->isBlocked()
            ? 'Utilisateur bloqué avec succès.'
            : 'Utilisateur débloqué avec succès.';

        $this->addFlash('success', $message);

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): Response
    {
        $guest = $this->entityManager->getRepository(User::class)->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('User not found');
        }
        // Empêcher l'admin de se bloquer lui-même
        if ($guest === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même.');
            return $this->redirectToRoute('admin_guest_index');
        }
        $this->entityManager->remove($guest);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_guest_index');
    }
}
