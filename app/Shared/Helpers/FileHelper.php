<?php

namespace App\Shared\Helpers;

use Buglinjo\LaravelWebp\Facades\Webp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    protected static string $defaultDisk = 'public';

    public static function store(UploadedFile $file, string $directory, ?string $disk = null): array
    {
        $disk = $disk ?? self::$defaultDisk;
        $directory = trim($directory, '/');

        $fileName = self::generateFileName($file, $file->getClientOriginalExtension());
        $path = $file->storeAs($directory, $fileName, $disk);

        return self::formatResponse($path, $fileName, $file, $disk);
    }

    public static function storeMultiple(array $files, string $directory, ?string $disk = null): array
    {
        $results = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = self::store($file, $directory, $disk);
            }
        }
        return $results;
    }

    public static function storeImageAsWebp(UploadedFile $file, string $directory, ?string $disk = null, int $quality = 80): array
    {
        $disk = $disk ?? self::$defaultDisk;
        $directory = trim($directory, '/');

        Storage::disk($disk)->makeDirectory($directory);

        $fileName = self::generateFileName($file, 'webp');
        $fullPath = $directory . '/' . $fileName;
        $absolutePath = Storage::disk($disk)->path($fullPath);

        $webp = Webp::make($file);

        if ($webp->save($absolutePath, $quality)) {
            return self::formatResponse($fullPath, $fileName, $file, $disk, true);
        }

        throw new \Exception('Failed to convert and save WebP image.');
    }

    /**
     * @throws \Exception
     */
    public static function storeMultipleAsWebp(array $files, string $directory, ?string $disk = null, int $quality = 80): array
    {
        $results = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = self::storeImageAsWebp($file, $directory, $disk, $quality);
            }
        }
        return $results;
    }

    public static function deleteMultiple(array $paths, ?string $disk = null): void
    {
        foreach ($paths as $path) {
            self::delete($path, $disk);
        }
    }

    public static function delete(?string $path, ?string $disk = null): bool
    {
        if (!$path) return false;

        $disk = $disk ?? self::$defaultDisk;

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    private static function generateFileName(UploadedFile $file, string $extension): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        return sprintf(
            '%s_%s_%s.%s',
            now()->format('YmdHis'),
            Str::slug($originalName),
            Str::random(8),
            $extension
        );
    }

    private static function formatResponse(string $path, string $fileName, UploadedFile $file, string $disk, bool $isWebp = false): array
    {
        return [
            'path'          => $path,
            'name'          => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'extension'     => $isWebp ? 'webp' : $file->getClientOriginalExtension(),
            'mime_type'     => $isWebp ? 'image/webp' : $file->getMimeType(),
            'size'          => Storage::disk($disk)->size($path),
            'disk'          => $disk,
            'url'           => $disk === 'public' ? asset('storage/' . $path) : null,
        ];
    }
}
