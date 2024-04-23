<?php

namespace App\Controller;

use App\Entity\GameSettings;
use App\Repository\GameSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GameSettingsController extends AbstractController
{
    #[Route('/api/gameSettings', name: 'getGameSettings', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les paramètres de jeu.')]
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
    public function createGameSettings(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $gameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json');
        $em->persist($gameSettings);
        $em->flush();
        $jsonGameSettings = $serializer->serialize($gameSettings, 'json');

        return new JsonResponse($jsonGameSettings, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/gameSettings/{id}', name:"updateGameSettings", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un paramètre du jeu.')]
    public function updateGameSettings(Request $request, SerializerInterface $serializer, GameSettings $currentGameSettings, EntityManagerInterface $em): JsonResponse
    {
        $updatedGameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json', ['object_to_populate' => $currentGameSettings]);
        $em->persist($updatedGameSettings);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/gameSettings/{id}', name: 'deleteGameSettings', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un paramètre du jeu.')]
    public function deleteGameSettings(GameSettings $gameSettings, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($gameSettings);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}