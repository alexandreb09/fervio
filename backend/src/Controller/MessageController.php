<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\FervioEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    #[Route('/conversations', name: 'api_conversations', methods: ['GET'])]
    public function conversations(
        MessageRepository $repo,
        UserRepository $userRepo,
        SerializerInterface $serializer
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $partnerIds = $repo->findConversationPartners($user);

        $partners = [];
        foreach ($partnerIds as $partnerId) {
            $partner = $userRepo->find($partnerId);
            if (!$partner) continue;

            $messages = $repo->findConversation($user, $partner);
            $lastMessage = end($messages);
            $unread = $repo->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.sender = :partner AND m.receiver = :user AND m.isRead = false')
                ->setParameter('partner', $partner)
                ->setParameter('user', $user)
                ->getQuery()->getSingleScalarResult();

            $partners[] = [
                'partner' => $serializer->normalize($partner, null, ['groups' => ['user:list']]),
                'lastMessage' => $lastMessage ? $serializer->normalize($lastMessage, null, ['groups' => ['message:read']]) : null,
                'unreadCount' => (int) $unread,
            ];
        }

        usort($partners, fn($a, $b) => ($b['lastMessage']['createdAt'] ?? '') <=> ($a['lastMessage']['createdAt'] ?? ''));

        return $this->json($partners);
    }

    #[Route('/with/{publicId}', name: 'api_messages_conversation', methods: ['GET'], requirements: ['publicId' => '\d+'])]
    public function conversation(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] User $partner,
        MessageRepository $repo,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $messages = $repo->findConversation($user, $partner);

        foreach ($messages as $message) {
            if ($message->getReceiver()->getId() === $user->getId() && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
        $em->flush();

        return $this->json(
            $serializer->normalize($messages, null, ['groups' => ['message:read']])
        );
    }

    #[Route('', name: 'api_messages_send', methods: ['POST'])]
    public function send(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        MailerInterface $mailer,
        FervioEmail $fervioEmail,
    ): JsonResponse {
        /** @var User $sender */
        $sender = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $receiver = $userRepo->findOneBy(['publicId' => $data['receiverPublicId'] ?? 0]);
        if (!$receiver) {
            return $this->json(['error' => 'Destinataire introuvable'], 404);
        }
        if ($receiver->getId() === $sender->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous écrire à vous-même'], 400);
        }
        if (!$receiver->isAcceptMessages()) {
            return $this->json(['error' => 'Ce joueur n\'accepte pas les messages'], 403);
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($data['content'] ?? '');

        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], 422);
        }

        $em->persist($message);
        $em->flush();

        if ($receiver->isNotifyMessages()) {
            try {
                $convUrl = rtrim($_ENV['DEFAULT_URI'] ?? 'https://fervio.fr', '/') . '/messages/' . $sender->getPublicId();
                $senderName = $sender->getFirstName() . ' ' . $sender->getLastName();

                $body = "<p style=\"margin:0 0 20px;font-size:15px;color:#78604E;line-height:1.6;\">"
                    . "<strong>{$senderName}</strong> vous a envoyé un message sur Fervio :"
                    . '</p>'
                    . FervioEmail::quote($message->getContent())
                    . FervioEmail::button($convUrl, 'Répondre')
                    . '<p style="margin:0;font-size:13px;color:#9A7B6A;">Vous pouvez désactiver ces notifications depuis votre profil Fervio.</p>';

                $mailer->send($fervioEmail->build(
                    $receiver->getEmail(),
                    "Nouveau message de {$sender->getFirstName()} — Fervio",
                    $receiver->getFirstName(),
                    $body
                ));
            } catch (\Throwable) {
                // Notification non critique, on n'interrompt pas la réponse
            }
        }

        return $this->json(
            $serializer->normalize($message, null, ['groups' => ['message:read']]),
            201
        );
    }

    #[Route('/unread-count', name: 'api_messages_unread', methods: ['GET'])]
    public function unreadCount(MessageRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->json(['count' => $repo->countUnread($user)]);
    }
}
