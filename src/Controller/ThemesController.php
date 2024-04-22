<?php

namespace App\Controller;

use App\Entity\Cards;
use App\Entity\Themes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ThemesController extends AbstractController
{
    #[Route('/api/themes', name: 'getThemes', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les themes.')]
    public function getThemes(SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer le repository des thèmes
        $themesRepository = $em->getRepository(Themes::class);

        // Récupérer tous les thèmes
        $themes = $themesRepository->findAll();

        // Sérialiser les thèmes en JSON
        $jsonThemes = $serializer->serialize($themes, 'json');

        // Retourner une réponse JSON avec les thèmes
        return new JsonResponse($jsonThemes, Response::HTTP_OK, [], true);
    }

    #[Route('/api/themes/{id}', name: 'getThemeById', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les themes par id.')]
    public function getThemeById(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $themesRepository = $em->getRepository(Themes::class);
        $theme = $themesRepository->find($id);

        if ($theme === null) {
            return new JsonResponse(['error' => 'Theme not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonTheme = $serializer->serialize($theme, 'json');

        return new JsonResponse($jsonTheme, Response::HTTP_OK, [], true);
    }

    #[Route('/api/themes', name:"createTheme", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un theme.')]
    public function createTheme(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        // Désérialiser la requête en une entité Themes
        $theme = $serializer->deserialize($request->getContent(), Themes::class, 'json');

        // Persistez l'entité dans la base de données
        $em->persist($theme);
        $em->flush();

        // Sérialiser l'entité en JSON
        $jsonTheme = $serializer->serialize($theme, 'json');

        // Retourner une réponse JSON avec l'entité et l'URL
        return new JsonResponse($jsonTheme, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/themes/{id}', name:"updateTheme", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un theme.')]
    public function updateTheme(Request $request, SerializerInterface $serializer, Themes $currentTheme, EntityManagerInterface $em): JsonResponse
    {
        // Désérialiser la requête en une entité Themes
        $updatedTheme = $serializer->deserialize($request->getContent(),
            Themes::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentTheme]);

        // Persistez l'entité dans la base de données
        $em->persist($updatedTheme);
        $em->flush();

        // Retourner une réponse JSON avec le code HTTP 204 (No Content)
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/themes/{id}', name: 'deleteTheme', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un theme.')]
    public function deleteTheme(Themes $theme, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer le repository des cartes
        $cardsRepository = $em->getRepository(Cards::class);

        // Trouver toutes les cartes associées au thème
        $cards = $cardsRepository->findBy(['theme_id' => $theme->getId()]);

        // Supprimer toutes les cartes associées au thème
        foreach ($cards as $card) {
            $em->remove($card);
        }

        // Supprimer le thème
        $em->remove($theme);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
