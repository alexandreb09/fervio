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
        NormalizerInterface $normalizer,
        MailerInterface $mailer
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

        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->setIsEmailVerified(false);

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

        $siteUrl   = rtrim($_ENV['DEFAULT_URI'] ?? 'https://fervio.fr', '/');
        $verifyUrl = $siteUrl . '/confirmer-email?token=' . $token;
        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@fervio.fr';
        $fromName  = $_ENV['MAILER_FROM_NAME']  ?? 'Fervio';

        try {
            $message = (new Email())
                ->from("$fromName <$fromEmail>")
                ->to($user->getEmail())
                ->subject('Confirmez votre adresse email — Fervio')
                ->html($this->buildVerificationEmailHtml($user->getFirstName(), $verifyUrl));

            $mailer->send($message);
        } catch (\Throwable) {
            // L'email n'a pas pu être envoyé mais le compte est créé
        }

        return $this->json($normalizer->normalize($user, null, ['groups' => ['user:read']]), 201);
    }

    #[Route('/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $token = $request->query->get('token', '');

        if (!$token) {
            return $this->json(['error' => 'Token manquant.'], 400);
        }

        $user = $userRepo->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Lien invalide ou déjà utilisé.'], 400);
        }

        $user->setIsEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $em->flush();

        return $this->json(['message' => 'Email confirmé avec succès.']);
    }

    private function buildVerificationEmailHtml(string $firstName, string $verifyUrl): string
    {
        return <<<HTML
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
                    <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1A0F08;">Bonjour {$firstName} 👋</p>
                    <p style="margin:0 0 24px;font-size:15px;color:#78604E;line-height:1.6;">
                      Merci de vous être inscrit sur Fervio ! Il ne reste qu'une étape : confirmer votre adresse email pour activer votre compte.
                    </p>
                    <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                      <tr>
                        <td style="background:#C25228;border-radius:10px;">
                          <a href="{$verifyUrl}" style="display:inline-block;padding:14px 28px;color:#fff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:-0.01em;">
                            Confirmer mon adresse email
                          </a>
                        </td>
                      </tr>
                    </table>
                    <p style="margin:0 0 8px;font-size:13px;color:#9A7B6A;line-height:1.5;">
                      Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
                    </p>
                    <p style="margin:0 0 24px;font-size:12px;color:#C25228;word-break:break-all;">{$verifyUrl}</p>
                    <p style="margin:0;font-size:13px;color:#9A7B6A;">
                      Si vous n'avez pas créé de compte sur Fervio, ignorez cet email.
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

        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@fervio.fr';
        $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'Fervio';

        $htmlBody = $this->buildResetEmailHtml($user->getFirstName(), $resetUrl);

        $message = (new Email())
            ->from("$fromName <$fromEmail>")
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — Fervio')
            ->html($htmlBody);

        try {
            $mailer->send($message);
        } catch (\Throwable) {
            // L'email n'a pas pu être envoyé mais la réponse reste identique
        }

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
                    <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1A0F08;">Bonjour {$firstName} 👋</p>
                    <p style="margin:0 0 24px;font-size:15px;color:#78604E;line-height:1.6;">
                      Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.
                      Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
                    </p>
                    <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                      <tr>
                        <td style="background:#C25228;border-radius:10px;">
                          <a href="{$resetUrl}" style="display:inline-block;padding:14px 28px;color:#fff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:-0.01em;">
                            Réinitialiser mon mot de passe
                          </a>
                        </td>
                      </tr>
                    </table>
                    <p style="margin:0 0 8px;font-size:13px;color:#9A7B6A;line-height:1.5;">
                      Ce lien est valable <strong>1 heure</strong>. Si vous n'avez pas demandé de réinitialisation, ignorez cet email — votre mot de passe reste inchangé.
                    </p>
                    <p style="margin:0 0 8px;font-size:13px;color:#9A7B6A;line-height:1.5;">
                      Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
                    </p>
                    <p style="margin:0;font-size:12px;color:#C25228;word-break:break-all;">{$resetUrl}</p>
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
    }
}
