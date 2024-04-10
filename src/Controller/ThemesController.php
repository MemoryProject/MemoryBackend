<?php

namespace App\Controller;

use App\Repository\ThemesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ThemesController extends AbstractController
{
    #[Route('/themes', name: 'app_themes')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Liste des themes du jeu memory',
            'path' => 'src/Controller/ThemesController.php',
        ]);
    }

    #[Route('/api/themes', name: 'themes', methods: ['GET'])]
    public function getNameList(ThemesRepository $themesRepository): JsonResponse
    {
        $themesList = $themesRepository->findAll();

        return $this->json([
            'themes' => $themesList,
        ]);
    }
}
