<?php

namespace Krugozor\Framework\Thumbnail;

use UnexpectedValueException;

class Factory
{
    /**
     * @param string $uploadedFile путь к исходному файлу
     * @param string $destinationFile путь к файлу назначения
     * @return GifCreator|JpegCreator|PngCreator
     * @throws UnexpectedValueException
     */
    public static function create(string $uploadedFile, string $destinationFile): GifCreator|PngCreator|JpegCreator
    {
        list(,,$type,) = getimagesize($uploadedFile);

        return match ($type) {
            IMAGETYPE_GIF => new GifCreator($uploadedFile, $destinationFile),
            IMAGETYPE_JPEG => new JpegCreator($uploadedFile, $destinationFile),
            IMAGETYPE_PNG => new PngCreator($uploadedFile, $destinationFile),
            default => throw new UnexpectedValueException('Передан неизвестный тип файла изображения'),
        };
    }
}