<?php

namespace Krugozor\Framework\Thumbnail;

use GdImage;
use RuntimeException;

class JpegCreator extends AbstractCreator
{
    /**
     * Сохраняет jpeg изображение в файловой системе
     *
     * @param GdImage $thumbnail ресурс изображения
     * @return bool
     */
    protected function storeImage(GdImage $thumbnail): bool
    {
        if (!imageJPEG($thumbnail, $this->getFilePath(IMAGETYPE_JPEG), 100)) {
            throw new RuntimeException('Невозможно сохранить jpeg файл изображения');
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
        return imageCreateFromJPEG($this->source_image);
    }
}