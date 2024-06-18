<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Model;

use InvalidArgumentException;
use Krugozor\Framework\Type\Date\DateTime;
use RuntimeException;

abstract class AbstractResource
{
    /** @var string Путь к файлу ресурса */
    protected string $path;

    /**
     * Возвращает информацию о ресурсе по его расширению или RuntimeException,
     * если запрошен не файл данного ресурса.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getResourceMimeType(): string
    {
        $mimeType = static::RESOURCE_INFO[$this->getResourceExtension()] ?? null;
        if (!$mimeType) {
            throw new RuntimeException(sprintf(
                '%s: Call not valid resource file by path "%s"', __METHOD__, $this->path
            ));
        }

        return $mimeType;
    }

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf(
                '%s: Call to undefined resource file by path "%s"', __METHOD__, $path
            ));
        }

        $this->path = $path;
    }

    /**
     * Возвращает время последнего изменения файла ресурса.
     *
     * @return DateTime
     */
    public function getModificationTime(): DateTime
    {
        return (new DateTime())->setTimestamp(filemtime($this->path));
    }

    /**
     * Возвращает расширение файла ресурса
     *
     * @return string|bool
     */
    public function getResourceExtension(): string|bool
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Возвращает содержимое файла ресурса.
     *
     * @return bool|string
     */
    public function getResourceContents(): bool|string
    {
        return file_get_contents($this->path);
    }
}