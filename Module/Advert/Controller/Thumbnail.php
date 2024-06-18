<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Exception;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Thumbnail\Factory;
use Krugozor\Framework\Utility\Upload\DirectoryGenerator;
use Krugozor\Framework\Utility\Upload\File;
use Krugozor\Framework\View;
use Krugozor\Framework\Module\Advert\Model\Thumbnail as ThumbnailModel;

class Thumbnail extends AbstractController
{
    public function run(): View
    {
        if (Request::isPost() && !empty($_FILES['file'])) {
            try {
                $upload = new File($_FILES['file']);
                $upload
                    ->setMaxFileSize(Registry::getInstance()->get('UPLOAD.MAX_FILE_SIZE'))
                    ->setAllowableMimeType('image/jpeg', 'image/gif', 'image/png', 'image/pjpeg', 'image/x-png');

                if ($upload->isFileUpload()) {
                    if ($upload->hasMimeTypeErrors()) {
                        $this->getView()->getStorage()->offsetSet('error', 'Загруженный файл имеет недопустимый mime-тип');
                    } else if ($upload->hasFileSizeErrors()) {
                        $this->getView()->getStorage()->offsetSet(
                            'error',
                            sprintf(
                                'Загруженный файл имеет недопустимый размер, допустимый размер: %s',
                                Registry::getInstance()->get('UPLOAD.MAX_FILE_SIZE')
                            )
                        );
                    }

                    if (!$this->getView()->getStorage()->get('error')) {
                        $upload
                            ->setFileNameAsUnique()
                            ->copy(DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_ORIGINAL'));

                        $directory_generator = new DirectoryGenerator($upload->getFileNameWithoutExtension());

                        $directory = $directory_generator->create(DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_SMALL'));
                        $creator = Factory::create(
                            DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_ORIGINAL') . $upload->getFileNameWithExtension(),
                            $directory . $upload->getFileNameWithoutExtension()
                        );

                        $creator->setResizedWidth(209);
                        $creator->setResizedHeight(138);
                        $creator->resizeFixed();

                        $this->getView()->getStorage()->offsetSet(
                            'path_to_image',
                            $directory_generator->getHttpPath() . $creator->getFileNameWithExt()
                        );

                        $directory = $directory_generator->create(DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_800x800'));
                        $creator = Factory::create(
                            DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_ORIGINAL') . $upload->getFileNameWithExtension(),
                            $directory . $upload->getFileNameWithoutExtension()
                        );
                        $creator->setResizedWidth(800);
                        $creator->setResizedHeight(800);
                        $creator->resize();

                        @unlink(DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_ORIGINAL') . $upload->getFileNameWithExtension());

                        /** @var ThumbnailModel $file */
                        $file = $this->getMapper(ThumbnailMapper::class)->createModel();
                        $file->setFileName($creator->getFileNameWithExt());
                        $this->getMapper(ThumbnailMapper::class)->saveModel($file);
                        $this->getView()->getStorage()->offsetSet('thumbnail_id', $file->getId());
                    }
                } else {
                    if ($upload->hasFileSizeErrors()) {
                        $this->getView()->getStorage()->offsetSet(
                            'error',
                            sprintf(
                                'Файл не был загружен, т.к. имеет недопустимый размер, допустимый размер: %s',
                                Registry::getInstance()->get('UPLOAD.MAX_FILE_SIZE')
                            )
                        );
                    }
                }
            } catch (Exception $e) {
                @unlink(DOCUMENTROOT_PATH . Registry::getInstance()->get('UPLOAD.THUMBNAIL_ORIGINAL') . $upload->getFileNameWithExtension());

                $this->getView()->getStorage()->offsetSet('error', $e->getMessage());
            }
        }

        return $this->getView();
    }
}