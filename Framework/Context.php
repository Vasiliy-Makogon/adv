<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use Krugozor\Database\Mysql;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Memcache;

/**
 * Объект-хранилище, содержащий все "звёздные" объекты системы.
 */
class Context
{
    /** @var Context|null */
    protected static ?Context $instance = null;

    /** @var Request|null */
    protected ?Request $request = null;

    /** @var Response|null */
    protected ?Response $response = null;

    /** @var Mysql|null */
    protected ?Mysql $db = null;

    /** @var Memcache|null */
    protected ?Memcache $memcache = null;

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

    /**
     * Возвращает объект запроса.
     *
     * @return Request|null
     */
    final public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Принимает объект запроса.
     *
     * @param Request $request
     * @return $this
     */
    final public function setRequest(Request $request): self
    {
        if (null === $this->request) {
            $this->request = $request;
        }

        return $this;
    }

    /**
     * Возвращает объект ответа.
     *
     * @return Response|null
     */
    final public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Принимает объект ответа.
     *
     * @param Response $response
     * @return $this
     */
    final public function setResponse(Response $response): self
    {
        if (null === $this->response) {
            $this->response = $response;
        }

        return $this;
    }

    /**
     * Возвращает объект СУБД.
     *
     * @return Mysql|null
     */
    final public function getDatabase(): ?Mysql
    {
        return $this->db;
    }

    /**
     * Принимает объект СУБД.
     *
     * @param Mysql $db
     * @return $this
     */
    final public function setDatabase(Mysql $db): self
    {
        if (null === $this->db) {
            $this->db = $db;
        }

        return $this;
    }

    /**
     * @return Memcache|null
     */
    final public function getMemcache(): ?Memcache
    {
        return $this->memcache;
    }

    /**
     * @param Memcache $memcache
     * @return $this
     */
    final public function setMemcache(Memcache $memcache): self
    {
        if (null === $this->memcache) {
            $this->memcache = $memcache;
        }

        return $this;
    }

    private function __construct() {}
}