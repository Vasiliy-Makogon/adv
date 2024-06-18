<?php

namespace Krugozor\Framework\Type;

interface TypeInterface
{
    /**
     * Возвращает скалярное/сформированное значение типа, пригодное для записи в БД.
     *
     * @return mixed
     */
    public function getValue(): mixed;
}