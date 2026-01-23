<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//Ce contrôleur MediaController gère les fichiers médias (images, documents, etc.) dans l'interface d'administration

class MediaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $criteria = [];

        // Si pas admin, afficher seulement ses médias
        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $this->entityManager->getRepository(Media::class)->findBy(
            $criteria,
            ['id' => 'ASC'],
            25,
            25 * ($page - 1)
        );
        $total = $this->entityManager->getRepository(Media::class)->count([]);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request): Response
    {
        $media = new Media();

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new \LogicException('User must be logged in');
        }

        $media->setUser($user);
        $form = $this->createForm(MediaType::class, $media, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($user);
            }

            // ✅ Vérifier que le fichier existe
            $file = $media->getFile();
            if ($file === null) {
                throw new \LogicException('File is required');
            }

            $extension = $file->guessExtension();
            if ($extension === null) {
                $extension = 'bin';
            }

            $media->setPath('uploads/' . md5(uniqid()) . '.' . $extension);
            $file->move('uploads/', $media->getPath());

            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id): Response
    {
        $media = $this->entityManager->getRepository(Media::class)->find($id);
        if (!$media) {
            throw $this->createNotFoundException('Media not found');
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($media->getUser() !== $this->getUser()) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer un média qui ne vous appartient pas.');
            }
        }


        // Supprime le fichier physique
        if (file_exists($media->getPath())) {
            unlink($media->getPath());
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_media_index');
    }
}
