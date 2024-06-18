<?php

declare(strict_types=1);

namespace Krugozor\Framework\View\InternationalizationReader;

use InvalidArgumentException;
use Krugozor\Cover\CoverArray;

/**
 * Читатель файлов интернационализации, хранящий ссылку на прочитанные данные (self::$dataObject)
 * и готовый в любой момент дополнить эти данные новыми текстами интернационализации при
 * помощи метода @see AbstractInternationalizationMessagesReader::loadI18n()
 */
abstract class AbstractInternationalizationMessagesReader
{
    /** @var CoverArray */
    protected CoverArray $dataObject;

    /**
     * @param CoverArray $dataObject
     */
    public function __construct(CoverArray $dataObject)
    {
        $this->dataObject = $dataObject;
    }

    /**
     * Возвращает полный путь в ОС к файлу интернационализации $fileName модуля $module.
     * Пример: на вход InternationalizationErrorMessagesReader::loadI18n было подано
     * 'common/general', метод возвратил строку
     * D:\dev\adverts\Framework\Module\Common\i18n\ru\validator\general.php
     *
     * @param string $module
     * @param string $fileName
     * @return string
     */
    abstract protected function getPathToI18nFile(string $module, string $fileName): string;

    /**
     * @param string ...$args
     * @return $this
     */
    public function loadI18n(string ...$args): self
    {
        foreach ($args as $arg) {
            list($module, $file) = array_pad(explode('/', $arg, 2), 2, null);
            if (!$module || !$file) {
                throw new InvalidArgumentException(sprintf(
                    '%s: Указан не правильный путь к файлу интернационализации: %s', __METHOD__, $arg
                ));
            }

            $path = $this->getPathToI18nFile($module, $file);

            if (!file_exists($path)) {
                throw new InvalidArgumentException(sprintf(
                    '%s: Не найден файл интернационализации по адресу %s', __METHOD__, $path
                ));
            }

            $this->dataObject->setData(
                $this->arrayMergeRecursiveDistinct(
                    $this->dataObject->getDataAsArray(),
                    (array) require ($path)
                )
            );
        }

        return $this;
    }

    /**
     * Рекурсивно объединяет любое количество массивов-параметров, заменив
     * значения со строковыми ключами значениями из последних массивов.
     * Если следующее присваиваемое значение является массивом, то он
     * автоматически обрабатывает оба аргумента как массив.
     * Числовые записи добавляются, а не заменяются, но только если они
     * уникальны.
     *
     * @param array[] ...$arrays
     * @return array
     */
    protected function arrayMergeRecursiveDistinct(array ...$arrays): array
    {
        $base = array_shift($arrays);

        if (!is_array($base)) {
            $base = empty($base) ? [] : array($base);
        }

        foreach ($arrays as $append) {
            if (!is_array($append)) {
                $append = array($append);
            }

            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) and !is_numeric($key)) {
                    $base[$key] = $value;
                }

                if (is_array($value) or isset($base[$key]) && is_array($base[$key])) {
                    $base[$key] = call_user_func($this->{__FUNCTION__}(...), $base[$key], $value);
                } else if (is_numeric($key)) {
                    if (!in_array($value, $base)) {
                        $base[] = $value;
                    }
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }
}