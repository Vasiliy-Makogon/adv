<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Common\Model;

use InvalidArgumentException;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Statical\Strings;

/**
 * Fake-модель.
 * Предназначена для ситуаций, когда в полученном результате SQL-запроса
 * присутствуют поля данных, полученные не из конкретных существующих таблиц, а в результате вычислений:
 *
 *  SELECT COUNT(*) as `advert_count` ...
 *  SELECT 1 + 1 as `number` ...
 *
 *  Эти данные транслируются в Fake модель и доступны в ней с помощью виртуальных методов,
 *  аналогичных базовым моделям:
 *
 *  $common_fake->getAdvertCount()
 *  $common_fake->getNumber()
 */
class Fake extends AbstractModel
{
    /**
     * Получение и установка свойств объекта через вызов магического метода вида:
     *
     * $model->(get|set)PropertyName($prop);
     *
     * @param string $method_name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method_name, array $arguments): mixed
    {
        $args = preg_split(Strings::$pattern_search_method_name, $method_name);
        $action = array_shift($args);
        $property_name = strtolower(implode('_', $args));

        switch ($action) {
            case 'get':
                return $this->$property_name;

            case 'set':
                $this->$property_name = $arguments[0];
                return $this;

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s: вызов метода с неопределённым action `%s`',
                    __METHOD__,
                    $action
                ));
        }
    }

    /**
     * $excluded_keys в контексте facke-модели не используется.
     *
     * @param iterable $data
     * @param array $excluded_keys
     * @return static
     */
    public function setData(iterable $data, array $excluded_keys = array()): static
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}