<?php

namespace Krugozor\Framework\Utility\Upload;

use InvalidArgumentException;
use RuntimeException;

class DirectoryGenerator
{
    /**
     * Имя файла.
     *
     * @var string
     */
    private $file_name;

    /**
     * Глубина создаваемой вложенности директорий.
     * @var int
     */
    private $depth = 3;

    /**
     * DirectoryGenerator constructor.
     *
     * @param string $file_name имя файла
     */
    public function __construct(string $file_name)
    {
        if (!strlen($file_name)) {
            throw new InvalidArgumentException(__METHOD__ . ': Указан параметр нулевой длинны');
        }

        $this->file_name = $file_name;
    }

    /**
     * Создает директории (если они ещё не созданы) на основе имени файла
     * (например, d2d8f9c20083bd8483ac5d5526f923b9.jpeg) и возвращает путь.
     *
     * @param string $destinationDir директория назначния
     * @return string путь, например: i\700x600\d\2\d\
     */
    public function create(string $destinationDir): string
    {
        $destinationDir = rtrim($destinationDir, '\/');

        for ($i = 0; $i < $this->depth; $i++) {
            $destinationDir .= DIRECTORY_SEPARATOR . $this->file_name[$i];

            if (!is_dir($destinationDir)) {
                if (!@mkdir($destinationDir, 0775)) {
                    throw new RuntimeException(sprintf(
                        '%s: Не удалось создать директорию `%s`, ошибка: %s',
                        __METHOD__,
                        $destinationDir,
                        print_r(error_get_last(), true)
                    ));
                }
            }
        }

        return $destinationDir . DIRECTORY_SEPARATOR;
    }

    /**
     * На основе имени файла (например, d2d8f9c20083bd8483ac5d5526f923b9.jpeg)
     * возвращает путь к файлу для HTTP, вида /d/2/d/8/f/.
     *
     * @todo переименовать, название не отражает сути
     * @return string HTTP-путь к файлу
     */
    public function getHttpPath(): string
    {
        $destinationDir = '';

        for ($i = 0; $i < $this->depth; $i++) {
            $destinationDir .= '/' . $this->file_name[$i];
        }

        return $destinationDir . '/';
    }
}