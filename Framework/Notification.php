<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use InvalidArgumentException;
use Krugozor\Database\Mysql;
use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Statical\Strings;

/**
 * Класс flash-уведомлений на основе редиректа.
 * Используется так:
 *
 * $notification = new Notification($databaseInstance);
 * $notification->setMessage('Пользователь {user_name} (ID: {user_id}) сохранён');
 * $notification->addParam('user_name', 'Вася');
 * $notification->addParam('id_user', 123);
 * $notification->setRedirectUrl('/path/to/url/');
 * $notification->run();
 */
class Notification
{
    /** @var string */
    public const NOTIFICATION_PARAM_NAME = 'notif';

    /** @var string */
    public const TYPE_ALERT = 'alert';

    /** @var string */
    public const TYPE_NORMAL = 'normal';

    /** @var string */
    public const TYPE_WARNING = 'warning';

    /**
     * @var Mysql
     */
    private Mysql $db;

    /**
     * ID сообщения.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Скрытое сообщение (true) или нет (false).
     *
     * @var bool
     */
    private bool $is_hidden = false;

    /**
     * Тип сообщения. Может быть трёх любых типов:
     * self::TYPE_NORMAL  - сообщение об успешном выполнении.
     * self::TYPE_ALERT   - сообщение при ошибке пользователя или системы,
     *                      отменившее выполнение какого-либо действия.
     * self::TYPE_WARNING - сообщение об успешном выполнении, но предупреждающее о чем-либо.
     *
     * @var string
     */
    private string $type = self::TYPE_NORMAL;

    /**
     * Заголовок сообщения.
     *
     * @var string|null
     */
    private ?string $header = null;

    /**
     * Тело сообщения.
     *
     * @var string|null
     */
    private ?string $message = null;

    /**
     * Параметры для подстановки в тело сообщения в виде ассоциативного массива.
     *
     * @var array
     */
    private array $params = [];

    /**
     * URL для перенаправления (если нужно использовать перенаправление).
     *
     * @var string|null
     */
    private ?string $redirect_url = null;

    /**
     * Удалять ли уведомление после показа (true) или нет (false).
     *
     * @var bool
     */
    private bool $remove = true;

    /**
     * @param Mysql $db
     */
    public function __construct(Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * @return int
     */
    public function getLastInserId(): int
    {
        return $this->db->getLastInsertId();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getIsHidden(): bool
    {
        return $this->is_hidden;
    }

    /**
     * Редирект, без данных.
     *
     * @param bool $is_hidden
     * @return $this
     */
    public function setIsHidden(bool $is_hidden = true): self
    {
        $this->is_hidden = $is_hidden;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_ALERT, self::TYPE_NORMAL, self::TYPE_WARNING])) {
            throw new InvalidArgumentException(sprintf(
                '%s: Указан некорректный тип уведомления %s', __METHOD__, $type
            ));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getHeader(): ?string
    {
        return $this->header;
    }

    /**
     * @param string|null $header
     * @return $this
     */
    public function setHeader(?string $header): self
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setRedirectUrl(string $url): self
    {
        $this->redirect_url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return Strings::createMessageFromParams($this->message, $this->params);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addParam(string $key, mixed $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function addParams(array $params = []): self
    {
        foreach ($params as $key => $value) {
            $this->addParam($key, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Устанавливает флаг удаления уведомления.
     *
     * @param bool $value
     * @return $this
     */
    public function setRemoveNotificationFlag(bool $value = true): self
    {
        $this->remove = $value;

        return $this;
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): self
    {
        if (!$this->is_hidden) {
            $sql = '
                INSERT INTO `notifications`
                    SET
                `notification_remove` = ?i,
                `notification_hidden` = ?i,
                `notification_type` = "?s",
                `notification_header` = "?s",
                `notification_message` = "?s",
                `notification_params` = "?s",
                `notification_date` = NOW()
            ';

            $this->db->query(
                $sql,
                $this->remove,
                $this->is_hidden,
                $this->type,
                $this->header,
                $this->message,
                serialize($this->params)
            );

//            $anchor = '';
//            if (preg_match('~#\S+$~', $this->redirect_url, $matches) === 1) {
//                $this->redirect_url = str_replace($matches[0], '', $this->redirect_url);
//                $anchor = $matches[0];
//            }
//            $this->redirect_url .= str_contains($this->redirect_url, '?') ? '&' : '?';
//            $this->redirect_url .= Notification::NOTIFICATION_PARAM_NAME . '=' . $this->getLastInserId();
//            $this->redirect_url .= $anchor;
        }

        return $this;
    }

    /**
     * Получает информацию о совершившемся действии на основании
     * идентификатора $id записи в таблице сообщений.
     *
     * @param int|string $id
     * @throws MySqlException
     */
    public function findById(int|string $id): void
    {
        $res = $this->db->query('
            SELECT
                `notification_remove`,
                `notification_hidden`,
                `notification_type` as type,
                `notification_header` as header,
                `notification_message` as message,
                `notification_params` as params
            FROM `notifications`
            WHERE `id_notification` = ?i
            LIMIT 0, 1', $id
        );

        if ($data = $res->fetchAssoc()) {
            $this->id = (int) $id;
            $this->remove = (bool) $data['notification_remove'];
            $this->is_hidden = (bool) $data['notification_hidden'];
            $this->type = $data['type'];
            $this->header = $data['header'];
            $this->message = $data['message'];
            $this->params = unserialize($data['params']);

            if ($this->remove) {
                $this->db->query('DELETE FROM `notifications` WHERE `id_notification` = ?i', $this->getId());
            }
        }
    }

    /**
     * Очистка таблицы уведомлений.
     * Метод для cron.
     *
     * @return bool|Statement
     * @throws MySqlException
     */
    public function truncateTable(): Statement|bool
    {
        return $this->db->query('TRUNCATE TABLE `notifications`');
    }
}