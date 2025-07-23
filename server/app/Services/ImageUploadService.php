<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    protected ImageManager $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function upload(UploadedFile $file, string $folder, ?string $oldImage = null): array
    {
        if ($oldImage) {
            $this->cleanupOldImage($oldImage);
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        $originalPath = "uploads/{$folder}/originals/{$filename}";
        $thumbnailPath = "uploads/{$folder}/thumbnails/{$filename}";

        Storage::disk('public')->put($originalPath, file_get_contents($file));

        // Ottieni configurazione specifica per il folder
        $config = $this->getThumbnailConfig($folder);

        // Legge immagine
        $image = $this->imageManager->read($file);

        // Crea thumbnail
        $thumb = $this->createThumbnail($image, $config);

        // Conversione finale
        $thumbnail = match ($config['format']) {
            'png' => $thumb->toPng($config['quality']),
            'webp' => $thumb->toWebp($config['quality']),
            default => $thumb->toJpeg($config['quality']),
        };

        Storage::disk('public')->put($thumbnailPath, (string) $thumbnail);

        return [
            'image_path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
            'image_url' => asset("storage/{$originalPath}"),
            'thumbnail_url' => asset("storage/{$thumbnailPath}"),
        ];
    }

    protected function getThumbnailConfig(string $folder): array
    {
        // Configurazioni specifiche per tipo
        $specificConfig = config("image.thumbnails.{$folder}", []);

        // Configurazione di default
        $defaultConfig = [
            'width' => config('image.thumbnail.width', 300),
            'height' => config('image.thumbnail.height', 300),
            'method' => config('image.thumbnail.method', 'canvas'),
            'format' => config('image.thumbnail.format', 'jpeg'),
            'background_color' => config('image.thumbnail.background_color', '#ffffff'),
        ];

        $config = array_merge($defaultConfig, $specificConfig);

        // Aggiungi quality
        $config['quality'] = config("image.quality.{$config['format']}", 85);

        return $config;
    }

    protected function createThumbnail($image, array $config)
    {
        $width = $config['width'];
        $height = $config['height'];
        $method = $config['method'];
        $bgColor = $config['background_color'];

        return match ($method) {
            'fit' => $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }),

            'cover' => $image->cover($width, $height),

            'resize' => $image->resize($width, $height),

            'canvas' => $this->createCanvasThumbnail($image, $width, $height, $bgColor),

            'smart_canvas' => $this->createSmartCanvasThumbnail($image, $width, $height, $bgColor),

            default => $this->createCanvasThumbnail($image, $width, $height, $bgColor),
        };
    }

    protected function createCanvasThumbnail($image, int $width, int $height, string $bgColor)
    {
        // Ridimensiona mantenendo proporzioni
        $resized = $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Crea canvas con sfondo
        return $this->imageManager
            ->create($width, $height, $bgColor)
            ->place($resized, 'center');
    }

    protected function createSmartCanvasThumbnail($image, int $width, int $height, string $bgColor)
    {
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Calcola il rapporto di ridimensionamento
        $ratioW = $width / $originalWidth;
        $ratioH = $height / $originalHeight;
        $ratio = min($ratioW, $ratioH);

        // Calcola nuove dimensioni
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Ridimensiona
        $resized = $image->resize($newWidth, $newHeight);

        // Se l'immagine è molto piccola rispetto al canvas,
        // usa un ridimensionamento più generoso
        if ($newWidth < $width * 0.7 && $newHeight < $height * 0.7) {
            $ratio = min($width * 0.9 / $originalWidth, $height * 0.9 / $originalHeight);
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            $resized = $image->resize($newWidth, $newHeight);
        }

        // Crea canvas con sfondo
        return $this->imageManager
            ->create($width, $height, $bgColor)
            ->place($resized, 'center');
    }

    public function delete(string $imagePath): void
    {
        Storage::disk('public')->delete($imagePath);

        $thumbnailPath = str_replace('/originals/', '/thumbnails/', $imagePath);
        Storage::disk('public')->delete($thumbnailPath);
    }

    public function cleanupOldImage(string $oldPath): void
    {
        $this->delete($oldPath);
    }

    // Metodo helper per rigenerare thumbnails esistenti
    public function regenerateThumbnail(string $originalPath, string $folder): bool
    {
        $fullPath = Storage::disk('public')->path($originalPath);

        if (!file_exists($fullPath)) {
            return false;
        }

        $config = $this->getThumbnailConfig($folder);
        $image = $this->imageManager->read($fullPath);
        $thumb = $this->createThumbnail($image, $config);

        $thumbnail = match ($config['format']) {
            'png' => $thumb->toPng($config['quality']),
            'webp' => $thumb->toWebp($config['quality']),
            default => $thumb->toJpeg($config['quality']),
        };

        $thumbnailPath = str_replace('/originals/', '/thumbnails/', $originalPath);
        Storage::disk('public')->put($thumbnailPath, (string) $thumbnail);

        return true;
    }
}
