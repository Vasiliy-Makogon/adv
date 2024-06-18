<?php

declare(strict_types=1);

namespace Krugozor\Framework\Model;

use BadMethodCallException;
use InvalidArgumentException;
use Krugozor\Cover\CoverArray;
use Krugozor\Cover\Simple;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Type\TypeInterface;
use LogicException;
use RuntimeException;

/**
 * @method getId() Идентификатор модели
 */
abstract class AbstractModel
{
    use Simple;

    /**
     * Карта атрибутов модели.
     * Перечисляются в дочерних классах в виде массивов следующего содержания:
     *
     * 'email' => [
     *     'type' => Email::class,
     *     'db_element' => true,
     *     'db_field_name' => 'advert_email',
     *     'validators' => [
     *         StringLengthValidator::class => [
     *             'start' => StringLengthValidator::ZERO_LENGTH,
     *             'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
     *         ],
     *         EmailValidator::class => [],
     *     ]
     * ],
     *
     * Допустимые свойства карты атрибутов модели и их возможные значения:
     *
     * type mixed
     *      Тип данных значения атрибута модели.
     *      Тип указывается только для значений не скалярных типов, объектов,
     *      имплементирующих интерфейс @see TypeInterface::class и DateTime::class
     *      Если тип не указан, значит, это скаляр.
     *      @see AbstractModel::setValueWithTransformation()
     *      @see AbstractModel::getPropertyType()
     *
     * db_element bool
     *      Должно ли значение атрибута модели записываться в БД.
     *      В большинстве случаев это свойство устанавливается в true. Исключения составляют
     *      какие-то "вспомогательные" атрибуты модели, которые записывать в БД не нужно
     *      (для них в таблицах просто нет полей).
     *      Например, атрибут модели ID (Primary Key) для каждой таблицы имеет значение false по
     *      причине того, что никогда не пишется в таблицу, а является лишь указателем
     *      на запись, т.е. фактически является "вспомогательным" в данной терминологии.
     *      @see CommonMapper::saveModel()
     *      @see AbstractModel::getPropertyDbElement()
     *
     * db_field_name string
     *      Имя поля таблицы данных, ассоциируемое с данным атрибутом модели.
     *      Если не указывается в карте модели, то подразумевается, что поле таблицы называется как имя атрибута.
     *      @see AbstractModel::getPropertyFieldName()
     *
     * default_value mixed
     *      Данный параметр никак не связан со значением DEFAULT SQL-описания таблицы данных,
     *      а является лишь значением атрибута модели устанавливаемым по умолчанию при инстанцировании объекта.
     *      @see AbstractModel::getPropertyDefaultValue()
     *
     * validators array
     *      Массив валидаторов, которые должны быть применены к атрибуту модели при присвоении ему значения.
     *      Валидаторы оперируют строковыми данными и срабатывают ДО того момента, когда на основании значения
     *      свойства 'type' карты аттрибутов модели, будет создано конкретное значение атрибута модели.
     *      @see AbstractModel::setValueWithTransformation()
     *
     *      Массив имеет вид:
     *
     *     'validators' => [
     *          StringLengthValidator::class => [
     *             'start' => StringLengthValidator::ZERO_LENGTH,
     *             'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
     *         ],
     *     ]
     *
     *      где ключ @see StringLengthValidator::class - класс валидатора, а значением является массив параметров валидатора.
     *      Для каждого параметра в валидаторе должен быть реализован set-метод, если требуется
     *      передать в валидатор какую-либо конфигурацию.
     *      Например, для параметра `start` и 'stop' из примера, в валидаторе @see StringLengthValidator::class
     *      реализованы set-методы
     *      @see StringLengthValidator::setStart()
     *      @see StringLengthValidator::setStop()
     *      которые установят в валидаторе значения `start`=0, 'stop'=255.
     *
     * record_once bool
     *      Если true, то значение атрибута модели записывается в базу единожды и больше не может быть перезаписано.
     *      Повторная установка значения для данного атрибута будет проигнорирована.
     *      Как пример - поле даты, символизирующее о дате создания записи или поле, которое обновляется не через ORM,
     *      а через триггеры БД или нативный исполняемый код.
     *      @see CommonMapper::saveModel()
     *      @see AbstractModel::isPropertyRecordOnce()
     *
     * default_excluded bool
     *      true, если значение атрибута модели никогда не должно быть записано в объект напрямую, а генерируется
     *      исключительно где-то в самой модели, минуя вызов set-методов, через конструкцию $this->data['key'] = 'val';
     *      Например, значение атрибута модели может устанавливаться исключительно в explicit-методе,
     *      пример использования: @see Advert::_setText(), Advert::setHashString()
     *      @see AbstractModel::setAttribute()
     *      @see AbstractModel::isPropertyDefaultExcluded()
     *
     * @var array
     */
    protected static array $model_attributes = [];

