<?php

namespace App\Controller;

use App\Entity\Cards;
use App\Entity\Themes;
use App\Repository\CardsRepository;
use App\Repository\ThemesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hateoas\HateoasBuilder;
use Psr\Cache\InvalidArgumentException;
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

class CardsController extends AbstractController
{
    #[Route('/api/cards', name: 'cards', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir les cartes.')]
    public function getAllCards(CardsRepository $cardsRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 3);

        $cacheKey = 'getAllCards-' . $page . '-' . $limit;

        $cards = $cache->get($cacheKey, function (ItemInterface $item) use ($cardsRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['cardsCache']);
            return $cardsRepository->findAllWithPagination($page, $limit);
        });

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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer une carte')]
    public function updateCard(Request $request, SerializerInterface $serializer, Cards $currentCard, EntityManagerInterface $em, ThemesRepository $themesRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $newCard = $serializer->deserialize($request->getContent(), Cards::class, 'json');

        // Copy the fields from newCard to currentCard
        $currentCard->setImageUrl($newCard->getImageUrl());

        // Get the themeId from the request content
        $content = $request->toArray();
        $themeId = $content['theme_id']['id'] ?? -1;

        // Find the theme
        $theme = $themesRepository->find($themeId);

        // If the theme does not exist, return an error
        if ($theme === null) {
            return new JsonResponse(['error' => 'Theme not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Set the theme of the currentCard
        $currentCard->setThemeId($theme);

        // Validate the currentCard
        $errors = $validator->validate($currentCard);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Persist and flush the currentCard
        $em->persist($currentCard);
        $em->flush();

        // Invalidate the cache
        $cache->invalidateTags(["cardsCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/cards/{id}/d', name: 'deleteCard', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer une carte.')]
    public function deleteCard(Cards $card, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($card);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}