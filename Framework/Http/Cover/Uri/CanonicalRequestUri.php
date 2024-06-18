<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http\Cover\Uri;

/**
 * Объект-оболочка над $_SERVER['REQUEST_URI'], который представляет собой
 * канонический адрес документа без учёта параметров в QUERY STRING.
 *
 * @var RequestUri
 */
class CanonicalRequestUri extends RequestUri
{
    /**
     * @param string $uri REQUEST_URI
     */
    public function __construct(string $uri)
    {
        parent::__construct($uri);

        $this->uri = static::getCanonicalRequestUri($this->uri);
    }

    /**
     * @param string $requestUri
     * @return string
     */
    private static function getCanonicalRequestUri(string $requestUri): string
    {
        $requestUri = parse_url($requestUri, PHP_URL_PATH);

        // Дополняет URI закрывающим слэшем. Это сделано для предотвращения конфликта
        // в канонических адресах, когда поисковая система трактует адреса вида
        // /aaa/bbb
        // /aaa/bbb/
        // по разному и требует для документа использование канонического адреса.
        // URL с точкой не дополняется, т.к. подразумевается, что это запрос ресурса вида
        // /js/local/library/krugozor.js и т.п.
        if (!str_contains($requestUri, '.')) {
            $requestUri = rtrim($requestUri, '/') . '/';
        }

        return $requestUri;
    }
}