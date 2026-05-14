<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostQrController extends AbstractController
{
    #[Route('/consultation/{id}/qr', name: 'app_post_qr', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function qr(int $id, PostRepository $posts): Response
    {
        $post = $posts->find($id);
        if (!$post || $post->isHidden()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $url = $this->generateUrl('app_post_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        $builder = new Builder(
            writer: new SvgWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return new Response(
            $result->getString(),
            200,
            ['Content-Type' => $result->getMimeType()]
        );
    }
}
