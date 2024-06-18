<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use InvalidArgumentException;
use Krugozor\Cover\CoverArray;
use RuntimeException;

class Registry extends CoverArray
{
    /** @var Registry|null */
    protected static ?Registry $instance = null;

    /**
     * Первый вызов данного регистра приходится с указанием ini-файла конфигурации.
     *
     * @param string|null $config_file_path путь к ini-файлу конфигурации
     * @return static
     */
    public static function getInstance(?string $config_file_path = null): static
    {
        if (static::$instance === null) {
            if ($config_file_path === null || !file_exists($config_file_path)) {
                throw new InvalidArgumentException("Configuration file not found at path `$config_file_path`");
            }

            $config = parse_ini_file($config_file_path, true);
            if (!$config) {
                throw new RuntimeException("Unable to read configuration file by path `$config_file_path`");
            }

            self::$instance = new static($config);
        }

        return static::$instance;
    }
}