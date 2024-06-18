<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use InvalidArgumentException;
use Krugozor\Cover\CoverArray;

class Session
{
    /** @var Session|null */
    private static ?Session $instance = null;

    /** @var CoverArray $data Хранилище данных, передаваемых через магические методы __set и __get. */
    protected CoverArray $data;

    /** @var string $session_name */
    private string $session_name;

    /** @var string $session_id */
    private string $session_id;

    /**
     * @param string|null $session_name
     * @param string|null $session_id
     * @param array $options
     * @return static
     */
    public static function getInstance(
        ?string $session_name = null,
        ?string $session_id = null,
        array $options = []
    ): static {
        if (is_null(static::$instance)) {
            static::$instance = new static($session_name, $session_id, $options);
        }

        return static::$instance;
    }

    /**
     * Возвращает элемент из хранилища данных $this->data.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->data->$key;
    }

    /**
     * Добавляет новый элемент в хранилище данных $this->data.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->data->$key = $value;
    }

    /**
     * Уничтожает сессию.
     */
    public function destroy(): void
    {
        $_SESSION = [];
        $this->data->clear();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                $this->getSessionName(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * @return string|null
     */
    public function getSessionName(): ?string
    {
        return $this->session_name;
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->session_id;
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     * Стартует сессию. Устанавливает имя сесии $session_name, если оно определено
     * и стандартное имя session.name в обратном случае.
     *
     * @param null|string $session_name имя сессии
     * @param null|string $session_id идентификатор сессии
     * @param array $options
     */
    protected function __construct(
        ?string $session_name = null,
        ?string $session_id = null,
        array $options = []
    ) {
        $this->data = new CoverArray();

        $this->session_name = $session_name ?? session_name();

        session_name($this->session_name);

        $this->start($session_id, $options);
    }

    /**
     * Стартует сессию.
     *
     * @param string|null $session_id
     * @param array $options
     */
    protected function start(?string $session_id, array $options = []): void
    {
        if (!$this->isStarted()) {
            $this->setSessionId($session_id);

            session_start($options);

            $this->session_id = session_id();

            if (!empty($_SESSION)) {
                foreach ($_SESSION as $key => $value) {
                    $this->data[$key] = is_array($value) ? new CoverArray($value) : $value;
                }
            }
        }
    }

    /**
     * @param string|null $session_id
     */
    protected function setSessionId(?string $session_id): void
    {
        if (is_null($session_id)) {
            return;
        }

        if ($session_id = trim($session_id)) {
            if (preg_match('~[^a-z0-9,\-]+~i', $session_id)) {
                throw new InvalidArgumentException('Попытка присвоить некорректный ID сессии.');
            }
            session_id($session_id);
        }
    }

    /**
     * @return bool
     */
    protected function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    protected function save(): void
    {
        if ($this->data instanceof CoverArray && $this->data->count()) {
            $_SESSION = $this->data->getDataAsArray();
        }
    }
}