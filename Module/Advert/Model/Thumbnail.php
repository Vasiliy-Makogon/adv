<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Model;

use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Utility\Upload\DirectoryGenerator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;
use Krugozor\Framework\Validator\StringLengthValidator;
use RuntimeException;

/**
 * Принцип удаления изображений:
 * Вызывается метод @see ThumbnailMapper::unlink() - изображение отвязывается от сущности.
 * Далее в CRON-обработчике вызывается @see ThumbnailMapper::getThumbnailsNotRelatedToAdverts(),
 * для каждой модели изображения вызывается метод delete().
 *
 * @method getIdAdvert()
 * @method setIdAdvert($idAdvert)
 *
 * @method getFileName()
 * @method setFileName($fileName)
 *
 * @method DateTime getFileDate()
 * @method setFileDate($fileDate)
 */
class Thumbnail extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'db_field_name' => 'id',
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => [],
            ]
        ],

        'id_advert' => [
            'db_element' => true,
            'db_field_name' => 'id_advert',
            'validators' => [
                DecimalValidator::class => [],
            ]
        ],

        'file_name' => [
            'db_element' => true,
            'db_field_name' => 'file_name',
            'validators' => [
                IsNotEmptyValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'file_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'file_date',
            'default_value' => 'now'
        ],
    ];

    /**
     * HTTP-путь к изображению.
     *
     * @var string|null
     */
    protected ?string $full_http_path = null;

    /**
     * На основе имени файла $this->file_name (например, d2d8f9c20083bd8483ac5d5526f923b9.jpeg)
     * возвращает полный путь к файлу для HTTP, вида /d/2/d/8/f/d2d8f9c20083bd8483ac5d5526f923b9.jpeg
     *
     * @return string|null
     */
    public function getFullHttpPath(): ?string
    {
        if (!$this->getFileName()) {
            return null;
        }

        if (!$this->full_http_path) {
            $directory_generator = new DirectoryGenerator($this->getFileName());
            $this->full_http_path = $directory_generator->getHttpPath();
        }

        return $this->full_http_path . $this->getFileName();
    }

    /**
     * Удаляет файлы изображений с файловой системы и информацию о них из СУБД.
     * Метод для cron, не вызывается в клиентском коде.
     *
     * @return int
     */
    public function delete(): int
    {
        $directory_generator = new DirectoryGenerator($this->getFileName());
        $directories = [
            Registry::getInstance()->get('UPLOAD.THUMBNAIL_SMALL'),
            Registry::getInstance()->get('UPLOAD.THUMBNAIL_800x800')
        ];

        foreach ($directories as $directory) {
            $file = $directory_generator->create(DOCUMENTROOT_PATH . $directory) . $this->getFileName();
            if (!file_exists($file)) {
                continue;
            }

            if (!@unlink($file) && file_exists($file)) {
                throw new RuntimeException('Failed to delete the image file ' . $file);
            }
        }

        return parent::delete($this);
    }

    /**
     * Устанавливает связь между текущим объектом изображения и объявлением $advert.
     *
     * @param Advert $advert
     * @return mixed
     */
    public function link(Advert $advert): mixed
    {
        return $this->getMapperManager()
            ->getMapper(ThumbnailMapper::class)
            ->link($this, $advert);
    }

    /**
     * Разрывает связь между текущим объектом изображения и объявлением,
     * к которому прикреплено изображение.
     *
     * @return int|bool кол-во затронутых рядов или false при ошибке
     */
    public function unlink(): int|bool
    {
        return $this->getMapperManager()
            ->getMapper(ThumbnailMapper::class)
            ->unlink($this);
    }
}