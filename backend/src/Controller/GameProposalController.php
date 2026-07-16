<?php

namespace App\Controller;

use App\Entity\GameProposal;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\GameProposalRepository;
use App\Repository\UserRepository;
use App\Service\FervioEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/proposals')]
class GameProposalController extends AbstractController
{
    #[Route('', name: 'api_proposals_list', methods: ['GET'])]
    public function list(Request $request, GameProposalRepository $repo, NormalizerInterface $normalizer): JsonResponse
    {
        $proposals = $repo->findByFilters(
            $request->query->get('city'),
            $request->query->get('surface'),
            $request->query->get('gameType'),
            $request->query->get('status', 'open'),
            $request->query->getInt('authorId') ?: null,
            $request->query->get('department')
        );

        return $this->json($normalizer->normalize($proposals, null, ['groups' => ['proposal:list']]));
    }

    #[Route('/received-private', name: 'api_proposals_received_private', methods: ['GET'])]
    public function receivedPrivate(GameProposalRepository $repo, NormalizerInterface $normalizer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $proposals = $repo->findReceivedPrivate($user);

        return $this->json($normalizer->normalize($proposals, null, ['groups' => ['proposal:list', 'user:list']]));
    }

    #[Route('/{publicId}', name: 'api_proposals_show', methods: ['GET'], requirements: ['publicId' => '\d+'])]
    public function show(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] GameProposal $proposal,
        NormalizerInterface $normalizer
    ): JsonResponse {
        if ($proposal->isPrivate()) {
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if (!$currentUser) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }
            $isAuthor = $proposal->getAuthor()->getId() === $currentUser->getId();
            $isTarget = $proposal->getTargetUser()?->getId() === $currentUser->getId();
            if (!$isAuthor && !$isTarget) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }
        }

        return $this->json($normalizer->normalize($proposal, null, ['groups' => ['proposal:read', 'user:list']]));
    }

    #[Route('', name: 'api_proposals_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer,
        GameProposalRepository $repo,
        UserRepository $userRepo
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        if ($repo->countActiveByAuthor($user) >= 3) {
            return $this->json(['error' => 'Vous ne pouvez pas avoir plus de 3 annonces actives simultanément.'], 400);
        }

        $proposal = new GameProposal();
        $this->hydrateProposal($proposal, $data);
        $proposal->setAuthor($user);

        if (!empty($data['isPrivate'])) {
            if (empty($data['targetUserId'])) {
                return $this->json(['error' => 'Un destinataire est requis pour une partie privée.'], 400);
            }
            $targetUser = $userRepo->find($data['targetUserId']);
            if (!$targetUser) {
                return $this->json(['error' => 'Joueur introuvable.'], 404);
            }
            if ($targetUser->getId() === $user->getId()) {
                return $this->json(['error' => 'Vous ne pouvez pas vous proposer une partie à vous-même.'], 400);
            }
            if (!$targetUser->isAcceptPrivateProposals()) {
                return $this->json(['error' => "Ce joueur n'accepte pas les propositions de partie privée."], 400);
            }
            $proposal->setIsPrivate(true);
            $proposal->setTargetUser($targetUser);
        }

        $errors = $validator->validate($proposal);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], 422);
        }

        $em->persist($proposal);
        $em->flush();

        return $this->json($normalizer->normalize($proposal, null, ['groups' => ['proposal:read', 'user:list']]), 201);
    }

    #[Route('/{publicId}', name: 'api_proposals_update', methods: ['PUT', 'PATCH'], requirements: ['publicId' => '\d+'])]
    public function update(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] GameProposal $proposal,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($proposal->getAuthor()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $this->hydrateProposal($proposal, $data);

        $errors = $validator->validate($proposal);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], 422);
        }

        $em->flush();

        return $this->json($normalizer->normalize($proposal, null, ['groups' => ['proposal:read', 'user:list']]));
    }

    #[Route('/{publicId}', name: 'api_proposals_delete', methods: ['DELETE'], requirements: ['publicId' => '\d+'])]
    public function delete(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] GameProposal $proposal,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($proposal->getAuthor()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $em->remove($proposal);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{publicId}/join', name: 'api_proposals_join', methods: ['POST'], requirements: ['publicId' => '\d+'])]
    public function join(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] GameProposal $proposal,
        EntityManagerInterface $em,
        NormalizerInterface $normalizer,
        MailerInterface $mailer,
        FervioEmail $fervioEmail,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($proposal->getAuthor()->getId() === $user->getId()) {
            return $this->json(['error' => "Vous êtes l'auteur de cette annonce"], 400);
        }
        if ($proposal->getStatus() !== 'open') {
            return $this->json(['error' => "Cette annonce n'est plus ouverte"], 400);
        }
        if ($proposal->isFull()) {
            return $this->json(['error' => 'Cette partie est complète'], 400);
        }
        if ($proposal->hasParticipant($user)) {
            return $this->json(['error' => 'Vous avez déjà rejoint cette partie'], 400);
        }

        $proposal->addParticipant($user);

        if ($proposal->isFull()) {
            $proposal->setStatus('full');
        }

        $em->persist(new Notification($proposal->getAuthor(), 'proposal_join', [
            'joinerFirstName'  => $user->getFirstName(),
            'joinerLastName'   => $user->getLastName() ?? '',
            'proposalTitle'    => $proposal->getTitle(),
            'proposalPublicId' => $proposal->getPublicId(),
        ]));

        $em->flush();

        $author = $proposal->getAuthor();
        if ($author->isNotifyProposalReplies()) {
            $joiner     = $user->getFirstName() . ($user->getLastName() ? ' ' . $user->getLastName() : '');
            $proposalUrl = rtrim($_ENV['DEFAULT_URI'] ?? 'https://fervio.fr', '/') . '/annonces/' . $proposal->getPublicId();

            $body = '<p style="margin:0 0 16px;font-size:15px;color:#78604E;line-height:1.6;">'
                . htmlspecialchars($joiner, ENT_QUOTES) . ' vient de rejoindre votre annonce :'
                . '</p>'
                . '<p style="margin:0 0 24px;font-size:15px;font-weight:700;color:#3D2A20;">'
                . htmlspecialchars($proposal->getTitle(), ENT_QUOTES)
                . '</p>'
                . FervioEmail::button($proposalUrl, 'Voir l\'annonce')
                . FervioEmail::fallbackLink($proposalUrl);

            try {
                $mailer->send($fervioEmail->build(
                    $author->getEmail(),
                    'Quelqu\'un a rejoint votre partie — Fervio',
                    $author->getFirstName(),
                    $body
                ));
            } catch (\Throwable) {}
        }

        return $this->json($normalizer->normalize($proposal, null, ['groups' => ['proposal:read', 'user:list']]));
    }

    #[Route('/{publicId}/leave', name: 'api_proposals_leave', methods: ['DELETE'], requirements: ['publicId' => '\d+'])]
    public function leave(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] GameProposal $proposal,
        EntityManagerInterface $em,
        NormalizerInterface $normalizer
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$proposal->hasParticipant($user)) {
            return $this->json(['error' => "Vous n'avez pas rejoint cette partie"], 400);
        }

        $proposal->removeParticipant($user);

        if ($proposal->getStatus() === 'full') {
            $proposal->setStatus('open');
        }

        $em->flush();

        return $this->json($normalizer->normalize($proposal, null, ['groups' => ['proposal:read', 'user:list']]));
    }

    private function hydrateProposal(GameProposal $proposal, array $data): void
    {
        if (isset($data['title'])) $proposal->setTitle($data['title']);
        if (array_key_exists('description', $data)) $proposal->setDescription($data['description'] ?: null);
        if (isset($data['city'])) $proposal->setCity($data['city']);
        if (array_key_exists('postalCode', $data)) $proposal->setPostalCode(!empty($data['postalCode']) ? $data['postalCode'] : null);
        if (array_key_exists('address', $data)) $proposal->setAddress($data['address'] ?: null);
        if (array_key_exists('surface', $data)) $proposal->setSurface($data['surface'] ?: null);
        if (array_key_exists('gameType', $data)) $proposal->setGameType($data['gameType'] ?: null);
        if (array_key_exists('minRanking', $data)) $proposal->setMinRanking($data['minRanking'] ?: null);
        if (array_key_exists('maxRanking', $data)) $proposal->setMaxRanking($data['maxRanking'] ?: null);
        if (array_key_exists('maxPlayers', $data)) $proposal->setMaxPlayers(max(1, (int) $data['maxPlayers']));
        if (array_key_exists('duration', $data)) $proposal->setDuration($data['duration'] ? (int) $data['duration'] : null);
        if (isset($data['status'])) $proposal->setStatus($data['status']);
        if (array_key_exists('latitude', $data)) $proposal->setLatitude($data['latitude'] ?: null);
        if (array_key_exists('longitude', $data)) $proposal->setLongitude($data['longitude'] ?: null);
        if (!empty($data['scheduledAt'])) {
            $proposal->setScheduledAt(new \DateTime($data['scheduledAt']));
        }
    }
}
