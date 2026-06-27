<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['username'] ?? $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        if ($user->isSuspended()) {
            return $this->json([
                'error'     => 'Votre compte est temporairement suspendu suite à des signalements. Notre équipe de modération analyse la situation. Si vous pensez qu\'il s\'agit d\'une erreur, contactez-nous à support@tennis-partner.fr.',
                'suspended' => true,
            ], 403);
        }

        $token = $jwtManager->create($user);

        return $this->json(['token' => $token]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setCity($data['city'] ?? null);
        $user->setFftRanking(!empty($data['fftRanking']) ? $data['fftRanking'] : null);
        $user->setGender($data['gender'] ?? null);

        if (!empty($data['password'])) {
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], 422);
        }

        $em->persist($user);
        $em->flush();

        return $this->json($normalizer->normalize($user, null, ['groups' => ['user:read']]), 201);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(NormalizerInterface $normalizer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->json(
            $normalizer->normalize($user, null, ['groups' => ['user:read', 'user:private']])
        );
    }

    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepo,
        PasswordResetTokenRepository $tokenRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!$email) {
            return $this->json(['error' => 'Email requis.'], 400);
        }

        // Always return the same message to avoid email enumeration
        $user = $userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
        }

        // Clean up old tokens for this user
        $old = $tokenRepo->findBy(['user' => $user]);
        foreach ($old as $t) {
            $em->remove($t);
        }

        $resetToken = new PasswordResetToken($user);
        $em->persist($resetToken);
        $em->flush();

        $frontendUrl = $_ENV['DEFAULT_URI'] ?? 'http://localhost:5173';
        $resetUrl = $frontendUrl . '/reinitialiser-mot-de-passe?token=' . $resetToken->getToken();

        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@tennis-partner.fr';
        $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'Tennis Partner';

        $htmlBody = $this->buildResetEmailHtml($user->getFirstName(), $resetUrl);

        $message = (new Email())
            ->from("$fromName <$fromEmail>")
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — Tennis Partner')
            ->html($htmlBody);

        $mailer->send($message);

        return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        PasswordResetTokenRepository $tokenRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $tokenStr = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (!$tokenStr || !$password) {
            return $this->json(['error' => 'Token et mot de passe requis.'], 400);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }

        $resetToken = $tokenRepo->findValidToken($tokenStr);

        if (!$resetToken) {
            return $this->json(['error' => 'Ce lien est invalide ou a expiré.'], 400);
        }

        $user = $resetToken->getUser();
        $user->setPassword($hasher->hashPassword($user, $password));
        $resetToken->markUsed();

        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    private function buildResetEmailHtml(string $firstName, string $resetUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Inter,system-ui,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 16px;">
    <tr><td align="center">
      <table width="100%" style="max-width:480px;background:#fff;border-radius:16px;border:1px solid #E2E8F0;overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#6366F1,#818CF8);padding:32px 40px;text-align:center;">
            <span style="font-size:24px;font-weight:800;color:#fff;letter-spacing:-0.03em;">
              🎾 Tennis Partner
            </span>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:36px 40px;">
            <p style="font-size:16px;font-weight:600;color:#0F172A;margin:0 0 12px;">
              Bonjour {$firstName} 👋
            </p>
            <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 28px;">
              Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.
              Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
            </p>
            <div style="text-align:center;margin-bottom:28px;">
              <a href="{$resetUrl}"
                 style="display:inline-block;padding:14px 32px;background:#6366F1;color:#fff;
                        text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;">
                Réinitialiser mon mot de passe
              </a>
            </div>
            <p style="font-size:12px;color:#94A3B8;line-height:1.6;margin:0;">
              Ce lien est valable <strong>1 heure</strong>. Si vous n'avez pas demandé de
              réinitialisation, ignorez cet email — votre mot de passe reste inchangé.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#F8FAFC;border-top:1px solid #F1F5F9;padding:20px 40px;text-align:center;">
            <p style="font-size:11px;color:#CBD5E1;margin:0;">
              Tennis Partner · Cet email est envoyé automatiquement, merci de ne pas y répondre.
            </p>
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
