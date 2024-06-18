<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http;

use DateTime;
use InvalidArgumentException;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Http\Cover\Data\AbstractGPCRData;
use Krugozor\Framework\Http\Cover\Data\CookieData;
use Krugozor\Framework\Http\Cover\Data\GetData;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Cover\Data\RequestData;
use Krugozor\Framework\Http\Cover\Uri\CanonicalRequestUri;
use Krugozor\Framework\Http\Cover\Uri\PartEntity;
use Krugozor\Framework\Http\Cover\Uri\RequestUri;
use Krugozor\Framework\Statical\Numeric;
use Krugozor\Framework\Statical\Strings;

class Request
{
    /** @var string */
    const SANITIZE_STRING = 'string';

    /** @var string */
    const SANITIZE_STRING_FULLTEXT = 'fulltext';

    /** @var string */
    const SANITIZE_BOOL = 'bool';

    /** @var string */
    const SANITIZE_ARRAY = 'array';

    /** @var string */
    const SANITIZE_STRING_TO_ARRAY = 'string_to_array';

    /** @var string */
    const SANITIZE_INT = 'int';

    /** @var Request|null */
    private static ?Request $instance = null;

    /** @var RequestData */
    private RequestData $requestData;

    /** @var GetData */
    private GetData $getData;

    /** @var PostData */
    private PostData $postData;

    /** @var CookieData */
    private CookieData $cookieData;

    /** @var CanonicalRequestUri */
    private CanonicalRequestUri $canonicalRequestUri;

    /**
     * Объект-оболочка над запрошенным $_SERVER['REQUEST_URI'].
     *
     * @var RequestUri
     */
    private RequestUri $requestUri;

    /**
     * Объект-обертка над именем модуля.
     *
     * @var PartEntity|null
     */
    private ?PartEntity $moduleName = null;

    /**
     * Объект-обертка над именем контроллера.
     *
     * @var PartEntity|null
     */
    private ?PartEntity $controllerName = null;

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct()
    {
        $this->requestData = new RequestData($_REQUEST);
        $this->postData = new PostData($_POST);
        $this->getData = new GetData($_GET);
        $this->cookieData = new CookieData($_COOKIE);

        $this->canonicalRequestUri = new CanonicalRequestUri($_SERVER['REQUEST_URI']);
        $this->requestUri = new RequestUri($_SERVER['REQUEST_URI']);
    }

    /**
     * Данный метод является профилактикой ошибки присваивания значения
     * объекту напрямую, минуя вызов функций получения ссылок на
     * соответствующие объекты хранилищ GPCR.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value)
    {
        throw new InvalidArgumentException(
            'Попытка присвоить значение в Request минуя вызовы GPCR-объектов'
        );
    }

    /**
     * Получает ссылку на хранилище GET или значение по ключу $key.
     *
     * @param null|string $key ключ возвращаемого значения
     * @param null|string $type приведение к типу
     * @return mixed
     * @see AbstractGPCRData::get
     */
    public function getGet(?string $key = null, ?string $type = null): mixed
    {
        if ($key !== null) {
            return $type === null
                ? $this->getData->get($key)
                : self::sanitizeValue($this->getData->get($key), $type);
        }

        return $this->getData;
    }

    /**
     * Получает ссылку на хранилище POST или значение по ключу $key.
     *
     * @param null|string $key ключ возвращаемого значения
     * @param null|string $type приведение к типу
     * @return mixed
     * @see AbstractGPCRData::get
     */
    public function getPost(?string $key = null, ?string $type = null): mixed
    {
        if ($key !== null) {
            return $type === null
                ? $this->postData->get($key)
                : self::sanitizeValue($this->postData->get($key), $type);
        }

        return $this->postData;
    }

    /**
     * Получает ссылку на хранилище COOKIE или значение по ключу $key.
     *
     * @param null|string $key ключ возвращаемого значения
     * @param null|string $type приведение к типу
     * @return mixed
     * @see AbstractGPCRData::get
     */
    public function getCookie(?string $key = null, ?string $type = null): mixed
    {
        if ($key !== null) {
            return $type === null
                ? $this->cookieData->get($key)
                : self::sanitizeValue($this->cookieData->get($key), $type);
        }

        return $this->cookieData;
    }

    /**
     * Получает ссылку на хранилище REQUEST или значение по ключу $key.
     *
     * @param null|string $key ключ возвращаемого значения
     * @param null|string $type приведение к типу
     * @return mixed
     * @see AbstractGPCRData::get
     */
    public function getRequest(?string $key = null, ?string $type = null): mixed
    {
        if ($key !== null) {
            return $type === null
                ? $this->requestData->get($key)
                : self::sanitizeValue($this->requestData->get($key), $type);
        }

        return $this->requestData;
    }

