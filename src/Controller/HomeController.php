<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// HomeController gère les pages publiques du site (front-office).

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 15; // Nombre d'invités par page

        // QueryBuilder pour la pagination
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.admin = :admin')
            ->andWhere('u.blocked = :blocked')
            ->setParameter('admin', false)
            ->setParameter('blocked', false)
            ->orderBy('u.id', 'DESC') // Les plus récents en premier
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $guests = $qb->getQuery()->getResult();

        // Compter le nombre total d'invités
        $total = $this->entityManager
            ->getRepository(User::class)
            ->count([
                'admin' => false,
                'blocked' => false,
            ]);

        $pages = ceil($total / $limit);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/guest/{id}', name: 'guest')]
    public function guest(int $id): Response
    {
        $guest = $this->entityManager->getRepository(User::class)->find($id);

        if (!$guest || $guest->isBlocked()) {
            throw $this->createNotFoundException('Guest not found');
        }


        return $this->render('front/guest.html.twig', [
            'guest' => $guest,

        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio')]
    public function portfolio(Request $request, ?int $id = null): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 6; // 12 médias par page (3 colonnes x 4 lignes)

        // Charger tous les albums (pour le menu de navigation)
        $albums = $this->entityManager->getRepository(Album::class)->findAll();
        $album = $id ? $this->entityManager->getRepository(Album::class)->find($id) : null;
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['admin' => true]);

        // QueryBuilder pour la pagination
        $qb = $this->entityManager->getRepository(Media::class)->createQueryBuilder('m');

        if ($album) {
            // Médias d'un album spécifique
            $qb->where('m.album = :album')
                ->setParameter('album', $album);
        } else {
            // Tous les médias de l'admin
            $qb->where('m.user = :user')
                ->setParameter('user', $user);
        }

        $qb->orderBy('m.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $medias = $qb->getQuery()->getResult();

        // Compter le total
        $qbCount = $this->entityManager->getRepository(Media::class)->createQueryBuilder('m');

        if ($album) {
            $qbCount->select('COUNT(m.id)')
                ->where('m.album = :album')
                ->setParameter('album', $album);
        } else {
            $qbCount->select('COUNT(m.id)')
                ->where('m.user = :user')
                ->setParameter('user', $user);
        }

        $total = $qbCount->getQuery()->getSingleScalarResult();
        $pages = ceil($total / $limit);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
