<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\MailQueue;

use InvalidArgumentException;
use RuntimeException;

/**
 * Класс-обёртка для работы с mail()
 * Используется следующим образом:
 *
 * $mail = new Mail();
 * $mail->setTo('info@host.com');
 * $mail->setFrom('robot@server.com');
 * $mail->setReplyTo('robot@server.com');
 * $mail->setHeader('Заголовок письма');
 *
 * // шаблон письма в стиле PHP-pure
 * $mail->setTemplate('/path/to/template.mail');
 *
 * // данные для шаблона любого PHP-типа
 * $mail->user = $user;
 * $mail->password = $password;
 *
 * $this->mail->send();
 */
class Mail
{
    /** @var array Данные шаблона */
    private array $data = [];

    /** @var string Тип письма */
    private string $type = 'text';

    /** @var array|string[] mime-типы */
    private static array $types = [
        'text' => 'text/plain',
        'html' => 'text/html',
    ];

    /** @var string Заголовок письма */
    private string $header;

    /** @var string Email-адрес адресата */
    private string $to;

    /** @var string Путь до файла почтового шаблона */
    private string $tpl_file;

    /** @var string Язык письма по-умолчанию */
    private string $lang = 'ru';

    /** @var array Дополнительные HTTP-заголовки письма */
    private array $additional_headers = [];

    /**
     * Устанавливает данные $value под ключом $key во
     * внутреннее представление для подстановки в шаблон письма.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Возвращает данные из внутреннего представления под ключом $key.
     *
     * @param string $key
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Устанавливает тип письма: text или html
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        if (!isset(self::$types[$type])) {
            throw new InvalidArgumentException(sprintf(
                '%s: Unknown letter type specified', __METHOD__
            ));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Устанавливает Email-адрес адресата.
     *
     * @param string $to
     * @return $this
     */
    public function setTo(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Устанавливает язык письма.
     *
     * @param string $lang
     * @return $this
     */
    public function setLang(string $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Устанавливает Email-адрес отправителя.
     *
     * @param string $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setFrom(string $value): static
    {
        if ($value) {
            $this->additional_headers['From'] = $value;
        }

        return $this;
    }

    /**
     * Устанавливает Email-адрес для ответа.
     *
     * @param string|null $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setReplyTo(?string $value): static
    {
        if ($value) {
            $this->additional_headers['Reply-To'] = $value;
        }

        return $this;
    }

    /**
     * Устанавливает копию Email-адреса адресата.
     *
     * @param string|null $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setCc(?string $value): static
    {
        if ($value) {
            $this->additional_headers['Cc'] = $value;
        }

        return $this;
    }

    /**
     * Принимает путь к файлу шаблона.
     *
     * @param string $tpl_file
     * @return $this
     */
    public function setTemplate(string $tpl_file): static
    {
        if ($tpl_file && !file_exists($tpl_file)) {
            throw new RuntimeException(sprintf(
                '%s: No mail template found at %s', __METHOD__, $tpl_file
            ));
        }

        $this->tpl_file = $tpl_file;

        return $this;
    }

    /**
     * Устанавливает заголовок письма.
     *
     * @param string $header
     * @return $this
     */
    public function setHeader(string $header): static
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Отправляет письмо.
     *
     * @return bool
     * @throws RuntimeException
     */
    public function send(): bool
    {
        if (!$this->to || !$this->header) {
            throw new RuntimeException(sprintf(
                "%s: Empty email destination ('to' property) or email header ('header' property)",
                __METHOD__
            ));
        }

        $headers = [
            'Content-type' => self::$types[$this->type] . '; charset=utf-8',
            'Content-language' => $this->lang,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Date' => date("r"),
            'MIME-Version' => '1.0',
            'Content-Transfer-Encoding' => 'BASE64',
        ];

        $headers = array_merge($headers, $this->additional_headers);

        if (empty($headers['Reply-To']) && !empty($headers['From'])) {
            $headers['Reply-To'] = $headers['From'];
        }

        return mb_send_mail(
            $this->to,
            $this->header,
            $this->getMessage(),
            $headers
        );
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        ob_start();
        include($this->tpl_file);
        $message = ob_get_contents();
        ob_end_clean();

        return $message;
    }
}