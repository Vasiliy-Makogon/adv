<?php

namespace Krugozor\Framework\Thumbnail;

use GdImage;
use UnexpectedValueException;

abstract class AbstractCreator
{
    /**
     * Минимально-допустимая ширина и высота изображения для указания в клиентском коде.
     *
     * @var int
     */
    const MIN_WIDTH = 50;
    const MIN_HEIGHT = 50;

    /**
     * Максимально-допустимая ширина и высота изображения для указания в клиентском коде.
     *
     * @var int
     */
    const MAX_WIDTH = 1000;
    const MAX_HEIGHT = 1000;

    /**
     * Путь к файлу-источнику.
     *
     * @var string
     */
    protected string $source_image;

    /**
     * Путь к файлу назначения.
     *
     * @var string
     */
    protected string $destination_image;

    /**
     * Ширина файла-источника
     *
     * @var int
     */
    protected $actual_width;

    /**
     * Высота файла-источника
     *
     * @var int
     */
    protected $actual_height;

    /**
     * Соотношение сторон.
     *
     * @var int
     */
    protected $ratio;

    /**
     * Ширина файла-назначения.
     *
     * @var int
     */
    protected $resized_width;

    /**
     * Высота файла-назначения.
     *
     * @var int
     */
    protected $resized_height;

    /**
     * Имя сохраненного файла, с расширением.
     *
     * @var string
     */
    protected $file_name_with_ext;

    /**
     * @param string $source_image файл-источник
     * @param string $destination_image файл-назначения БЕЗ расширения
     */
    public function __construct($source_image, $destination_image)
    {
        $this->source_image = $source_image;
        $this->destination_image = $destination_image;

        list($this->actual_width, $this->actual_height) = getImageSize($this->source_image);

        $this->ratio = $this->actual_width / $this->actual_height;
    }

    /**
     * Устанавливает размер ширины изображения, до которого необходимо преобразовать изображение
     *
     * @param int $width
     * @return static
     */
    public function setResizedWidth(int $width): static
    {
        if ($width < self::MIN_WIDTH || $width > self::MAX_WIDTH) {
            throw new UnexpectedValueException(
                "Установленное значение ширины изображения должно быть в диапазоне от " . self::MIN_WIDTH . " до " . self::MAX_WIDTH
            );
        }

        $this->resized_width = $width;

        return $this;
    }

    /**
     * Устанавливает размер высоты изображения, до которого необходимо преобразовать изображение
     *
     * @param int $height
     * @return static
     */
    public function setResizedHeight(int $height): static
    {
        if ($height < self::MIN_HEIGHT || $height > self::MAX_HEIGHT) {
            throw new UnexpectedValueException(
                "Установленное значение высоты изображения должно быть в диапазоне от " . self::MIN_HEIGHT . " до " . self::MAX_HEIGHT
            );
        }

        $this->resized_height = $height;

        return $this;
    }

    /**
     * Получает значение размера ширины изображения, до которого необходимо преобразовать изображение
     *
     * @return int

    public function getresized_width()
     * {
     * return $this->resized_width;
     * }*/

    /**
     * Получает значение размера высоты изображения, до которого необходимо преобразовать изображение
     *
     * @return int

    public function getresized_height()
     * {
     * return $this->resized_height;
     * }*/

    /**
     * Преобразовывает изображение пропорционально до размеров с заданной шириной.
     *
     * @return bool
     */
    public function resizeByWidth()
    {
        $this->validateactual_width();

        $this->resized_height = floor($this->resized_width / $this->ratio);

        return $this->executeResize();
    }

    /**
     * Преобразовывает изображение пропорционально до указанных размеров с заданной шириной и высотой.
     *
     * @param string|null $text
     * @return bool
     */
    public function resize(string $text = null): bool
    {
        $this->validateactual_width();
        $this->validateactual_height();

        // Если размер изображения меньше, чем указанные размеры к преобразованию, то сохраняем изображение
        // с оригинальным размером.
        if ($this->actual_height < $this->resized_height && $this->actual_width < $this->resized_width) {
            $this->resized_height = $this->actual_height;
            $this->resized_width = $this->actual_width;
        }

        if ($this->resized_width / $this->resized_height > $this->ratio) {
            $this->resized_width = floor($this->resized_height * $this->ratio);
        } else {
            $this->resized_height = floor($this->resized_width / $this->ratio);
        }

        return $this->executeResize($text);
    }

