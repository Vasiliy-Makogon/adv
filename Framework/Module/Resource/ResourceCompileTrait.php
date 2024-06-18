<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource;

use Krugozor\Framework\Application;

trait ResourceCompileTrait
{
    /**
     * Создает имя файла компиляции ресурсов по массиву $options
     *
     * @param array $options массив опций, пример:
     * [
     *   'local' => ['reset.css', 'tags.css', 'classes.css', 'structure.css'],
     *   'help' => ['help.css'],
     * ]
     * @return string имя кэшированного файла вида
     */
    public static function createCompileResourceFileNameByOptions(array $options): string
    {
        return implode(';', array_map(function ($module, $enums) {
            return sprintf('%s=%s', $module, implode('&', $enums));
        }, array_keys($options), array_values($options)));
    }

    /**
     * Производит раскомпиляцию имени файла ресурсов.
     *
     * @param string $compileFilename
     * @return array
     */
    public static function recompileResourceFileName(string $compileFilename): array
    {
        $data = [];
        array_map(function($part) use (&$data) {
            list($module, $enums) = array_pad(explode('=', $part, 2), 2, null);
            if (!$module || !$enums) {
                return;
            }
            // Фильтр отсекает любые попытки взломать систему путём передачи любого файла с конкретным путём к файлу
            $data[$module] = array_filter(explode('&', $enums), function($item) {
               return $item && preg_match('~^[a-z0-9_\-]+\.css$~', $item) === 1;
            });
        }, explode(';', $compileFilename));

        return $data;
    }

    /**
     * @param string $module
     * @param string $file
     * @return string
     */
    public static function getRealResourceFilePath(string $module, string $file): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            Application::getAnchor($module)::getPath(),
            'resources',
            'css',
            $file
        ]);
    }
}