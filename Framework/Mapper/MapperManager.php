<?php

declare(strict_types=1);

namespace Krugozor\Framework\Mapper;

use InvalidArgumentException;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Controller\AbstractController;

/**
 * Объект-хранилище, содержащий инстанцированные mapper-объекты.
 *
 * Данный объект через метод self::getMapper(ModelMapper::class) порождает конкретный
 * маппер модели Model.
 *
 *
 * Объект MapperManager доступен в Контроллере, Модели и в самих Мапперах.
 *
 * - В Контроллере доступ к MapperManager напрямую запрещён, доступ
 * к конкретному Мапперу осуществляется через вызов метода контроллера
 * Controller::getMapper(ModelMapper::class);
 *
 * Первое инстанцирование данного класса происходит в @see AbstractController::getMapper(),
 * после чего Manager передается во все создаваемые модели и мапперы, т.к. инстанс всех
 * мапперов происходит из Контроллеров, а всех моделей - из Мапперов.
 *
 * - В Модели и Маппере доступ к Manager осуществляется через метод
 * $this->getMapperManager(), доступ к конкретному Мапперу осуществляется через
 * $this->getMapperManager()->getMapper(ModelMapper::class);
 *
 * Объект СУБД доступен в MapperManager, поэтому обращение в СУБД из маппера
 * должно идти так: MapperManager->getDb()
 */
class MapperManager
{
    /** @var array Коллекция инстанцированных мэпперов */
    private static array $mappers = [];

    /**
     * @param Mysql $db
     */
    public function __construct(private Mysql $db)
    {}

    /**
     * @param string $mapperClass
     * @return AbstractMapper
     */
    final public function getMapper(string $mapperClass): AbstractMapper
    {
        if (isset(self::$mappers[$mapperClass])) {
            return self::$mappers[$mapperClass];
        }

        if (!class_exists($mapperClass)) {
            throw new InvalidArgumentException(sprintf(
                "%s: Попытка вызвать неизвестный mapper `%s`", __METHOD__, $mapperClass
            ));
        }

        return self::$mappers[$mapperClass] = new $mapperClass($this);
    }

    /**
     * @return Mysql
     */
    final public function getDb(): Mysql
    {
        return $this->db;
    }
}