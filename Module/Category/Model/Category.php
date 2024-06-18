<?php

namespace Krugozor\Framework\Module\Category\Model;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Model\Interface\TreeElementInterface;
use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\Mapper\TerritoryMapper;
use Krugozor\Framework\Module\Category\Type\AdvertTypesType;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;
use Krugozor\Framework\Statical\Translit;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\StringLengthValidator;
use Krugozor\Framework\Validator\VarSetValidator;

/**
 * @method getPid()
 * @method setPid($pid)
 *
 * @method AdvertTypesType getAdvertTypes()
 * @method AdvertTypesType setAdvertTypes($types)
 *
 * @method getName()
 * @method setName($name)
 *
 * @method string getAlias()
 * @method string setAlias($alias)
 *
 * @method string getUrl()
 * @method string setUrl($url)
 *
 * @method string getIsService()
 * @method string setIsService($is_service)
 *
 * @method string getDescription()
 * @method string setDescription($description)
 *
 * @method string getText()
 * @method string setText($description)
 *
 * @method string getKeywords()
 * @method string setKeywords($keywords)
 *
 * @method string setAdvertCount($advertCount)
 * @method string getAdvertCount()
 *
 * @method string getShowOnGrandparent()
 * @method string setShowOnGrandparent($showOnGrandparent)
 *
 * @method string getIndent()
 * @method string setIndent($indent)
 *
 * @method string getPaid()
 * @method string setPaid($paid)
 */
