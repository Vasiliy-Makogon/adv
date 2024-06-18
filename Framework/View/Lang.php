<?php

declare(strict_types=1);

namespace Krugozor\Framework\View;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\View\InternationalizationReader\InternationalizationTextMessagesReader;

class Lang extends CoverArray
{
    /** @var string */
    protected const TITLE_KEY = 'title';

    /**
     * @param string ...$args
     * @return static
     */
    public function loadI18n(string ...$args): static
    {
        $i18nTextMessagesReader = new InternationalizationTextMessagesReader($this);
        $i18nTextMessagesReader->loadI18n(...$args);

        // Элемент с ключом title является подтипом LangTitle типа Lang и содержит методы,
        // необходимые для работы с title во время исполнения клиентского кода.
        if ($this->get(static::TITLE_KEY)) {
            $this->offsetSet(static::TITLE_KEY, new LangTitle(
                $this->get(static::TITLE_KEY)->getDataAsArray()
            ));
        }

        unset($i18nTextMessagesReader);

        return $this;
    }

    /**
     * Заменяет в строковом элемента под индексом $index метки на данные $data.
     *
     * @param int|string $index индекс элемента, в котором производится замена
     * @param array $data массив данных
     * @return static
     * @see Strings::createMessageFromParams
     */
    public function replaceParams(int|string $index, array $data = []): static
    {
        $this->setData([
            $index => Strings::createMessageFromParams(
                $this->item($index), $data
            )
        ]);

        return $this;
    }

    /**
     * Добавляет постфикс к строковому элементу под индексом $index
     *
     * @param int|string $index индекс элемента, к которому добавить постфикс
     * @param string $postfix постфикс
     * @return static
     */
    public function addPostfix(int|string $index, string $postfix): static
    {
        $this->setData([
            $index => $this->item($index) . $postfix
        ]);

        return $this;
    }
}