    /**
     * Возвращает true, если текущий запрос POST, false в противном случае.
     *
     * @return bool
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Возвращает true, если текущий запрос GET, false в противном случае.
     *
     * @return bool
     */
    public static function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Возвращает true, если дата (обычно, документа) $data является устаревшей
     * по отношению к HTTP заголовку If-Modified-Since.
     *
     * @param $date DateTime
     * @return bool
     */
    public static function IfModifiedSince(DateTime $date): bool
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $if_modified_since = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));

            if ($if_modified_since && $if_modified_since >= $date->getTimestamp()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return PartEntity|null
     */
    public function getModuleName(): ?PartEntity
    {
        return $this->moduleName;
    }

    /**
     * @param PartEntity $name URL модуля в оболочке PartEntity
     * @return $this
     */
    public function setModuleName(PartEntity $name): self
    {
        if ($this->moduleName === null) {
            $this->moduleName = $name;
        }

        return $this;
    }

    /**
     * @return PartEntity|null
     */
    public function getControllerName(): ?PartEntity
    {
        return $this->controllerName;
    }

    /**
     * @param PartEntity $name URL контроллера в оболочке PartEntity
     * @return $this
     */
    public function setControllerName(PartEntity $name): self
    {
        if ($this->controllerName === null) {
            $this->controllerName = $name;
        }

        return $this;
    }

    /**
     * @return CanonicalRequestUri
     */
    public function getCanonicalRequestUri(): CanonicalRequestUri
    {
        return $this->canonicalRequestUri;
    }

    /**
     * @return RequestUri
     */
    public function getRequestUri(): RequestUri
    {
        return $this->requestUri;
    }

    /**
     * Возвращает "виртуальный" путь для текущего контроллера.
     * Если данный метод вызовет контроллер Krugozor\Framework\Module\User\Controller\BackendEdit,
     * то метод вернет строку "User/BackendEdit".
     * Данный метод применяется для облегчения написания виртуальных путей к файлам интернационализации и шаблонам.
     *
     * @return string
     */
    public function getVirtualControllerPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getModuleName()->getCamelCaseStyle(),
            $this->getControllerName()->getCamelCaseStyle()
        );
    }

    /**
     * Приведение к типу $type значение $value.
     *
     * @param mixed $value значение
     * @param string $type санитарный тип, к которому будет приведено значение
     * @return string|bool|null|AbstractGPCRData|array
     */
    private static function sanitizeValue(mixed $value, string $type): string|bool|null|AbstractGPCRData|array
    {
        return match ($type) {
            self::SANITIZE_INT => match (true) {
                !is_scalar($value) => null,
                default => Numeric::detectAndExtractDecimal($value, true)
            },
            self::SANITIZE_STRING => match (true) {
                !is_scalar($value) => null,
                default => Strings::string2Utf($value)
            },
            self::SANITIZE_STRING_TO_ARRAY => match (true) {
                !is_string($value) => null,
                default => (new CoverArray(explode(PHP_EOL, trim($value))))
                    ->map('trim')
                    ->map([Strings::class, 'string2Utf'])
                    ->filter()
                    ->getDataAsArray()
            },
            self::SANITIZE_STRING_FULLTEXT => match (true) {
                !is_scalar($value) => null,
                default => Strings::prepareBeforeFulltext(Strings::string2Utf($value))
            },
            self::SANITIZE_BOOL => (bool) $value,
            self::SANITIZE_ARRAY => match (true) {
                is_array($value) => $value,
                $value instanceof AbstractGPCRData => $value->getDataAsArray(),
                is_null($value) => [],
                default => (array) $value
            },
            PostData::class => self::sanitizeToAnyRequestData($value, PostData::class),
            GetData::class => self::sanitizeToAnyRequestData($value, GetData::class),
            CookieData::class => self::sanitizeToAnyRequestData($value, CookieData::class),
            RequestData::class => self::sanitizeToAnyRequestData($value, RequestData::class),
            default => throw new InvalidArgumentException(sprintf(
                '%s: Недопустимый санитарный тип `%s`', __METHOD__, $type
            )),
        };
    }

    /**
     * Приводит значение к одному из типов, наследуемых от @see AbstractGPCRData.
     *
     * @param mixed $value
     * @param string $type
     * @return AbstractGPCRData
     */
    protected static function sanitizeToAnyRequestData(mixed $value, string $type): AbstractGPCRData
    {
        if ($value instanceof $type) {
            return $value;
        }

        /** @var AbstractGPCRData $object */
        $object = new $type;

        return match (true) {
            $value instanceof AbstractGPCRData => $object->setData($value->getDataAsArray()),
            is_iterable($value) => $object->setData($value),
            is_null($value) => $object,
            default => $object->setData([$value])
        };
    }
}