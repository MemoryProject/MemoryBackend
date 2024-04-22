<?php

namespace App\Controller;

use App\Entity\Cards;
use App\Repository\CardsRepository;
use App\Repository\ThemesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class CardsController extends AbstractController
{
    #[Route('/api/cards', name: 'getCards', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les cartes.')]
    public function getCards(CardsRepository $cardsRepository, SerializerInterface $serializer): JsonResponse
    {
        $cards = $cardsRepository->findAll();
        $jsonCards = $serializer->serialize($cards, 'json');

        return new JsonResponse($jsonCards, Response::HTTP_OK, [], true);
    }

    #[Route('/api/cards/{id}', name: 'getCardById', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les cartes par id.')]
    public function getCardById(int $id, CardsRepository $cardsRepository, SerializerInterface $serializer): JsonResponse
    {
        $card = $cardsRepository->find($id);

        if ($card === null) {
            return new JsonResponse(['error' => 'Card not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonCard = $serializer->serialize($card, 'json');

        return new JsonResponse($jsonCard, Response::HTTP_OK, [], true);
    }

    #[Route('/api/cards', name:"createCard", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une carte.')]
    public function createCard(Request $request, SerializerInterface $serializer, ThemesRepository $themesRepository, EntityManagerInterface $em): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['themeId']['id'])) {
            return new JsonResponse(['error' => 'Theme ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $theme = $themesRepository->find($requestData['themeId']['id']);

        if ($theme === null) {
            return new JsonResponse(['error' => 'Theme not found'], Response::HTTP_NOT_FOUND);
        }

        $card = new Cards();
        $card->setImageUrl($requestData['imageUrl']);
        $card->setThemeId($theme);

        $em->persist($card);
        $em->flush();

        $jsonCard = $serializer->serialize($card, 'json');

        return new JsonResponse($jsonCard, Response::HTTP_CREATED, [], true);
    }
    #[Route('/api/cards/{id}', name:"updateCard", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une carte.')]
    public function updateCard(int $id, Request $request, SerializerInterface $serializer, ThemesRepository $themesRepository, CardsRepository $cardsRepository, EntityManagerInterface $em): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['themeId']['id'])) {
            return new JsonResponse(['error' => 'Theme ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $theme = $themesRepository->find($requestData['themeId']['id']);

        if ($theme === null) {
            return new JsonResponse(['error' => 'Theme not found'], Response::HTTP_NOT_FOUND);
        }

        $card = $cardsRepository->find($id);

        if ($card === null) {
            return new JsonResponse(['error' => 'Card not found'], Response::HTTP_NOT_FOUND);
        }

        $card->setImageUrl($requestData['imageUrl']);
        $card->setThemeId($theme);

        $em->persist($card);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/cards/{id}', name: 'deleteCard', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer une carte.')]
    public function deleteCard(Cards $card, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($card);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}