<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Model;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Group\Mapper\AccessMapper;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;

/**
 * @method getName()
 * @method setName($name)
 *
 * @method getActive()
 * @method setActive($active)
 *
 * @method getAlias()
 * @method setAlias($alias)
 *
 * @method getAccess()
 * @method setAccess($access)
 */
class Group extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'group';

    /** @var int ID группы `администраторы` в СУБД */
    const ID_GROUP_ADMINISTRATOR = 1;

    /** @var int ID группы `пользователи` в СУБД. */
    const ID_GROUP_USER = 2;

    /** @var int ID группы `гости` в СУБД. */
    const ID_GROUP_GUEST = 3;

    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'name' => [
            'db_element' => true,
            'db_field_name' => 'group_name',
            'validators' => [
                IsNotEmptyValidator::class => [],
            ]
        ],

        'active' => [
            'db_element' => true,
            'default_value' => 1,
            'db_field_name' => 'group_active',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],

        'alias' => [
            'db_element' => true,
            'db_field_name' => 'group_alias',
            'validators' => [
                IsNotEmptyValidator::class => [],
                CharPasswordValidator::class => [],
            ]
        ],

        /**
         * Сериализованный массив прав доступа группы к модулям системы.
         * Данное свойство записывается исключительно в момент сохранения данных группы
         *  в контроллере @see \Krugozor\Framework\Module\Group\Controller\BackendEdit
         */
        'access' => [
            'db_element' => true,
            'default_value' => 'a:0:{}',
            'db_field_name' => 'group_access',
            'validators' => []
        ],
    ];

    /**
     * Коллекция объектов доступа группы (Access)
     * к контроллерам системы. Заполняется при POST-запросе.
     *
     * @var null|CoverArray
     */
    protected ?CoverArray $accesses = null;

    /**
     * @var null|DenormalizedAccesses
     */
    protected ?DenormalizedAccesses $denormalizedAccesses = null;

    /**
     * Денормализует права доступа группы после сериализации или достает их из базы, после чего
     * возвращает объект DenormalizedAccesses с помощью которого можно проверить право группы
     * на доступ к конкретному контроллеру.
     *
     * Права хранятся в виде структуры
     *
     *    [ModuleKey] => Array (
     *      [ContollerKey] => int access
     *      [...]
     *    )
     *
     * @return DenormalizedAccesses
     */
    public function getDenormalizedAccesses(): DenormalizedAccesses
    {
        if ($this->denormalizedAccesses === null) {
            // в поле `access` обнаружены сериализованные права доступа, получаем их
            if ($this->getAccess()) {
                $this->denormalizedAccesses = new DenormalizedAccesses(
                    unserialize($this->getAccess())
                );
            } else {
                // прав доступа в поле `access` не найдено, делаем запрос на их получение
                $accesses = $this->getMapperManager()->getMapper(AccessMapper::class)
                    ->getGroupAccessByIdWithControllerNames($this->getId())
                    ->getDataAsArray();
                $this->denormalizedAccesses = new DenormalizedAccesses($accesses);
            }
        }

        return $this->denormalizedAccesses;
    }

    /**
     * @param array|CoverArray $data
     * @param array $excluded_keys
     * @return static
     */
    public function setData($data, array $excluded_keys = []): static
    {
        parent::setData($data, $excluded_keys);

        // При создании/редактировании группы из административной части.
        if (!empty($data['accesses'])) {
            if (!$this->accesses) {
                $this->accesses = new CoverArray();
            }

            foreach ($data['accesses'] as $access_data) {
                foreach ($access_data as $id_controller => $access_value) {
                    /** @var Access $access */
                    $access = $this->getMapperManager()
                        ->getMapper(AccessMapper::class)
                        ->createModel()
                        ->setIdGroup($this->getId())
                        ->setIdController($id_controller)
                        ->setAccess($access_value);

                    $this->accesses->append($access);
                }
            }
        }

        return $this;
    }

    /**
     * Возвращает коллекцию объектов доступа.
     *
     * @return CoverArray
     */
    public function getAccesses(): CoverArray
    {
        if (!$this->accesses) {
            $this->accesses = $this->findAccesses();
        }

        return $this->accesses;
    }

    /**
     * Ищет и возвращает коллецию объектов доступа.
     * Lazy Load.
     *
     * @return CoverArray
     */
    protected function findAccesses(): CoverArray
    {
        if (!$this->accesses) {
            $this->accesses = new CoverArray();

            foreach ($this->getMapperManager()->getMapper(AccessMapper::class)->findByGroup($this) as $access) {
                $this->accesses->append($access);
            }
        }

        return $this->accesses;
    }
}