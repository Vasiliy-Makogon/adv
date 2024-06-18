<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Captcha\Model;

use InvalidArgumentException;
use GdImage;

/**
 * Пример использования:
 * $captcha = new Captcha('./path/to/font.ttf');
 * $_SESSION['code'] = $captcha->getCode();
 * $captcha->create();
 * $captcha->getImage();
 */
class Captcha
{
    /** @var GdImage */
    protected GdImage $gdImage;

    /** @var int */
    protected const IMAGE_WIDTH = 121;

    /** @var int */
    protected const IMAGE_HEIGHT = 51;

    /** @var string|null Числовой код капчи */
    private ?string $code = null;

    /**
     * @param string $ttfPath Путь до файла шрифта ttf
     */
    public function __construct(protected string $ttfPath)
    {
        if (!file_exists($ttfPath)) {
            throw new InvalidArgumentException(sprintf(
                'Не найден файл шрифта по адресу `%s`', $ttfPath
            ));
        }
    }

    /**
     * Возвращает числовой код капчи (для дальнейшей передачи в сессию).
     *
     * @return string
     */
    public function getCode(): string
    {
        if (is_null($this->code)) {
            $this->code = (string) rand(1000, 9999);
        }

        return $this->code;
    }

    /**
     * Создает изображение капчи.
     */
    public function create(): void
    {
        $this->gdImage = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

        $fill = imagecolorallocate($this->gdImage, 255, 255, 255);
        imagefill($this->gdImage, 0, 0, $fill);

        // цвет линеек
        $lineColor = imagecolorallocate($this->gdImage, 192, 192, 192);

        // рисуем вертикальные линии
        for ($i = 0; $i <= self::IMAGE_WIDTH; $i += 5) {
            imageline($this->gdImage, $i, 0, $i, self::IMAGE_HEIGHT, $lineColor);
        }

        // рисуем горизонтальные линии
        for ($i = 0; $i <= self::IMAGE_HEIGHT; $i += 5) {
            imageline($this->gdImage, 0, $i, self::IMAGE_WIDTH, $i, $lineColor);
        }

        imagettftext($this->gdImage, rand(25, 35), rand(-7, 7), 10 + rand(-5, 5), self::IMAGE_HEIGHT - 10 + rand(-5, 5),
            $this->getRandColor($this->gdImage), $this->ttfPath, substr($this->code, 0, 1));

        imagettftext($this->gdImage, rand(25, 35), rand(-7, 7), 30 + rand(-5, 5), self::IMAGE_HEIGHT - 10 + rand(-5, 10),
            $this->getRandColor($this->gdImage), $this->ttfPath, substr($this->code, 1, 1));

        imagettftext($this->gdImage, rand(25, 35), rand(-7, 7), 50 + rand(-5, 5), self::IMAGE_HEIGHT - 10 + rand(-5, 5),
            $this->getRandColor($this->gdImage), $this->ttfPath, substr($this->code, 2, 1));

        imagettftext($this->gdImage, rand(25, 35), rand(-7, 7), 70 + rand(-5, 5), self::IMAGE_HEIGHT - 10 + rand(-10, 5),
            $this->getRandColor($this->gdImage), $this->ttfPath, substr($this->code, 3, 1));

        imagettftext($this->gdImage, rand(25, 35), rand(-7, 7), 90 + rand(-5, 5), self::IMAGE_HEIGHT - 10 + rand(-10, 5),
            $this->getRandColor($this->gdImage), $this->ttfPath, substr($this->code, 4, 1));
    }

    /**
     * Вывод изображения капчи.
     */
    public function showCaptcha()
    {
        imagepng($this->gdImage);
    }

    /**
     * Возвращает случайный цвет для элемента капчи.
     *
     * @param GdImage $gdImage
     * @return bool|int
     */
    private function getRandColor(GdImage $gdImage): bool|int
    {
        return imagecolorallocate($gdImage, rand(0, 128), rand(0, 128), rand(0, 128));
    }
}