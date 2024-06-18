<?php

namespace Krugozor\Framework\Thumbnail;

use GdImage;
use RuntimeException;

class PngCreator extends AbstractCreator
{
    /**
     * Сохраняет png изображение в файловой системе
     *
     * @param GdImage $thumbnail ресурс изображения
     * @return bool
     */
    protected function storeImage(GdImage $thumbnail): bool
    {
        if (!imagePNG($thumbnail, $this->getFilePath(IMAGETYPE_PNG))) {
            throw new RuntimeException('Невозможно сохранить png файл изображения');
        }

        return true;
    }

    /**
     * Возвращает ссылку на ресурс изображения
     *
     * @return resource
     */
    protected function getSourceImage()
    {
        return imageCreateFromPNG($this->source_image);
    }
}