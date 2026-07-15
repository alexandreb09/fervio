<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;

class FervioEmail
{
    public function __construct(
        #[Autowire('%env(MAILER_FROM_EMAIL)%')] private string $fromEmail,
        #[Autowire('%env(MAILER_FROM_NAME)%')]  private string $fromName,
    ) {}

    /**
     * Builds a complete branded Email object.
     * $bodyHtml is the inner content (below the greeting, above the footer).
     */
    public function build(string $to, string $subject, string $firstName, string $bodyHtml): Email
    {
        return (new Email())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($to)
            ->subject($subject)
            ->html($this->wrap($firstName, $bodyHtml));
    }

    /** CTA button block. */
    public static function button(string $url, string $label): string
    {
        $url   = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">'
            . '<tr><td style="background:#C25228;border-radius:10px;">'
            . "<a href=\"{$url}\" style=\"display:inline-block;padding:14px 28px;color:#fff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:-0.01em;\">{$label}</a>"
            . '</td></tr></table>';
    }

    /** Quoted message block (escapes user content). */
    public static function quote(string $text): string
    {
        $escaped = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">'
            . '<tr><td style="background:#FEF0E6;border-left:3px solid #C25228;border-radius:0 8px 8px 0;padding:16px 20px;">'
            . "<p style=\"margin:0;font-size:14px;color:#3D1F0F;line-height:1.7;\">{$escaped}</p>"
            . '</td></tr></table>';
    }

    /** Fallback plain-text link for buttons that may not render. */
    public static function fallbackLink(string $url): string
    {
        $escaped = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<p style="margin:0 0 8px;font-size:13px;color:#9A7B6A;line-height:1.5;">'
            . 'Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</p>'
            . "<p style=\"margin:0 0 24px;font-size:12px;color:#C25228;word-break:break-all;\">{$escaped}</p>";
    }

    private function wrap(string $firstName, string $bodyHtml): string
    {
        $firstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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
            <p style="margin:0 0 20px;font-size:20px;font-weight:700;color:#1A0F08;">Bonjour {$firstName} 👋</p>
            {$bodyHtml}
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
