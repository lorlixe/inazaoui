<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour gérer les albums dans l'interface d'administration
 */

class AlbumController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/admin/album', name: 'admin_album_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        $albums = $this->entityManager->getRepository(Album::class)->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }

    #[Route('/admin/album/add', name: 'admin_album_add')]
    #[IsGranted('ROLE_ADMIN')]
    public function add(Request $request): Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/album/update/{id}', name: 'admin_album_update')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, int $id): Response
    {
        $album = $this->entityManager->getRepository(Album::class)->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/album/delete/{id}', name: 'admin_album_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): Response
    {
        $album = $this->entityManager->getRepository(Album::class)->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        $this->entityManager->remove($album);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_album_index');
    }
}
