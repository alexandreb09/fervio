<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(NotificationRepository $repo): JsonResponse
    {
        $notifications = $repo->findUnreadByUser($this->getUser());

        return $this->json(array_map(fn ($n) => [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'data'      => $n->getData(),
            'createdAt' => $n->getCreatedAt()->format('c'),
        ], $notifications));
    }

    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['POST'])]
    public function readAll(NotificationRepository $repo): JsonResponse
    {
        $repo->markAllReadByUser($this->getUser());
        return $this->json(null, 204);
    }
}
