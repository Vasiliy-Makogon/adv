<?php

namespace Krugozor\Framework\Thumbnail;

use GdImage;
use RuntimeException;

class GifCreator extends AbstractCreator
{
    /**
     * Сохраняет gif изображение в файловой системе
     *
     * @param GdImage $thumbnail
     * @return bool
     */
    protected function storeImage(GdImage $thumbnail): bool
    {
        if (!imageGIF($thumbnail, $this->getFilePath(IMAGETYPE_GIF))) {
            throw new RuntimeException('Невозможно сохранить gif файл изображения');
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
        return imageCreateFromGIF($this->source_image);
    }
}