<?php

declare(strict_types=1);

namespace Krugozor\Framework\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Model\Interface\TreeElementInterface;

class Tree extends CommonMapper
{
    /**
     * Получает полное дерево.
     *
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadTree(int $level = 0): CoverArray
    {
        $sql = '/* ?f */ SELECT * FROM ?f ' . (
            $level ? ' WHERE category_indent <= ' . $level : ''
        ) . ' ORDER BY `order` DESC';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName()
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Получает полное дерево потомков узла $id (включая сам узел).
     *
     * @param int $id ID узла, для которого выбираем потомков
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadSubTree(int $id): CoverArray
    {
        $sql = '
            /* ?f */
            WITH recursive cte AS (
                SELECT * FROM ?f WHERE id = ?i 
                UNION 
                SELECT c.* FROM ?f c INNER JOIN cte ON c.pid = cte.id
            ) SELECT * FROM cte';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName(),
            $id,
            $this->getTableName()
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Получает полное дерево потомков узла $id (исключая сам узел).
     *
     * @param int $id ID узла, для которого выбираем потомков
     * @param int $level ограничить выборку вложенностью $indent. Корневые узлы имеют category_indent = 0
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadSubTreeWithoutSpecifiedLevel(int $id, int $level = 0): CoverArray
    {
        $sql = '
            /* ?f */
            WITH recursive cte AS (
                SELECT * FROM ?f WHERE id = ?i 
                UNION 
                SELECT c.* FROM ?f c 
                INNER JOIN cte ON c.pid = cte.id
            ) SELECT * FROM cte WHERE id <> ?i' . ($level ? ' AND cte.category_indent <= ' . $level : '') .
            ' ORDER BY cte.`order` DESC';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName(),
            $id,
            $this->getTableName(),
            $id
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Получает путь от начала дерева к указанной вершине (включая саму вершину).
     *
     * @param int $id
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadPath(int $id): CoverArray
    {
        $sql = '
        /* ?f */
        WITH recursive cte AS ( 
            SELECT * FROM ?f WHERE id = ?i
            UNION 
            SELECT c.* FROM ?f c INNER JOIN cte ON c.id = cte.pid
        ) SELECT * FROM cte';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName(),
            $id,
            $this->getTableName()
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Получает путь от начала дерева к указанной вершине (исключая саму вершину).
     *
     * @param int $id
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadPathWithoutSpecifiedLevel(int $id): CoverArray
    {
        $sql = '
        /* ?f */
        WITH recursive cte AS ( 
            SELECT * FROM ?f WHERE id = ?i
            UNION 
            SELECT c.* FROM ?f c INNER JOIN cte ON c.id = cte.pid
        ) SELECT * FROM cte WHERE id <> ?i';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName(),
            $id,
            $this->getTableName(),
            $id
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Получает непосредственных потомков узла.
     *
     * @param int $id
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadChilds(int $id): CoverArray
    {
        $sql = '/* ?f */ SELECT * FROM ?f WHERE `pid` = ?i ORDER BY `order` DESC;';

        $statement = $this->getDb()->query(
            $sql,
            __METHOD__,
            $this->getTableName(),
            $id
        );

        return $this->createTreeByStatement($statement);
    }

    /**
     * Создаёт дерево
     *
     * @param Statement $statement
     * @return CoverArray
     */
    public function createTreeByStatement(Statement $statement): CoverArray
    {
        $data = $this->result2medium($statement);

        return $this->medium2objectTree($data);
    }

    /**
     * @param Statement $statement
     * @return array
     */
    protected function result2medium(Statement $statement): array
    {
        $data = [];

        if ($statement->getNumRows()) {
            while ($row = $statement->fetchAssoc()) {
                if (!isset($data[$row['category_indent']])) {
                    $data[$row['category_indent']] = [];
                }

                if (!isset($data[$row['category_indent']][$row['pid']])) {
                    $data[$row['category_indent']][$row['pid']] = [];
                }

                $data[$row['category_indent']][$row['pid']][] = $row;
            }
        }

        return $data;
    }

    /**
     * Создает дерево объектов из многомерного массива, возвращённого методом
     *
     * @param array $data массив, возвращаемый от Tree::createTreeByStatement()
     * @param int $level миниальный уровень вложености массива
     * @param null $parent_id
     * @return CoverArray
     */
    protected function medium2objectTree(array $data, $parent_id = null): CoverArray
    {
        // Получаем ID самого верхнего уровня, что бы начать с него построение дерева.
        $level = $data ? min(array_keys($data)) : 0;
        $tree = new CoverArray();

        if (empty($data[$level])) {
            return $tree;
        }

        $currentLevelData = $data[$level];
        unset($data[$level]);

        foreach ($currentLevelData as $modelPid => $levelData) {
            foreach ($levelData as $modelData) {
                if ($parent_id !== null && $parent_id != $modelPid) {
                    continue;
                }

                /** @var TreeElementInterface $treeElement */
                $treeElement = parent::createModelFromDatabaseResult($modelData);
                $treeElement->setTree($this->medium2objectTree($data, $treeElement->getId()));
                $tree->append($treeElement);
            }
        }

        return $tree;
    }
}