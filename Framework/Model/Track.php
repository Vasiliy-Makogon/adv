<?php

declare(strict_types=1);

namespace Krugozor\Framework\Model;

use Countable;
use Krugozor\Cover\Simple;

/**
 * Объект, содержащий данные модели при первичном наполнении и позволяющий отслеживать,
 * какие свойства были изменены, а какие - нет.
 * Данный объект наполняется в момент наполнения объекта модели
 * и его свойства не меняются в ходе работы клиентского кода.
 */
class Track implements Countable
{
    use Simple;

    /** @var AbstractModel */
    private AbstractModel $model;

    /**
     * @param AbstractModel $model
     */
    public function __construct(AbstractModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $exclude_keys
     * @return array ключи, которые необходимо исключить из экспертизы
     */
    public function getDifference(array $exclude_keys = []): array
    {
        $differentValues = [];

        foreach ($this->model->getData() as $key => $value) {
            if (in_array($key, $exclude_keys)) {
                continue;
            }

            if (!$this->compareValue($key, $value)) {
                $differentValues[$key] = $value;
            }
        }

        return $differentValues;
    }

    /**
     * Сверяет значение $value со значением $this->data[$key].
     * Если значения равны, то возвращает true, false - в ином случае.
     *
     * @param string $key
     * @param $value
     * @return bool
     */
    public function compareValue(string $key, $value): bool
    {
        if (is_object($value) && is_object($this->data[$key])) {
            return $value == $this->data[$key];
        } else {
            /*
                Отсутствующие значения и проблемы связанные с NULL и пустой строкой.

                Если объект достали из СУБД, в track записались свойства с NULL значением.
                Пришли данные из POST-запроса с пустыми строками. Track при сравнении сочтет, что пустая строка -
                это значение отличное от NULL и даст добро на обновление данных.
                Но в базу опять запишется NULL, т.к. Mapper преобразует пустые строки в NULL.
                Получится лишний запрос, который в поле со значением NULL проставит в NULL.
                Пример такого поведения:

                // Нашли объект с пустым свойством last_name (поле в таблице в NULL)
                $object = $this->getMapper(UserMapper::class)->findModelById(3801);
                var_dump($object->getLastName()); // NULL
                echo "---------\n";
                $object->setLastName('');
                var_dump($object->getLastName()); // string(0) ""
                echo "---------\n";
                // save не сработает благодаря конструкции ниже
                $this->getMapper(UserMapper::class)->saveModel($object);
                echo "Query: " . $this->getMapper(UserMapper::class)->getDb()->getQueryString();

                Для предотвращения этой ситуации код ниже.
            */
            if ($value === '') {
                $value = null;
            }

            // Не использовать строгое сравнение, иначе 1 <> '1'
            return $value == $this->data[$key];
        }
    }

    /**
     * @return int
     */
    final public function count(): int
    {
        return count($this->data);
    }
}