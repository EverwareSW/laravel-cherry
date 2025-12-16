<?php

namespace Everware\LaravelCherry\Helpers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\MimeTypes;

class FileHelper
{
    /**
     * Convert content to base 64 readable data.
     * Based on https://stackoverflow.com/a/18739753/3017716
     *
     * Also {@see StringHelper::base64UrlEncode()}.
     */
    public static function dataToBase64(string $mime, string $content): string
    {
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    /**
     * Convert a File to a base 64 encoded string.
     *
     * Also {@see StringHelper::base64UrlEncode()}.
     */
    public static function fileToBase64(File $file): string
    {
        return 'data:' . $file->getMimeType() . ';base64,' . base64_encode($file->getContent());
    }

    /**
     * Based on https://github.com/crazybooot/base64-validation/blob/master/src/Validators/Base64Validator.php#L168
     * Can be used with @see fileToUploadedFile()
     *
     * Also {@see StringHelper::base64UrlEncode()} and {@see StringHelper::base64UrlDecode()}.
     */
    public static function base64ToFile(string $base64): File
    {
        if (strpos($base64, ';base64') !== false) {
            [, $base64] = explode(';', $base64);
            [, $base64] = explode(',', $base64);
        }

        $binaryData = base64_decode($base64);
        $tmpFile = tempnam(sys_get_temp_dir(), 'base64decoded');
        file_put_contents($tmpFile, $binaryData);

        $file = new File($tmpFile);
        return $file;
    }

    /**
     * Based on @see UploadedFile::createFromBase()
     * Also {@see FinfoMimeTypeDetector::detectMimeType()}.
     * and https://stackoverflow.com/a/58512459/3017716
     */
    public static function fileToUploadedFile(File $file): UploadedFile
    {
        $uFile = new UploadedFile(
            $file->getPathname(),
            $file->getFilename(),
            $file->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        return $uFile;
    }

    /**
     * @param resource|string $content
     */
    public static function determineMimeType($content): string
    {
        if (is_resource($content)) {
            $resource = $content;
            // We have to read the actual content because finfo_buffer() can't analyze custom stream wrappers like 'guzzle://stream'.
            $content = stream_get_contents($resource);
            rewind($resource);
        }

        /** Based on @see Filesystem::guessExtension() and https://stackoverflow.com/a/6061602/3017716 */
        return finfo_buffer(
            finfo_open(FILEINFO_MIME_TYPE),
            $content,
            FILEINFO_MIME_TYPE,
            $resource ?? null,
        );
    }

    /**
     * @param string|resource $resource
     */
    public static function determineExtension($resource, string $fallback = 'jpg'): string
    {
        $mimeType = static::determineMimeType($resource);
        /** Based on @see Filesystem::guessExtension() and https://stackoverflow.com/a/6061602/3017716 */
        return MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? $fallback;
    }

    /**
     * @param int $maxSize In bytes
     */
    public static function filterFilesBySize(int $maxSize, Filesystem $disk): array
    {
        $files = $disk->files();

        return array_filter($files, fn($file) => $disk->size($file) <= $maxSize);
    }

    /**
     * Generates a PNG image and stores it on a disk.
     * Note the PHP package ext-gd needs to be installed.
     *
     * @return string The stored file name (without the path)
     */
    public static function generateImage(
        string $disk,
        string $directory,
        int $width = 256,
        int $height = 256,
        ?string $fileName = null
    ) : string
    {
        $disk = \Storage::disk($disk);
        // When files are stored through \Storage::disks(), this happens automatically.
        $disk->makeDirectory($directory);
        // Using \Storage::path so when \Storage mocked returns testing directory.
        $fullDir = $disk->path($directory);

        /**
         * We are not using Fakers @see Image::image() because that uses an online service to
         * generate and download the images, e.g. https://via.placeholder.com/100x100.png/CCCCCC?text=100x100
         * and sometimes that service is down, which obviously causes issues.
         * `$fileName = $faker->image($fullDir, width: 256, height: 256, fullPath: false, randomize: false);`
         * @see UploadedFile::hashName()
         */
        $fileName ??= \Str::random(40) . '.png';
        $backgroundRgb = [mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)];
        $font = 1;

        $image = imagecreate($width, $height);
        imagecolorallocate($image, ...$backgroundRgb);

        $textRgb = array_map(fn($c) => 255 - $c, $backgroundRgb);
        $textColor = imagecolorallocate($image, ...$textRgb);
        $text = "{$width}x{$height}";
        $x = floor($width/2 - strlen($text)*imagefontwidth($font)/2);
        $y = floor($height/2 - imagefontheight($font)/2);
        imagestring($image, $font, $x, $y, $text, $textColor);

        imagepng($image, $fullDir.DIRECTORY_SEPARATOR.$fileName);
        return $fileName;
    }
}