    /**
     * Создает изображение фиксированного размера и сохраняет его в файловую систему.
     *
     * @return bool
     */
    public function resizeFixed(): bool
    {
        $this->validateactual_width();
        $this->validateactual_height();

        $kWidth = ($this->actual_width / $this->resized_width);
        $kHeight = ($this->actual_height / $this->resized_height);

        $k = min($kHeight, $kWidth);

        $tmpWidth = round($this->actual_width / $k);
        $tmpHeight = round($this->actual_height / $k);

        $dstWidth = $tmpWidth - ($tmpWidth - $this->resized_width);
        $dstHeight = $tmpHeight - ($tmpHeight - $this->resized_height);

        $wOffset = $tmpWidth > $this->resized_width ? ($tmpWidth - $this->resized_width) / 2 : 0;
        $hOffset = $tmpHeight > $this->resized_height ? ($tmpHeight - $this->resized_height) / 2 : 0;

        $wOffset = round($wOffset);
        $hOffset = round($hOffset);

        $tmp = imagecreatetruecolor($tmpWidth, $tmpHeight);

        $thumbnailResource = imagecreatetruecolor($dstWidth, $dstHeight);

        imagecopyresampled(
            $tmp,
            $this->getSourceImage(),
            0, 0, 0, 0,
            $tmpWidth, $tmpHeight,
            $this->actual_width, $this->actual_height
        );

        imagecopy(
            $thumbnailResource,
            $tmp,
            0, 0,
            $wOffset, $hOffset,
            $dstWidth, $dstHeight
        );

        return $this->storeImage($thumbnailResource);
    }

    /**
     * Возвращает имя сохраненного файла с расширением.
     */
    public function getFileNameWithExt()
    {
        return $this->file_name_with_ext;
    }

    /**
     * Получает указатель на изображение.
     *
     * @return resource
     */
    abstract protected function getSourceImage();

    /**
     * Сохраняет изображение в файловой системе.
     *
     * @param GdImage $thumbnail
     * @return bool
     */
    abstract protected function storeImage(GdImage $thumbnail): bool;

    /**
     * Проверяет, является ли новая ширина изображения меньше актуальной.
     *
     * @throws UnexpectedValueException
     */
    private function validateactual_width()
    {
        if ($this->actual_width < $this->resized_width) {
//            throw new \UnexpectedValueException(
//                "Ширина вашего изображения меньше " . $this->resized_width . " px."
//            );
        }
    }

    /**
     * Проверяет, является ли новая высота изображения меньше актуальной
     *
     * @throws UnexpectedValueException
     */
    private function validateactual_height()
    {
        if ($this->actual_height < $this->resized_height) {
//            throw new \UnexpectedValueException(
//                "Высота вашего изображения меньше " . $this->resized_height . " px."
//            );
        }
    }

    /**
     * Выполняет непосредственное преобразование изображения
     * и сохраняет его в файловую систему.
     *
     * @param string|null $text
     * @return bool
     */
    private function executeResize(?string $text = null): bool
    {
        $thumbnailResource = imageCreateTrueColor($this->resized_width, $this->resized_height);

        imageCopyResampled(
            $thumbnailResource,
            $this->getSourceImage(),
            0, 0, 0, 0,
            $this->resized_width,
            $this->resized_height,
            $this->actual_width,
            $this->actual_height
        );

        if ($text) {
            $textcolor = imagecolorallocate($thumbnailResource, 255, 255, 255);
            imagestring($thumbnailResource, 5, 5, $this->resized_height - 20, $text, $textcolor);
        }

        return $this->storeImage($thumbnailResource);
    }

    /**
     * Возвращает полный путь к файлу изображения назначения,
     * включая стандартное расширение.
     *
     * @param int тип изображения, один из стандартных цифровых форматов
     *            (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
     * @return string
     */
    protected function getFilePath($type)
    {
        $image_path = $this->destination_image . image_type_to_extension($type, true);

        $this->file_name_with_ext = basename($image_path);

        return $image_path;
    }
}