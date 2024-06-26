<?php

namespace App\Controller;

use App\Entity\Cards;
use App\Entity\Themes;
use App\Repository\ThemesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;

class ThemesController extends AbstractController
{
    #[Route('/api/themes', name: 'getThemes', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les thèmes.')]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des thèmes",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\Parameter(
        name: "page",
        in: "query",
        description: "Le numéro de la page à afficher",
        schema : new OA\Schema(type: "int")
    )]
    #[OA\Parameter (
        name: "limit",
        in: "query",
        description: "Le nombre de thèmes par page",
        schema: new OA\Schema(type: "int")
    )]
    #[OA\Tag(name: "Themes")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function getAllThemes(ThemesRepository $themesRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 3);

        $cacheKey = 'getAllThemes-' . $page . '-' . $limit;

        $themes = $cache->get($cacheKey, function (ItemInterface $item) use ($themesRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['themesCache']);
            return $themesRepository->findAllWithPagination($page, $limit);
        });

        $jsonThemes = $serializer->serialize($themes, 'json');

        return new JsonResponse($jsonThemes, Response::HTTP_OK, [], true);
    }
    #[Route('/api/themes/{id}', name: 'getThemeById', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les themes par id.')]
    #[OA\Response(
        response: 200,
        description: "Retourne le thème correspondant à l'id",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\Tag(name: "Themes")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
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
    #[OA\Response(
        response: 201,
        description: "Crée un thème",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\RequestBody(
        description: "Les données à créer",
        required: true,
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\Tag(name: "Themes")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer un thème')]
    #[OA\Response(
        response: 204,
        description: "Retourne le thème mis à jour",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\RequestBody(
        description: "Les données à mettre à jour",
        required: true,
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Themes::class))
        )
    )]
    #[OA\Tag(name: "Themes")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function updateTheme(Request $request, SerializerInterface $serializer, Themes $currentTheme, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $newTheme = $serializer->deserialize($request->getContent(), Themes::class, 'json');

        // Copy the fields from newTheme to currentTheme
        $currentTheme->setName($newTheme->getName());

        // Validate the currentTheme
        $errors = $validator->validate($currentTheme);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Persist and flush the currentTheme
        $em->persist($currentTheme);
        $em->flush();

        // Invalidate the cache
        $cache->invalidateTags(["themesCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/themes/{id}', name: 'deleteTheme', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un theme.')]
    #[OA\Response(
        response: 204,
        description: "Supprime un thème par id"
    )]
    #[OA\Tag(name: "Themes")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
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
