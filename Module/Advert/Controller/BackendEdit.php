<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Exception;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Controller\Trait\BackendIdValidatorTrait;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Service\CreateUserFromAdvertService;
use Krugozor\Framework\Module\Advert\Validator\CityNameInHeaderValidator;
use Krugozor\Framework\Module\Advert\Validator\TextHashValidator;
use Krugozor\Framework\Module\Category\Helper\BreadCrumbs;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\User\Validator\UserIdExistsValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;
use Throwable;

class BackendEdit extends AbstractController
{
    use BackendIdValidatorTrait;

    /** @var User владелец объявления */
    private User $user;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/advert/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->advert) {
            $this->advert = $this->getMapper(AdvertMapper::class)->createModel(
                $this->getMapper(UserMapper::class)->findModelById(User::GUEST_USER_ID)
            );
        } else {
            // Если администратор просмотрел объявление, то делаем отметку для административной части.
            if ($this->getCurrentUser()->isAdministrator() && !$this->advert->getWasModerated()) {
                $this->advert->setWasModerated(1);
                $this->advert->clearCache(['was_moderated', 'edit_date'])->save();
            }
        }

        $this->user = $this->getMapper(UserMapper::class)->findModelById(
            $this->advert->getIdUser()
        );

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet(
            'category',
            $this->getMapper(CategoryMapper::class)->findModelById($this->advert->getCategory() ?: 0)
        );

        $this->getView()->getStorage()->offsetSet('advert', $this->advert);
        $this->getView()->getStorage()->offsetSet('user', $this->user);

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $postData = $this->getRequest()->getPost('advert', PostData::class);
        $this->advert->setData($postData);

        // Добавление объектов изображений в объект объявления
        // на основе массива идентификаторов изображений из формы.
        $thumbnailsData = $this->getRequest()->getPost('thumbnail', PostData::class);
        if ($thumbnailsData->count()) {
            $this->advert->loadThumbnailsListByIds($thumbnailsData);
        }

        $validator = new Validator('common/general', 'advert/edit', 'user/common');

        if ($this->user->isGuest() and
            !$this->advert->getTelegram() && !$this->advert->getPhone() &&
            !$this->advert->getEmail()->getValue() && !$this->advert->getUrl()) {
            $validator->addError('contact_info', 'EMPTY_CONTACT_INFO');
        }

        // Проверка на затирание special-даты администратором в момент редактирования только что поданного объявления.
        if (!$this->advert->getSpecialDate() && $this->advert->getTrack()->special_date) {
            if ($this->advert->getTrack()->special_date->getTimestamp() > (new DateTime())->getTimestamp()) {
                $validator->addError('special_date', 'AFFECT_REWRITE_SPECIAL_DATE_NULL_VALUE');
                $this->advert->setSpecialDate($this->advert->getTrack()->special_date);
            }
        }

        // Проверка на затирание vip-даты администратором в момент редактирования только что поданного объявления.
        if (!$this->advert->getVipDate() && $this->advert->getTrack()->vip_date) {
            if ($this->advert->getTrack()->vip_date->getTimestamp() > (new DateTime())->getTimestamp()) {
                $validator->addError('vip_date', 'AFFECT_REWRITE_VIP_DATE_NULL_VALUE');
                $this->advert->setVipDate($this->advert->getTrack()->vip_date);
            }
        }

        $validator->addErrors($this->advert->getValidateErrors());

        if (!$this->advert->getValidateErrorsByKey('id_user')) {
            $validator->add(
                'id_user',
                (new UserIdExistsValidator($this->advert->getIdUser()))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        $validator->add(
            'text',
            (new TextHashValidator($this->advert))
                ->setMapper($this->getMapper(AdvertMapper::class))
        );

        $validator->add(
            'header',
            (new CityNameInHeaderValidator($this->advert))
                ->setMapper($this->getMapper(CityMapper::class))
        );

        $notification = $this->createNotification();

        if ($errors = $validator->validate()->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification
                ->setType(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            if (!$this->advert->getId()) {
                $this->advert->setCurrentCreateDateDiffSecond();
            } else {
                $this->advert->setEditDate(new DateTime());
            }

            // Администратор перенес объявление в другой раздел каталога,
            // после сохранения информируем об этом пользователя, если найдём его email
            $changeCategoryNotification =
                $this->advert->getId()
                && !$this->advert->getTrack()->compareValue('category', $this->advert->getCategory());

            $this->advert->deleteCache()->save();

            if ($changeCategoryNotification) {
                if ($this->advert->getIdUser() != User::GUEST_USER_ID) {
                    /** @var User $user */
                    $user = $this->getMapper(UserMapper::class)->findModelById($this->advert->getIdUser());

                    if (!$user->getEmail()->getValue() && $this->advert->getEmail()->getValue()) {
                        $user->setEmail($this->advert->getEmail());
                    }
                } else {
                    /** @var User $user */
                    $user = $this->getMapper(UserMapper::class)->createModel();
                    $user->setFirstName($this->advert->getUserName());
                    $user->setEmail($this->advert->getEmail());
                }

                if ($user->getEmail()->getValue()) {
                    $newCategoryPath = $this->getMapper(CategoryMapper::class)->loadPath(
                        $this->advert->getCategory()
                    );

                    $bread_crumbs = (new BreadCrumbs($newCategoryPath, separator: '/'))
                        ->setOnlyPlainText(true)
                        ->addFirstSeparator(false);

                    try {
                        $mailQueue = new MailQueue();
                        $mailQueue
                            ->setSendDate(new DateTime())
                            ->setToEmail($user->getEmail()->getValue())
                            ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                            ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                            ->setHeader($this->getView()->getLang()->get('mail.header.send_mail_advert_transfer'))
                            ->setTemplate($this->getRealLocalTemplatePath('AdvertTransfer'))
                            ->setMailData([
                                'bread_crumbs' => $bread_crumbs->getHtml(),
                                'user_name' => $user->getFullName(),
                                'advert_header' => $this->advert->getHeader(),
                                'hostinfo' => Registry::getInstance()->get('HOSTINFO'),
                            ]);
                        $this->getMapper(MailQueueMapper::class)->saveModel($mailQueue);
                    } catch (Exception $e) {
                        ErrorLog::write($e->getMessage());
                    }
                }
            }

            if ($this->getRequest()->getRequest('create_user', Request::SANITIZE_INT)) {
                try {
                    $createUserFromAdvertService = (new CreateUserFromAdvertService(
                        $this->advert,
                        $this->getMapperManager()
                    ))->createUser();

                    if ($email = $createUserFromAdvertService->getUser()->getEmail()->getValue()) {
                        /** @var MailQueue $mailQueue */
                        $mailQueue = $this->getMapper(MailQueueMapper::class)->createModel();
                        $mailQueue
                            ->setSendDate(new DateTime())
                            ->setToEmail($email)
                            ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                            ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                            ->setHeader($this->getView()->getLang()->get('mail.header.we_copypasta_you_advert'))
                            ->setTemplate($this->getRealLocalTemplatePath('AdvertCopypasta'))
                            ->setMailData([
                                'user' => $createUserFromAdvertService->getUser(),
                                'advert_header' => $this->advert->getHeader(),
                                'hostinfo' => Registry::getInstance()->get('HOSTINFO'),
                                'user_password' => $createUserFromAdvertService->getPassword(),
                            ]);
                        $mailQueue->save();
                    }
                } catch (Throwable $t) {
                    ErrorLog::write($t->getMessage());
                }
            }

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/advert/backend-edit/?id=' . $this->advert->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/advert/backend-main/');

            return $notification
                ->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}