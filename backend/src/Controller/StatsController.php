<?php

namespace App\Controller;

use App\Repository\GameProposalRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function stats(UserRepository $userRepo, GameProposalRepository $proposalRepo): JsonResponse
    {
        $cities = array_unique(array_merge(
            $userRepo->findDistinctCities(),
            $proposalRepo->findDistinctCities()
        ));

        return $this->json([
            'players' => $userRepo->countActive(),
            'proposals' => $proposalRepo->countOpenFuture(),
            'cities' => count($cities),
        ]);
    }
}