class Category extends AbstractModel implements TreeElementInterface
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'category';

    /**
     * Дерево подкатегорий данного узла.
     *
     * @var CoverArray
     */
    protected CoverArray $tree;

    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'db_field_name' => 'id',
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'pid' => [
            'db_element' => true,
            'db_field_name' => 'pid',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'advert_types' => [
            'type' => AdvertTypesType::class,
            'db_element' => true,
            'db_field_name' => 'category_advert_types',
            'default_value' => AdvertType::TYPE_SALE . ',' . AdvertType::TYPE_BUY,
            'validators' => [
                VarSetValidator::class => [
                    'enum' => [
                        AdvertType::TYPE_SALE,
                        AdvertType::TYPE_BUY,
                        AdvertType::TYPE_RENT_SALE,
                        AdvertType::TYPE_RENT_BUY,

                        AdvertType::TYPE_JOB_FULL,
                        AdvertType::TYPE_JOB_PART,
                        AdvertType::TYPE_JOB_PROBATION,
                        AdvertType::TYPE_JOB_PROJECT,
                        AdvertType::TYPE_JOB_VOLUNTEER,
                    ]
                ],
            ]
        ],

        'name' => [
            'db_element' => true,
            'db_field_name' => 'category_name',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'alias' => [
            'db_element' => true,
            'db_field_name' => 'category_alias',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'url' => [
            'db_element' => true,
            'db_field_name' => 'category_url',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'is_service' => [
            'db_element' => true,
            'db_field_name' => 'category_is_service',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],

        'description' => [
            'db_element' => true,
            'db_field_name' => 'category_description',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 3000
                ],
            ]
        ],

        'text' => [
            'db_element' => true,
            'db_field_name' => 'category_text',
            'validators' => [],
        ],

        'keywords' => [
            'db_element' => true,
            'db_field_name' => 'category_keywords',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 5000
                ],
            ]
        ],

        /**
         * Общее количество объявлений в этой категории.
         * См. логику проставления значений в category_advert_count.php
         */
        'advert_count' => [
            'db_element' => true,
            'db_field_name' => 'category_advert_count',
            'default_value' => 0,
            'record_once' => true,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        /*
         * Уровень вложенности. Категории первого уровня имеют значение 1. Только что добавленные - 0.
         * См. логику проставления значений в category_advert_count.php
         */
        'indent' => [
            'db_element' => true,
            'db_field_name' => 'category_indent',
            'default_value' => 0,
            'record_once' => true,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        /* Платная категория или нет */
        'paid' => [
            'db_element' => true,
            'db_field_name' => 'category_paid',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->tree = new CoverArray();
    }

    /**
     * Возвращает true, если эта категория первого уровня.
     *
     * @return bool
     */
    public function isTopCategory(): bool
    {
        return $this->getPid() == 0;
    }

    /**
     * Возвращает дерево подкатегорий данного узла.
     *
     * @return CoverArray
     */
    public function getTree(): CoverArray
    {
        return $this->tree;
    }

    /**
     * Присваивает дерево подкатегорий данному узлу.
     *
     * @param CoverArray $tree
     * @return static
     */
    public function setTree(CoverArray $tree): static
    {
        $this->tree = $tree;

        return $this;
    }

    /**
     * @return Category
     * @see CategoryMapper::findParentCategory
     */
    public function findParentCategory(): Category
    {
        return $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->findParentCategory($this->getPid());
    }

    /**
     * Возвращает непосредственных дочерних узлов категории.
     *
     * @return CoverArray
     * @see CategoryMapper::findChilds
     */
    public function findChilds(): CoverArray
    {
        return $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->findChilds($this->getId());
    }

    public function findChildsWithIndent(int $indent): CoverArray
    {
        return $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->loadSubTreeWithoutSpecifiedLevel($this->getId(), $this->getIndent() + $indent);
    }

    /**
     * Возвращает ID's непосредственных дочерних узлов категории.
     *
     * @return CoverArray
     * @see CategoryMapper::findChildsIds
     */
    public function findChildsIds(): CoverArray
    {
        return $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->findChildsIds($this->getId());
    }

    /**
     * Получает путь от начала дерева к указанной вершине.
     *
     * @return CoverArray
     * @see CategoryMapper::loadPath
     */
    public function findPath(): CoverArray
    {
        return $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->loadPath($this->getId());
    }

    /**
     * Возвращает имя категории для HTML элемента option
     * с возможной иммитацией padding-left в виде многоточия.
     *
     * @param int $repeat
     * @return string
     */
    public function getNameForOptionElement(int $repeat = 3): string
    {
        return str_repeat('.', $this->getIndent() * $repeat) . $this->getName();
    }

    /**
     * Возвращает URL родительской категории.
     *
     * @return string
     */
    public function getParentCategoryUrl(): string
    {
        return str_replace($this->getAlias() . '/', '', $this->getUrl());
    }

    /**
     * Создает и сохраняет полный URL-адрес к категории от начала дерева.
     *
     * @param array
     * @throws MySqlException
     */
    public function updateUrl(): void
    {
        $tree = $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->loadPathWithoutSpecifiedLevel($this->getId());

        $aliases = static::getValuesFromTree($tree, 'getAlias');
        $aliases[] = $this->getAlias();

        $this->setUrl('/' . implode('/', $aliases) . '/');

        $this->getMapperManager()
            ->getMapper(CategoryMapper::class)
            ->saveModel($this);
    }

    /**
     * Возвращает кол-во объявлений в категории территории $territory.
     *
     * @param AbstractTerritory|null $territory
     * @return int
     * @see TerritoryMapper::findAdvertCountInTerritoryAndCategory
     */
    public function findAdvertCount(?AbstractTerritory $territory = null): int
    {
        if ($territory === null) {
            return $this->getAdvertCount();
        }

        return $this->getMapperManager()
            ->getMapper(TerritoryMapper::class)
            ->findAdvertCountInTerritoryAndCategory($territory, $this);
    }

    /**
     * На основании списка строк - имён категорий, создаёт дочерние категории.
     *
     * @param array $newCategoriesList
     */
    public function createChildsFromList(array $newCategoriesList): void
    {
        sort($newCategoriesList);
        $newCategoriesList = array_reverse($newCategoriesList);

        foreach ($newCategoriesList as $newCategoryName) {
            /** @var Category $newCategory */
            $newCategory = $this->getMapperManager()->getMapper(static::getMapperClass())->createModel();
            $newCategory->setName($newCategoryName);
            $newCategory->setAlias($newCategoryName);
            $newCategory->setPid($this->getId());
            $this->getMapperManager()->getMapper(static::getMapperClass())->saveCategory($newCategory);
        }
    }

    /**
     * Извлекает из каждого элемента дерева значение с помощью метода
     * $method_name и помещает его в результирующий массив-список.
     * Подразумевается, что в каждом элементе дерева есть метод $method_name.
     *
     * @param CoverArray $tree дерево объектов, из которых необходимо получать значение
     * @param string $method_name имя get-метода получения свойства объекта
     * @return array
     */
    protected static function getValuesFromTree(CoverArray $tree, string $method_name): array
    {
        $data = [];

        foreach ($tree as $element) {
            $data[] = $element->$method_name();

            if ($element->getTree() && $element->getTree()->count()) {
                $data = array_merge($data, self::getValuesFromTree($element->getTree(), $method_name));
            }
        }

        return $data;
    }

    /**
     * Устанавливает локальный URL категории, транслитилируя значение.
     * explicit-метод.
     *
     * @param string|null $alias имя категории
     * @return string|null имя категории в транслите
     */
    protected function _setAlias(?string $alias): ?string
    {
        return is_null($alias) ? $alias : Translit::UrlTranslit($alias);
    }

    /**
     * Установка ключевых слов с сортировкой и отсеиванием дубликатов.
     * explicit-метод.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function _setKeywords(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $data = explode(',', $value);

        $keywords = [];

        foreach ($data as $word) {
            $word = trim($word);
            if ($word) {
                $word = mb_strtolower($word);
                if (!str_contains($word, 'в {city}')) {
                    $word = "$word в {city}";
                }
                $keywords[] = $word;
            }
        }

        sort($keywords);

        return implode(', ', array_unique($keywords, SORT_STRING));
    }
}