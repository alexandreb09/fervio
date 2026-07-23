<?php

namespace App\Controller;

use App\Entity\GameProposal;
use App\Entity\Message;
use App\Entity\Report;
use App\Entity\User;
use App\Repository\GameProposalRepository;
use App\Repository\MessageRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    private const SUSPENSION_THRESHOLD = 2;

    #[Route('', name: 'api_reports_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ReportRepository $reportRepo,
        UserRepository $userRepo,
        GameProposalRepository $proposalRepo,
        MessageRepository $messageRepo,
        MailerInterface $mailer,
    ): JsonResponse {
        /** @var User $reporter */
        $reporter = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $targetType = $data['targetType'] ?? '';
        $targetId   = (int) ($data['targetId'] ?? 0);
        $category   = $data['category'] ?? '';
        $reason     = trim($data['reason'] ?? '');

        if (!in_array($targetType, Report::TARGET_TYPES, true)) {
            return $this->json(['error' => 'Type de cible invalide.'], 400);
        }
        if (!array_key_exists($category, Report::CATEGORIES)) {
            return $this->json(['error' => 'Catégorie invalide.'], 400);
        }

        $reportedUser = $this->resolveReportedUser($targetType, $targetId, $reporter, $proposalRepo, $messageRepo, $userRepo);

        if ($reportedUser === null) {
            return $this->json(['error' => 'Contenu introuvable.'], 404);
        }
        if ($reportedUser->getId() === $reporter->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous signaler vous-même.'], 400);
        }
        if ($reportRepo->hasAlreadyReported($reporter, $targetType, $targetId)) {
            return $this->json(['error' => 'Vous avez déjà signalé ce contenu.'], 409);
        }

        $report = new Report();
        $report->setReporter($reporter);
        $report->setReportedUser($reportedUser);
        $report->setTargetType($targetType);
        $report->setTargetId($targetId);
        $report->setCategory($category);
        $report->setReason($reason ?: null);

        $em->persist($report);
        $em->flush();

        $activeCount = $reportRepo->countActiveReportsFor($reportedUser);
        if ($activeCount >= self::SUSPENSION_THRESHOLD && !$reportedUser->isSuspended()) {
            $reportedUser->setIsSuspended(true);
            $em->flush();
        }

        $this->notifyModerators($mailer, $report, $reportedUser, $reporter, $activeCount);

        return $this->json(['message' => 'Signalement enregistré. Merci pour votre contribution.'], 201);
    }

    private function resolveReportedUser(
        string $targetType,
        int $targetId,
        User $reporter,
        GameProposalRepository $proposalRepo,
        MessageRepository $messageRepo,
        UserRepository $userRepo,
    ): ?User {
        return match ($targetType) {
            'user'     => $userRepo->find($targetId),
            'proposal' => $proposalRepo->findOneBy(['publicId' => $targetId])?->getAuthor(),
            'message'  => $this->resolveMessageOwner($messageRepo->find($targetId), $reporter),
            default    => null,
        };
    }

    private function resolveMessageOwner(?Message $message, User $reporter): ?User
    {
        if (!$message) return null;
        // The "reported user" in a message context is the other party
        return $message->getSender()->getId() === $reporter->getId()
            ? $message->getReceiver()
            : $message->getSender();
    }

    private function notifyModerators(
        MailerInterface $mailer,
        Report $report,
        User $reportedUser,
        User $reporter,
        int $activeCount,
    ): void {
        $rawEmails = $_ENV['MODERATION_EMAILS'] ?? '';
        $emails    = array_filter(array_map('trim', explode(',', $rawEmails)));
        if (!$emails) return;

        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@tennis-partner.fr';
        $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'Tennis Partner';
        $adminUrl  = ($_ENV['DEFAULT_URI'] ?? 'http://localhost:8000') . '/admin';

        $suspended = $activeCount >= self::SUSPENSION_THRESHOLD ? '⚠️ COMPTE SUSPENDU AUTOMATIQUEMENT' : '';

        $targetLabel = match ($report->getTargetType()) {
            'user'     => 'Profil joueur',
            'proposal' => 'Annonce de partie',
            'message'  => 'Message privé',
            default    => $report->getTargetType(),
        };

        $body = $this->buildModerationEmail(
            reporter: $reporter->getFirstName() . ' ' . $reporter->getLastName() . ' (' . $reporter->getEmail() . ')',
            reportedUser: $reportedUser->getFirstName() . ' ' . $reportedUser->getLastName() . ' (' . $reportedUser->getEmail() . ')',
            targetLabel: $targetLabel,
            targetId: $report->getTargetId(),
            categoryLabel: $report->getCategoryLabel(),
            reason: $report->getReason() ?? '(aucune précision)',
            activeCount: $activeCount,
            suspended: $suspended,
            adminUrl: $adminUrl,
        );

        $subject = $suspended
            ? "🚨 [TennisPartner] Compte suspendu — {$reportedUser->getFirstName()} {$reportedUser->getLastName()}"
            : "⚑ [TennisPartner] Nouveau signalement — {$reportedUser->getFirstName()} {$reportedUser->getLastName()}";

        foreach ($emails as $to) {
            $message = (new Email())
                ->from("$fromName <$fromEmail>")
                ->to($to)
                ->subject($subject)
                ->html($body);
            try {
                $mailer->send($message);
            } catch (\Throwable) {
                // Do not fail the request if mail delivery fails
            }
        }
    }

    private function buildModerationEmail(
        string $reporter,
        string $reportedUser,
        string $targetLabel,
        int $targetId,
        string $categoryLabel,
        string $reason,
        int $activeCount,
        string $suspended,
        string $adminUrl,
    ): string {
        $suspendedBanner = $suspended
            ? "<div style=\"background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-weight:700;color:#DC2626;\">{$suspended}</div>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Inter,system-ui,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 16px;">
    <tr><td align="center">
      <table width="100%" style="max-width:520px;background:#fff;border-radius:16px;border:1px solid #E2E8F0;">
        <tr>
          <td style="background:linear-gradient(135deg,#DC2626,#EF4444);padding:24px 32px;text-align:center;border-radius:16px 16px 0 0;">
            <span style="font-size:20px;font-weight:800;color:#fff;">🎾 Tennis Partner — Modération</span>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 32px;">
            {$suspendedBanner}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Type de contenu</span><br>
                <span style="font-size:14px;color:#0F172A;font-weight:600;">{$targetLabel} #{$targetId}</span>
              </td></tr>
              <tr><td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Signalé par</span><br>
                <span style="font-size:14px;color:#0F172A;">{$reporter}</span>
              </td></tr>
              <tr><td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Utilisateur signalé</span><br>
                <span style="font-size:14px;color:#0F172A;font-weight:600;">{$reportedUser}</span>
              </td></tr>
              <tr><td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Catégorie</span><br>
                <span style="font-size:14px;color:#0F172A;">{$categoryLabel}</span>
              </td></tr>
              <tr><td style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Raison</span><br>
                <span style="font-size:14px;color:#475569;">{$reason}</span>
              </td></tr>
              <tr><td style="padding:8px 0;">
                <span style="font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;">Signalements actifs sur ce compte</span><br>
                <span style="font-size:18px;color:#DC2626;font-weight:800;">{$activeCount}</span>
              </td></tr>
            </table>
            <div style="text-align:center;margin-top:24px;">
              <a href="{$adminUrl}" style="display:inline-block;padding:12px 28px;background:#6366F1;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;">
                Gérer dans l'administration →
              </a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;border-top:1px solid #F1F5F9;padding:16px 32px;text-align:center;border-radius:0 0 16px 16px;">
            <p style="font-size:11px;color:#CBD5E1;margin:0;">Tennis Partner — Email automatique de modération</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
