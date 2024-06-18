<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http;

use InvalidArgumentException;

class Response
{
    /** @var Response|null */
    private static ?Response $instance = null;

    /** @var string */
    const HEADER_LOCATION = 'Location';

    /** @var string */
    const HEADER_CONTENT_TYPE = 'Content-type';

    /** @var string */
    const HEADER_CONTENT_LANGUAGE = 'Content-Language';

    /** @var string */
    const HEADER_EXPIRES = 'Expires';

    /** @var string */
    const HEADER_LAST_MODIFIED = 'Last-Modified';

    /** @var string */
    const HEADER_CACHE_CONTROL = 'Cache-Control';

    /** @var string */
    const HEADER_PRAGMA = 'Pragma';

    /** @var string */
    const X_ROBOTS_TAG = 'X-Robots-Tag';

    /**
     * Код состояния HTTP, например: HTTP/1.1 404 Not Found
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Массив HTTP-заголовков вида `имя заголовка` => `значение`.
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Массив массивов информации о cookie.
     * Данные в массивах хранятся согласно последовательности аргументов для функци setcookie.
     *
     * @var array
     */
    private array $cookies = [];

    /**
     * Возвращает экземпляр ответа с HTTP-заголовками по-умолчанию.
     *
     * @return Response
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Устанавливает состояние ответа HTTP.
     *
     * @param int $code код состояния
     * @return Response
     */
    public function setHttpStatusCode(int $code = 200): self
    {
        $statuses = $this->getHttpStatuses();
        if (!isset($statuses[$code])) {
            throw new InvalidArgumentException('Unknown http status code: ' . $code);
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';

        $this->status = implode(' ', [$protocol, $code, $statuses[$code]]);

        return $this;
    }

    /**
     * Устанавливает HTTP-заголовок ответа.
     *
     * @param string $name имя заголовка
     * @param string $value содержание заголовка
     * @return Response
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[trim($name)] = trim($value);

        return $this;
    }

    /**
     * Разустанавливает HTTP-заголовок ответа.
     *
     * @param string $name имя заголовка
     * @return Response
     */
    public function unsetHeader(string $name): self
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Отправляет HTTP-заголовки.
     *
     * @param bool true, если очищать хранилище заголовков, false в ином случае.
     * @return Response
     */
    public function sendHeaders(bool $clear = true): self
    {
        if ($this->status) {
            header($this->status);
        }

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($clear) {
            $this->clearHeaders();
        }

        return $this;
    }

    /**
     * Устанавливает cookie во внутреннее представление класса.
     * API - аналог PHP-функции cookie.
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return $this
     */
    public function setCookie(
        string $name,
        string $value = "",
        int $expire = 0,
        string $path = "",
        string $domain = "",
        bool $secure = true,
        bool $httponly = false
    ): self {
        $this->cookies[$name] = array($value, $expire, $path, $domain, $secure, $httponly);

        return $this;
    }

    /**
     * Отправляет все установленные cookie.
     *
     * @return Response
     */
    public function sendCookie(): self
    {
        foreach ($this->cookies as $name => $data) {
            $args = array_merge([$name], $data);

            call_user_func_array('setcookie', $args);
        }

        $this->clearCookie();

        return $this;
    }

    /**
     * Очищает заголовки ответа.
     *
     * @return Response
     */
    public function clearHeaders(): self
    {
        $this->status = null;
        $this->headers = [];

        return $this;
    }

    /**
     * Очищает куки.
     *
     * @return Response
     */
    public function clearCookie(): self
    {
        $this->cookies = [];

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getHttpStatuses()
    {
        return [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing', // WebDAV; RFC 2518
            103 => 'Early Hints', // RFC 8297
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information', // since HTTP/1.1
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content', // RFC 7233
            207 => 'Multi-Status', // WebDAV; RFC 4918
            208 => 'Already Reported', // WebDAV; RFC 5842
            226 => 'IM Used', // RFC 3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found', // Previously "Moved temporarily"
            303 => 'See Other', // since HTTP/1.1
            304 => 'Not Modified', // RFC 7232
            305 => 'Use Proxy', // since HTTP/1.1
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect', // since HTTP/1.1
            308 => 'Permanent Redirect', // RFC 7538
            400 => 'Bad Request',
            401 => 'Unauthorized', // RFC 7235
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required', // RFC 7235
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed', // RFC 7232
            413 => 'Payload Too Large', // RFC 7231
            414 => 'URI Too Long', // RFC 7231
            415 => 'Unsupported Media Type', // RFC 7231
            416 => 'Range Not Satisfiable', // RFC 7233
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot', // RFC 2324, RFC 7168
            421 => 'Misdirected Request', // RFC 7540
            422 => 'Unprocessable Entity', // WebDAV; RFC 4918
            423 => 'Locked', // WebDAV; RFC 4918
            424 => 'Failed Dependency', // WebDAV; RFC 4918
            425 => 'Too Early', // RFC 8470
            426 => 'Upgrade Required',
            428 => 'Precondition Required', // RFC 6585
            429 => 'Too Many Requests', // RFC 6585
            431 => 'Request Header Fields Too Large', // RFC 6585
            451 => 'Unavailable For Legal Reasons', // RFC 7725
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates', // RFC 2295
            507 => 'Insufficient Storage', // WebDAV; RFC 4918
            508 => 'Loop Detected', // WebDAV; RFC 5842
            510 => 'Not Extended', // RFC 2774
            511 => 'Network Authentication Required', // RFC 6585
        ];
    }
}