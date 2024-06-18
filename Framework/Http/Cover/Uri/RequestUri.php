<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http\Cover\Uri;

use Krugozor\Framework\Notification;

/**
 * Объект-оболочка над $_SERVER['REQUEST_URI'] с QUERY_STRING
 */
class RequestUri
{
    /** @var string REQUEST URI */
    protected string $uri;

    /**
     * @param string $uri REQUEST_URI
     */
    public function __construct(string $uri)
    {
        $this->uri = static::stripNotifQS($uri);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->getHttpScheme() . $_SERVER['HTTP_HOST'];
    }

    /**
     * Возвращает оригинальную строку REQUEST_URI.
     * Пример:
     *    /advert/frontend-user-adverts-list/?foo=bar&var=true
     *    http://adverts:8080/advert/frontend-user-adverts-list/?foo=bar&var=true
     *
     * @param bool $full_path true - возвращать полный путь (со схемой), false - относительный.
     * @return string
     */
    public function getSimpleUriValue(bool $full_path = false): string
    {
        return $full_path
            ? $this->getHttpScheme() . $_SERVER['HTTP_HOST'] . $this->uri
            : $this->uri;
    }

    /**
     * Возвращает urlencode строку REQUEST_URI адреса.
     * Эта функция удобна, когда закодированная строка будет использоваться в запросе,
     * как часть URL, в качестве удобного способа передачи переменных на следующую страницу.
     * Пример:
     *    %2Fadvert%2Ffrontend-user-adverts-list%2F%3Ffoo%3Dbar%26var%3Dtrue
     *    http%3A%2F%2Fadverts%3A8080%2Fadvert%2Ffrontend-user-adverts-list%2F%3Ffoo%3Dbar%26var%3Dtrue
     *
     * @param bool $full_path true - возвращать полный путь (со схемой), false - относительный.
     * @return string
     */
    public function getUrlencodeUriValue(bool $full_path = false): string
    {
        return urlencode($this->getSimpleUriValue($full_path));
    }

    /**
     * Возвращает htmlspecialchars строку REQUEST_URI адреса для вывода в HTML.
     * Пример:
     *    /advert/frontend-user-adverts-list/?foo=bar&amp;var=true
     *    http://adverts:8080/advert/frontend-user-adverts-list/?foo=bar&amp;var=true
     *
     * @param bool $full_path true - возвращать полный путь (со схемой), false - относительный.
     * @return string
     */
    public function getEscapeUriValue(bool $full_path = false): string
    {
        return htmlspecialchars($this->getSimpleUriValue($full_path), ENT_QUOTES);
    }

    /**
     * Функция вырезает из строки URL параметр системного уведомления &notif=
     *
     * @param string
     * @return string
     */
    protected static function stripNotifQS(string $in): string
    {
        return preg_replace(
            '/(&|%26|\?|%3F)' . Notification::NOTIFICATION_PARAM_NAME . '(=|%3D)[0-9]+/','', $in
        );
    }

    /**
     * Получение текущей схемы запроса.
     *
     * @return string
     */
    protected function getHttpScheme(): string
    {
        return
            $_SERVER['HTTP_SCHEME'] ?? (
            ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT'])
                ? 'https://'
                : 'http://'
            );
    }
}