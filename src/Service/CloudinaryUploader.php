<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CloudinaryUploader
{
    public function __construct(
        #[Autowire('%env(CLOUDINARY_CLOUD_NAME)%')] private readonly string $cloudName,
        #[Autowire('%env(CLOUDINARY_API_KEY)%')] private readonly string $apiKey,
        #[Autowire('%env(CLOUDINARY_API_SECRET)%')] private readonly string $apiSecret,
        #[Autowire('%env(CLOUDINARY_UPLOAD_PRESET)%')] private readonly string $uploadPreset,
    ) {
    }

    public function uploadProgrammeImage(UploadedFile $file): string
    {
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->cloudName,
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'programmes',
            'upload_preset' => $this->uploadPreset,
            'resource_type' => 'image',
        ]);

        return (string) ($result['secure_url'] ?? '');
    }
}
