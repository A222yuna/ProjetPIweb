<?php

namespace App\UserManagement\Pdf;

use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

final class UserAccountPdfGenerator
{
    public function __construct(
        private Environment $twig,
        private ParameterBagInterface $parameters,
        private string $projectDir,
    ) {
    }

    public function buildPdfBinary(User $user): string
    {
        $publicDir = $this->projectDir.\DIRECTORY_SEPARATOR.'public';
        $logoDataUri = $this->resolveLogoDataUri($publicDir);

        $html = $this->twig->render('user_management/pdf/account_sheet.html.twig', [
            'user' => $user,
            'brand' => [
                'title' => (string) $this->parameters->get('user_management.pdf.brand_title'),
                'primary' => (string) $this->parameters->get('user_management.pdf.primary_color'),
                'accent' => (string) $this->parameters->get('user_management.pdf.accent_color'),
                'header_bg' => (string) $this->parameters->get('user_management.pdf.header_background'),
                'logo_data_uri' => $logoDataUri,
            ],
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->setChroot($publicDir);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    private function resolveLogoDataUri(string $publicDir): ?string
    {
        $rel = $this->parameters->get('user_management.pdf.logo_public_path');
        if (!\is_string($rel) || '' === $rel) {
            return null;
        }

        $normalized = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, ltrim($rel, '/\\'));
        $absolute = $publicDir.\DIRECTORY_SEPARATOR.$normalized;
        if (!is_file($absolute) || !is_readable($absolute)) {
            return null;
        }

        $mime = @mime_content_type($absolute) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }
}
