<?php

declare(strict_types=1);

namespace Krugozor\Framework\Type;

/**
 * Тип `url адрес`.
 */
class Url implements TypeInterface
{
    /** @var string|null URL адрес */
    protected string|null $url;

    /**
     * @param string|null $url
     */
    public function __construct(?string $url)
    {
        $this->setValue($url);
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $value
     */
    public function setValue(?string $value)
    {
        $this->url = $value;
    }

    /**
     * Создает "красивый" якорь из длинного URL-адреса. Например, после обработки строки
     * <pre>http://test/admin/user/edit/?id=38&referer=http%3A%2F%2Ftest%2Fadmin%2Fuser%2F</pre>
     * будет получена строка вида <pre>http://test/admin/article/edit/?id=...%26sep%3D1</pre>
     *
     * @param int $width_prefix ширина префикса
     * @param int $width_postfix ширина постфикса
     * @param int $repeat кол-во повторений строки $symbol
     * @param string $symbol символ-заполнитель
     * @return string
     */
    public function getNiceAnchor(
        int $width_prefix = 20,
        int $width_postfix = 10,
        int $repeat = 3,
        string $symbol = '.'
    ): string {
        if (mb_strlen($this->url) > $width_prefix + $width_postfix) {
            return
                mb_substr($this->url, 0, $width_prefix) .
                str_repeat($symbol, $repeat) .
                mb_substr($this->url, -$width_postfix);
        }

        return $this->url;
    }
}