    /**
     * Оригинальные значения атрибутов модели.
     * Аналог @see AbstractModel::$data, но данный объект наполняется единожды, при вызове метода
     * @see AbstractModel::setData() и после не изменяется.
     * Предназначен для определения значений атрибутов модели, которые были изменены и должны быть подставлены
     * в SQL запрос на сохранение в методе @see CommonMapper::saveModel().
     *
     * @var Track
     */
    protected Track $track;

    /**
     * Префикс имен полей таблицы.
     * Исторически все поля таблиц моделей, за исключением поля id, именуются с однотипными префиксами,
     * означающим, к какой таблице относится поле.
     * Например: `user_name` (таблица `user`), `group_type` (таблица `group`) и т.д.
     * Данное свойство можно указать в виде пустой строки, тогда имена полей будут эквивалентны
     * именам свойств модели Однако, при ORM будут проблемы.
     * @todo проверить это
     *
     * @var string|null
     */
    protected static ?string $db_field_prefix = null;

    /**
     * Многомерный массив сообщений об ошибках валидации атрибутов модели.
     * Заполняется сообщениями, поступающими из валидаторов при присвоении объекту
     * значений, не удовлетворяющих описанным валидаторам в self::$model_attributes.
     *
     * @var array
     */
    protected array $validate_errors = [];

    /** @var MapperManager */
    private MapperManager $mapperManager;

    public function __construct()
    {
        $this->track = new Track($this);
    }

    /**
     * @param MapperManager $mapperManager
     * @return static
     */
    final public function setMapperManager(MapperManager $mapperManager): static
    {
        $this->mapperManager = $mapperManager;

        return $this;
    }

    /**
     * Принимает потенциальный массив значений атрибутов модели (вида "ключ_атрибута" => "значение")
     * и анализируя ключи, вызывает виртуальные set-методы, через которые значения
     * присваиваются атрибутам. Если в объект подается ключ, имя которого не найдено
     * в карте атрибутов модели @see AbstractModel::$model_attributes, то такое
     * присваивание будет проигнорировано без каких-либо ошибок.
     * Это поведение сделано для того, что бы любой мусор из POST-запроса
     * не мог вызывать какие-либо ошибки по этому поводу.
     *
     * @param iterable $data
     * @param array $excluded_keys ключи атрибутов из $data, которые будут проигнорированы при присваивании.
     * Если объект модели, для которого вызывается setData(), уже содержит атрибуты с ключами
     * из $excluded_keys, то эти атрибуты сохранят свои значения.
     * @return static
     */
    public function setData(iterable $data, array $excluded_keys = []): static
    {
        if (is_object($data) && $data instanceof CoverArray) {
            $data = $data->getDataAsArray();
        }

        // Из POST-запроса приходит массив вида:
        // 'id' => string '1344'
        // 'active' => string '1'
        // 'name' => string 'Vasya'
        // ...
        // а из базы получаем массив с префиксами полей:
        // ...
        // 'id' => string '1344'
        // 'user_active' => string '1'
        // 'user_name' => string 'Vasya'
        //
        // Цикл ниже приводит ключи массива $data в нормальный вид - без префикса полей.
        foreach ($data as $key => $value) {
            unset($data[$key]);
            $data[self::getPropertyNameWithoutPrefix($key)] = $value;
        }

        foreach (self::getModelsPropertiesSettings() as $key => $props) {
            if (in_array($key, $excluded_keys)) {
                continue;
            }

            $method_name = $this->getMethodNameByKeyWithPrefix($key);

            // Если в массиве извне есть пара ключ => значение, значит
            // необходимое значение нам передали (например, из POST-запроса)...
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            }
            // .. иначе значение не пришло.
            // Мы это значение получим либо из track, а если его там нет - возьмем то,
            // что объявлено по-умолчанию.
            else {
                $value = array_key_exists($key, $this->getTrack()->getData())
                         ? $this->getTrack()->$key
                         : self::getPropertyDefaultValue($key);
            }

            $this->$method_name($value);
        }

