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
use Symfony\Component\Serializer\SerializerInterface;

class GameSettingsController extends AbstractController
{
    #[Route('/api/gameSettings', name: 'getGameSettings', methods: ['GET'])]
    public function getGameSettings(GameSettingsRepository $gameSettingsRepository, SerializerInterface $serializer): JsonResponse
    {
        $gameSettings = $gameSettingsRepository->findAll();
        $jsonGameSettings = $serializer->serialize($gameSettings, 'json');

        return new JsonResponse($jsonGameSettings, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gameSettings/{id}', name: 'getGameSettingById', methods: ['GET'])]
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
    public function createGameSettings(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $gameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json');
        $em->persist($gameSettings);
        $em->flush();
        $jsonGameSettings = $serializer->serialize($gameSettings, 'json');

        return new JsonResponse($jsonGameSettings, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/gameSettings/{id}', name:"updateGameSettings", methods:['PUT'])]
    public function updateGameSettings(Request $request, SerializerInterface $serializer, GameSettings $currentGameSettings, EntityManagerInterface $em): JsonResponse
    {
        $updatedGameSettings = $serializer->deserialize($request->getContent(), GameSettings::class, 'json', ['object_to_populate' => $currentGameSettings]);
        $em->persist($updatedGameSettings);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/gameSettings/{id}', name: 'deleteGameSettings', methods: ['DELETE'])]
    public function deleteGameSettings(GameSettings $gameSettings, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($gameSettings);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}