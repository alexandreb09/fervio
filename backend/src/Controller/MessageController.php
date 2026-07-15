<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        MailerInterface $mailer
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
                $mailer->send($this->buildMessageNotificationEmail($sender, $receiver));
            } catch (\Throwable) {
                // Notification non critique, on n'interrompt pas la réponse
            }
        }

        return $this->json(
            $serializer->normalize($message, null, ['groups' => ['message:read']]),
            201
        );
    }

    private function buildMessageNotificationEmail(User $sender, User $receiver): Email
    {
        $siteUrl   = rtrim($_ENV['DEFAULT_URI'] ?? 'https://fervio.fr', '/');
        $convUrl   = $siteUrl . '/messages/' . $sender->getPublicId();
        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@fervio.fr';
        $fromName  = $_ENV['MAILER_FROM_NAME']  ?? 'Fervio';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#FAF5EF;font-family:'Inter',Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF5EF;padding:40px 16px;">
            <tr><td align="center">
              <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #E8D4C0;overflow:hidden;">
                <tr>
                  <td style="background:#C25228;padding:28px 32px;text-align:center;">
                    <span style="color:#fff;font-size:22px;font-weight:800;letter-spacing:-0.03em;">Ferv<span style="opacity:0.75">io</span></span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:36px 32px;">
                    <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1A0F08;">Bonjour {$receiver->getFirstName()} 👋</p>
                    <p style="margin:0 0 24px;font-size:15px;color:#78604E;line-height:1.6;">
                      <strong>{$sender->getFirstName()} {$sender->getLastName()}</strong> vous a envoyé un nouveau message sur Fervio.
                    </p>
                    <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                      <tr>
                        <td style="background:#C25228;border-radius:10px;">
                          <a href="{$convUrl}" style="display:inline-block;padding:14px 28px;color:#fff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:-0.01em;">
                            Voir le message
                          </a>
                        </td>
                      </tr>
                    </table>
                    <p style="margin:0;font-size:13px;color:#9A7B6A;">
                      Vous pouvez désactiver ces notifications depuis votre profil Fervio.
                    </p>
                  </td>
                </tr>
                <tr>
                  <td style="background:#FEF0E6;padding:16px 32px;text-align:center;border-top:1px solid #E8D4C0;">
                    <p style="margin:0;font-size:12px;color:#9A7B6A;">Fervio · Cet email est envoyé automatiquement, merci de ne pas y répondre.</p>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;

        return (new Email())
            ->from("$fromName <$fromEmail>")
            ->to($receiver->getEmail())
            ->subject("Nouveau message de {$sender->getFirstName()} — Fervio")
            ->html($html);
    }

    #[Route('/unread-count', name: 'api_messages_unread', methods: ['GET'])]
    public function unreadCount(MessageRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->json(['count' => $repo->countUnread($user)]);
    }
}
