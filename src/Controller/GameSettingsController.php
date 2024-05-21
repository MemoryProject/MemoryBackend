<?php

namespace App\Controller;

use App\Entity\GameSettings;
use App\Repository\GameSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;

class GameSettingsController extends AbstractController
{
    #[Route('/api/gameSettings', name: 'getGameSettings', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les paramètres de jeu.')]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des paramètres de jeu",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
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
        description: "Le nombre de paramètres de jeu par page",
        schema: new OA\Schema(type: "int")
    )]
    #[OA\Tag(name: "GameSettings")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function getAllGameSettings(GameSettingsRepository $gameSettingsRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 3);

        $cacheKey = 'getAllGameSettings-' . $page . '-' . $limit;

        $gameSettings = $cache->get($cacheKey, function (ItemInterface $item) use ($gameSettingsRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['gameSettingsCache']);
            return $gameSettingsRepository->findAllWithPagination($page, $limit);
        });

        $jsonGameSettings = $serializer->serialize($gameSettings, 'json');

        return new JsonResponse($jsonGameSettings, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gameSettings/{id}', name: 'getGameSettingById', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les paramètres du jeu par id.')]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des parametres du jeu par id",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
        )
    )]
    #[OA\Tag(name: "GameSettings")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function getGameSettingById(int $id, GameSettingsRepository $gameSettingsRepository, SerializerInterface $serializer): JsonResponse
    {
        $gameSetting = $gameSettingsRepository->find($id);

        if ($gameSetting === null) {
            return new JsonResponse(['error' => 'Game setting not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonGameSetting = $serializer->serialize($gameSetting, 'json');

        return new JsonResponse($jsonGameSetting, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gameSettings', name:"createGameSettings", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un paramètre du jeu.')]
    #[OA\Response(
        response: 201,
        description: "Retourne les paramètres de jeu créés",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
        )
    )]
    #[OA\RequestBody(
        description: "Les données à mettre à jour",
        required: true,
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
        )
    )]
    #[OA\Tag(name: "GameSettings")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function createGameSettings(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $gameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json');
        $em->persist($gameSettings);
        $em->flush();
        $jsonGameSettings = $serializer->serialize($gameSettings, 'json');

        return new JsonResponse($jsonGameSettings, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/gameSettings/{id}', name:"updateGameSettings", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer les paramètres de jeu')]
    #[OA\Response(
        response: 204,
        description: "Retourne les paramètres de jeu mis à jour",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
        )
    )]
    #[OA\RequestBody(
        description: "Les données à mettre à jour",
        required: true,
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: GameSettings::class))
        )
    )]
    #[OA\Tag(name: "GameSettings")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function updateGameSettings(Request $request, SerializerInterface $serializer, GameSettings $currentGameSettings, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $newGameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json');

        // Copy the fields from newGameSettings to currentGameSettings
        $currentGameSettings->setDifficulty($newGameSettings->getDifficulty());

        // Validate the currentGameSettings
        $errors = $validator->validate($currentGameSettings);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Persist and flush the currentGameSettings
        $em->persist($currentGameSettings);
        $em->flush();

        // Invalidate the cache
        $cache->invalidateTags(["gameSettingsCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/gameSettings/{id}', name: 'deleteGameSettings', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un paramètre du jeu.')]
    #[OA\Response(
        response: 204,
        description: "Supprimer un paramètre du jeu par id"
    )]
    #[OA\Tag(name: "GameSettings")]
    #[OA\SecurityRequirement(name: "bearerAuth")]
    public function deleteGameSettings(GameSettings $gameSettings, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($gameSettings);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}