        // Если идет создание нового объекта, то заполняем $this->track
        // Возможно данный код придется переписать.
        // Он даст логическую ошибку на ситуации, когда setData() будет вызван
        // более одного раза на пустом объекте. Во второй раз данные не будут записаны в track.
        // Проверочный код:
        /*
           $object = $this->getMapper(UserMapper::class)->createModel();
           $object->setData(array('id'=>0, 'unique_cookie_id' => 'value'));
           var_dump($object->getTrack()->email);
           echo "\n\n--------\n\n";
           $object->setData(array('email' => 'info@mirgorod.ru'));
           var_dump($object->getTrack()->email);
        */
        if ($this->getTrack()->count() === 0) {
            $this->getTrack()->setData($this->data);
        }

        return $this;
    }

    /**
     * @return Track
     */
    public function getTrack(): Track
    {
        return $this->track;
    }

    /**
     * Возвращает префикс имени поля таблицы.
     *
     * @return null|string
     */
    public function getDbFieldPrefix(): ?string
    {
        return static::$db_field_prefix;
    }

    /**
     * Возвращает имя поля таблицы для атрибута модели $property_name.
     * Если имя поля таблицы явно не указано, вернёт значение $property_name.
     *
     * @param string $property_name
     * @return string
     */
    public static function getPropertyFieldName(string $property_name): string
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['db_field_name'] ?? $property_name;
    }

    /**
     * Возвращает значение по умолчанию атрибута модели $property_name.
     *
     * @param string $property_name
     * @return mixed
     */
    public static function getPropertyDefaultValue(string $property_name): mixed
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['default_value'] ?? null;
    }

    /**
     * Возвращает значение, указывающее, пишется ли атрибут модели $property_name в СУБД.
     *
     * @param string $property_name
     * @return bool
     */
    public static function getPropertyDbElement(string $property_name): bool
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['db_element'];
    }

    /**
     * Возвращает тип атрибута модели $property_name.
     *
     * @throws InvalidArgumentException
     * @param string $property_name
     * @return string|null
     */
    public static function getPropertyType(string $property_name): ?string
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['type'] ?? null;
    }

    /**
     * Возвращает true, если атрибут модели $property_name записывается в объект единожды.
     *
     * @throws InvalidArgumentException
     * @param string $property_name
     * @return bool
     */
    public static function isPropertyRecordOnce(string $property_name): bool
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['record_once'] ?? false;
    }

    /**
     * @param string $property_name
     * @return bool
     */
    public static function isPropertyExist(string $property_name): bool
    {
        return !empty(static::$model_attributes[$property_name]);
    }

    /**
     * Возвращает true, если атрибут модели по-умолчанию excluded.
     *
     * @param string $property_name
     * @return bool
     */
    public static function isPropertyDefaultExcluded(string $property_name): bool
    {
        if (!isset(static::$model_attributes[$property_name])) {
            throw new InvalidArgumentException("Не найден атрибут модели `$property_name`");
        }

        return static::$model_attributes[$property_name]['default_excluded'] ?? false;
    }

    /**
     * Возвращает карту атрибутов модели.
     *
     * @return array
     */
    public function getModelsPropertiesSettings(): array
    {
        return static::$model_attributes;
    }

    /**
     * Получает имя атрибут модели без префикса @see AbstractModel::$db_field_prefix
     *
     * @param string $property_name
     * @return string
     */
    protected function getPropertyNameWithoutPrefix(string $property_name): string
    {
        return preg_replace(
            '~^(?:' . static::$db_field_prefix . '_)*([a-z0-9_]+)$~',
            '$1',
            $property_name
        );
    }

    /**
     * Получает имя set- или get- метода для атрибута модели с именем $property_name.
     * Имя атрибута модели $property_name может подаваться с приставкой $this->db_field_prefix
     * или без неё, т.е. методы с разными вызовами:
     *
     * ->getMethodNameByKeyWithPrefix('user_name', 'set');
     * ->getMethodNameByKeyWithPrefix('name', 'set');
     *
     * возвратят одинаковый результат - имя метода setUserName()
     *
     * @param string $property_name имя атрибута модели
     * @param string $action set|get действие метода
     * @return string имя get- или set- метода
     */
    public function getMethodNameByKeyWithPrefix(string $property_name, string $action = 'set'): string
    {
        $key = preg_replace('~^' . static::$db_field_prefix . '_([a-z0-9_]+)$~', '$1', $property_name);

        if (!static::isPropertyExist($key)) {
            throw new InvalidArgumentException(sprintf(
                '%s: Указано некорректное имя атрибута модели `%s`',__METHOD__ , $key
            ));
        }

        if (!in_array($action, array('set', 'get'))) {
            throw new InvalidArgumentException(sprintf(
                '%s: Указан некорректный action `%s`',__METHOD__ , $action
            ));
        }

        return $action . Strings::formatToCamelCaseStyle($key);
    }

    /**
     * Защита от выстрела в ногу.
     * Фактически, это аналог любого виртуального метода setPropertyName(),
     * но в виде "некрасивого" присваивания значения атрибуту модели через имя атрибута.
     *
     * @inheritDoc
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Получение и установка атрибутов модели через вызов магических методов вида:
     * $model->setPropertyName($value);
     * $model->getPropertyName();
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

        if (!static::isPropertyExist($property_name)) {
            throw new BadMethodCallException(sprintf(
                '%s: Вызов неизвестного метода %s::%s', __METHOD__, get_class($this), $method_name
            ));
        }

        switch ($action) {
            case 'get':
                return $this->getAttribute($property_name);

            case 'set':
                if (count($arguments) == 0) {
                    throw new InvalidArgumentException(sprintf(
                        '%s: вызов метода `%s:%s` без указания аргумента',
                        __METHOD__, get_class($this), $method_name
                    ));
                }

                $this->setAttribute($property_name, $arguments[0]);
                return $this;

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s: вызов метода с неопределённым action `%s`', __METHOD__, $action
                ));
        }
    }

    /**
     * Возвращает ошибки валидации модели.
     *
     * @param bool $add_model_prefix true, если ключи нужно возвращать
     * с префиксом @see AbstractModel::$db_field_prefix, false - в ином случае.
     * @return array
     */
    public function getValidateErrors(bool $add_model_prefix = false): array
    {
        if ($add_model_prefix) {
            foreach ($this->validate_errors as $key => $value) {
                unset($this->validate_errors[$key]);

                $this->validate_errors[static::$db_field_prefix . '_' . $key] = $value;
            }
        }

        return $this->validate_errors;
    }

    /**
     * Возвращает ошибки валидации свойства $key.
     *
     * @param string $key имя проверяемого свойства
     * @return array информация об ошибке
     */
    public function getValidateErrorsByKey(string $key): array
    {
        return $this->validate_errors[$key] ?? [];
    }

    /**
     * Явный метод setId(), предупреждающий затирание явно существующего ID текущего объекта.
     *
     * @param mixed $id
     * @return static
     */
    public function setId(mixed $id): static
    {
        if (!empty($this->data['id']) && $this->data['id'] != $id) {
            throw new LogicException(sprintf(
                '%s: Попытка переопределить значение ID объекта модели `%s` значением `%s`',
                __METHOD__,
                get_class($this),
                $id
            ));
        }

        $this->setAttribute('id', $id);

        return $this;
    }

    /**
     * @param int|string $id
     * @return string
     */
    public static function createModelCacheKey(int|string $id): string
    {
        return sprintf('%s_%s', basename(static::class), $id);
    }

    /**
     * Очистка кэша записи, если какие-либо данные были изменены.
     *
     * @param array $exclude_keys
     * @return static
     */
    public function clearCache(array $exclude_keys = []): static
    {
        if ($this->getId() && $this->getTrack()->getDifference($exclude_keys)) {
            Context::getInstance()->getMemcache()->delete(
                static::createModelCacheKey($this->getId())
            );
        }

        return $this;
    }

    /**
     * Очистка кэша записи.
     *
     * @return static
     */
    public function deleteCache(): static
    {
        if ($this->getId()) {
            Context::getInstance()->getMemcache()->delete(
                static::createModelCacheKey($this->getId())
            );
        }

        return $this;
    }

    /**
     * Сохранение записи модели.
     *
     * @return static
     * @throws MySqlException
     */
    public function save(): static
    {
        return $this->getMapperManager()
            ->getMapper(static::getMapperClass())
            ->saveModel($this);
    }

    /**
     * Удаление записи модели.
     *
     * @return int
     */
    public function delete(): int
    {
        return $this->getMapperManager()
            ->getMapper(static::getMapperClass())
            ->deleteModel($this);
    }

    /**
     * @return MapperManager
     */
    final protected function getMapperManager(): MapperManager
    {
        return $this->mapperManager;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    final protected function getMapperClass(): string
    {
        $mapperClassName = str_replace('\\Model\\', '\\Mapper\\', static::class) . 'Mapper';

        if (!class_exists($mapperClassName)) {
            throw new RuntimeException(sprintf(
                '%s: Не найден mapper-класс `%s`', __METHOD__, $mapperClassName
            ));
        }

        return $mapperClassName;
    }

    /**
     * Устанавливает значение $value для атрибута $key объекта модели.
     * Вызов метода сопровождается:
     * - проверкой карты атрибутов модели на наличие флагов 'record_once' и 'default_excluded'
     * - валидацией согласно массиву валидаторов атрибута
     * - трансформацией значения атрибута модели в необходимый тип
     * - проверка наличия и вызов explicit-метода
     *
     * @param string $key имя атрибута модели
     * @param mixed $value значение атрибута модели
     */
    protected function setAttribute(string $key, mixed $value): void
    {
        if (!static::isPropertyExist($key)) {
            throw new InvalidArgumentException(sprintf(
                '%s: Атрибут `%s` не принадлежит модели `%s`', __METHOD__, $key, get_class($this)
            ));
        }

        if (self::isPropertyDefaultExcluded($key)) {
            return;
        }

        /**
         * В карте атрибутов модели @see AbstractModel::$model_attributes указаны валидаторы,
         * которыми необходимо валидировать каждое значение атрибута.
         * Модель принимает любые скалярные данные, даже ошибочные.
         * Валидация в модели носит лишь уведомительный характер.
         * Принимать решение, что делать с ошибочной моделью должен слой, оперирующий с этой моделью.
         */
        if (isset(static::$model_attributes[$key]['validators'])) {
            // Если в объекте модели уже содержится информация об ошибочном заполнении
            // данного свойства, то эту информацию необходимо удалить, т.к. идет присвоение нового
            // значения и старая информация об ошибках уже не актуальна.
            if (isset($this->validate_errors[$key])) {
                unset($this->validate_errors[$key]);
            }

            foreach (static::$model_attributes[$key]['validators'] as $validatorClassName => $params) {
                if (!class_exists($validatorClassName)) {
                    throw new InvalidArgumentException(sprintf(
                        "%s: Не найден класс валидатора `%s`",
                        __METHOD__, $validatorClassName
                    ));
                }

                if (class_exists($validatorClassName)) {
                    // $value может быть либо объектом - собственным типом данных фреймворка, либо скаляром.
                    $value = is_object($value) && $value instanceof TypeInterface
                        ? $value->getValue()
                        : $value;

                    $validator = new $validatorClassName($value);

                    foreach ($params as $validator_criteria => $criteria_value) {
                        $method = 'set' . Strings::formatToCamelCaseStyle($validator_criteria);

                        if (method_exists($validator, $method)) {
                            $validator->$method($criteria_value);
                        } else {
                            throw new BadMethodCallException(sprintf(
                                '%s: Вызов неизвестного метода валидатора `%s::%s`',
                                __METHOD__, $validatorClassName, $method
                            ));
                        }
                    }

                    // Возникли ошибки валидации, помещаем их в общее хранилище.
                    if (!$validator->validate()) {
                        $this->validate_errors[$key][] = $validator->getError();

                        if ($validator->getBreak()) {
                            break;
                        }
                    }
                }
            }
        }

        $this->setValueWithTransformation($key, $value);

        /**
         * Смотрим, имеется ли в классе явно объявленный set-метод с префиксом "_set" для
         * данного атрибута модели и имеются ли ошибки валидации.
         * Если метод объявлен явно, а ошибок валидации нет, то применяем метод для текущего состояния атрибута.
         * Данные методы в модели нужны для ситуаций, когда при присвоении значения атрибуту,
         * необходимо обработать значение с помощью какой-либо логики или запустить иные скрытые процессы.
         * Как пример, можно посмотреть метод @see Advert::_setText()
         */
        $explicit_method = '_set' . Strings::formatToCamelCaseStyle($key);

        if (method_exists($this, $explicit_method) && !isset($this->validate_errors[$key])) {
            $this->data[$key] = $this->$explicit_method($this->data[$key]);
        }
    }

    /**
     * Возвращает атрибут $key модели с вызовом explicit-метода.
     *
     * @param string $key
     * @return mixed
     */
    protected function getAttribute(string $key): mixed
    {
        /**
         * Смотрим, имеется ли в классе явно объявленный get-метод с префиксом "_get" для данного атрибута модели.
         * Если метод объявлен явно, то применяем метод для значения текущего состояния атрибута.
         * Как пример, можно посмотреть метод @see Category::_getDescription()
         */
        $explicit_method = '_get' . Strings::formatToCamelCaseStyle($key);

        if (method_exists($this, $explicit_method)) {
            return $this->$explicit_method($this->$key);
        }

        return $this->$key;
    }

    /**
     * Устанавливает значение $value атрибуту модели $key в соответствии с типом атрибута,
     * описанным в карте атрибутов модели @see AbstractModel::$model_attributes
     *
     * @param string $key имя атрибута
     * @param mixed $value значение атрибута
     * @throws RuntimeException
     */
    private function setValueWithTransformation(string $key, mixed $value)
    {
        // Тип атрибута не указан в карте описания атрибутов модели.
        // Значит, работаем со скалярным типом данных и присваеваем
        // "как есть" значение $value атрибуту $key.
        if (!isset(static::$model_attributes[$key]['type'])) {
            $this->data[$key] = $value;
        } else {
            // Если $value - объект, производный от указанного в карте
            // описания атрибутов модели, то никаких преобразований с $value не делаем.
            if (is_object($value) && $value instanceof static::$model_attributes[$key]['type']) {
                $this->data[$key] = $value;
            } else {
                if (!class_exists(static::$model_attributes[$key]['type'])) {
                    throw new RuntimeException(sprintf(
                        ': Не найден класс типа `%s` для атрибута `%s` модели `%s`',
                        static::$model_attributes[$key]['type'],
                        $key,
                        get_class($this)
                    ));
                }

                $this->data[$key] = match (true) {
                    // Для атрибутов типа DateTime идёт проверка на пустое значение, т.к. присваивание
                    // null данному объекту равноценно созданию объекта с явным значением.
                    static::$model_attributes[$key]['type'] === DateTime::class && (is_null($value) || $value === '') => null,
                    // Если $value - скалярное значение, значит, его необходимо
                    // преобразовать в указанный в карте атрибутов объект.
                    // Для этого значение $value необходимо передать в конструктор.
                    default => new static::$model_attributes[$key]['type']($value)
                };
            }
        }
    }
}