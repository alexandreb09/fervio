<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/api/partners')]
class PartnerController extends AbstractController
{
    #[Route('', name: 'api_partners_list', methods: ['GET'])]
    public function list(NormalizerInterface $normalizer): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();
        return $this->json($normalizer->normalize($me->getPartners()->toArray(), null, ['groups' => ['user:list']]));
    }

    #[Route('/{id}', name: 'api_partners_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(User $partner, EntityManagerInterface $em, NormalizerInterface $normalizer): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();

        if ($me->getId() === $partner->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous ajouter vous-même'], 400);
        }

        $me->addPartner($partner);
        $em->flush();

        return $this->json($normalizer->normalize($partner, null, ['groups' => ['user:list']]), 201);
    }

    #[Route('/{id}', name: 'api_partners_remove', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function remove(User $partner, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();
        $me->removePartner($partner);
        $em->flush();

        return $this->json(null, 204);
    }
}
