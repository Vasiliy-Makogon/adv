<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Module\Advert\Controller\Trait\MailWithPaymentsInfoTrait;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\PaymentActionsEnum;
use Krugozor\Framework\Module\Advert\Validator\CityNameInHeaderValidator;
use Krugozor\Framework\Module\Advert\Validator\TextHashValidator;
use Krugozor\Framework\Module\Captcha\Validator\CaptchaValidator;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\Module\User\Validator\TermsOfPrivacyValidator;
use Krugozor\Framework\Module\User\Validator\TermsOfServiceValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Session;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class FrontendEditAdvert extends AbstractController
{
    use FrontendIdValidatorTrait;
    use MailWithPaymentsInfoTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            'Advert/FrontendCommon',
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
                ->setRedirectUrl($this->getRequest()->getRequest('referrer') ?: '/authorization/frontend-login/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->advert) {
            $this->advert = $this->getMapper(AdvertMapper::class)->createModel(
                $this->getCurrentUser()
            );
        }

        if (
            // Авторизованный пользователь пытается редактировать не своё объявление
            $this->getCurrentUser()->getId() !== $this->advert->getIdUser()
            // Запрещаем гостю редактировать объявления оставленные гостями
            OR
            $this->advert->getId() &&
            $this->getCurrentUser()->isGuest() &&
            $this->getCurrentUser()->getId() === $this->advert->getIdUser()
        ) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/authorization/frontend-login/')
                ->run();
        }

        if ($this->getCurrentUser()->isGuest()) {
            $this->getView()->getStorage()->offsetSet(
                'session_name',
                Session::getInstance('EDITADVERT',null, [
                    'cookie_secure' => Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                    'cookie_httponly' => session_get_cookie_params()['httponly'],
                ])->getSessionName()
            );
            $this->getView()->getStorage()->offsetSet(
                'session_id',
                Session::getInstance()->getSessionId()
            );
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        // Добавление объявления "В этот раздел" (переход по ссылке с параметрами).
        if (!$this->advert->getId()) {
            if ($id_category = $this->getRequest()->getGet('category', Request::SANITIZE_INT)) {
                $category = $this->getMapper(CategoryMapper::class)->findModelById($id_category);
                if ($category->getId()) {
                    $this->advert->setCategory($id_category);
                }
            }

            if ($id_country = $this->getRequest()->getGet('country', Request::SANITIZE_INT)) {
                $country = $this->getMapper(CountryMapper::class)->findModelById($id_country);
                if ($country->getId()) {
                    $this->advert->setPlaceCountry($country->getId());
                }
            }

            if ($id_region = $this->getRequest()->getGet('region', Request::SANITIZE_INT)) {
                $region = $this->getMapper(RegionMapper::class)->findModelById($id_region);
                if ($region->getId()) {
                    $this->advert->setPlaceRegion($region->getId());
                }
            }

            if ($id_city = $this->getRequest()->getGet('city', Request::SANITIZE_INT)) {
                $city = $this->getMapper(CityMapper::class)->findModelById($id_city);
                if ($city->getId()) {
                    $this->advert->setPlaceCity($city->getId());
                }
            }
        }

        $this->getView()->getStorage()->offsetSet(
            'category',
            $this->getMapper(CategoryMapper::class)->findModelById($this->advert->getCategory() ?: 0)
        );

        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->getStorage()->offsetSet('advert', $this->advert);

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $postData = $this->getRequest()->getPost('advert', PostData::class);
        $this->advert->setData($postData, [
            'id', 'id_user', 'unique_user_cookie_id', 'active', 'create_date', 'edit_date', 'vip_date', 'special_date',
            'view_count', 'was_moderated', 'thumbnail_count', 'hash', 'thumbnail_file_name', 'payment',
        ]);

        // Добавление объектов изображений в объект объявления
        // на основе массива идентификаторов изображений из формы.
        $thumbnailsData = $this->getRequest()->getPost('thumbnail', PostData::class);
        if ($thumbnailsData->count()) {
            $this->advert->loadThumbnailsListByIds($thumbnailsData);
        }

        $validator = new Validator('common/general', 'advert/edit', 'captcha/common', 'user/common');

        if ($this->getCurrentUser()->isGuest() &&
            !$this->advert->getTelegram() && !$this->advert->getPhone() &&
            !$this->advert->getEmail()->getValue() && (
                !$this->advert->getUrl()->getValue() or !Strings::isUrl($this->advert->getUrl()->getValue())
            ) &&
            !$this->advert->getSkype()
            OR
            !$this->getCurrentUser()->isGuest() &&
            !$this->advert->getTelegram() && !$this->advert->getPhone() &&
            !$this->advert->getEmail()->getValue() && !$this->advert->getUrl()->getValue() &&
            !$this->advert->getSkype() &&
            !$this->advert->getMainTelegram() && !$this->advert->getMainPhone() &&
            !$this->advert->getMainEmail() && !$this->advert->getMainUrl() &&
            !$this->advert->getMainSkype()
        ) {
            $validator->addError('contact_info', 'EMPTY_CONTACT_INFO');
        }

        $validator->addErrors($this->advert->getValidateErrors());

        if ($this->getCurrentUser()->isGuest()) {
            $validator->add('captcha', new CaptchaValidator([
                $this->getRequest()->getPost('captcha_code', Request::SANITIZE_STRING),
                Session::getInstance()->code
            ]));

            $validator->add('terms_of_service', new TermsOfServiceValidator(
                $this->getRequest()->getPost('terms_of_service', Request::SANITIZE_INT)
            ));

            $validator->add('terms_of_privacy', new TermsOfPrivacyValidator(
                $this->getRequest()->getPost('terms_of_privacy', Request::SANITIZE_INT)
            ));
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

        $validator->validate();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $this->getResponse()->setHttpStatusCode($postData->count() ? 422 : 400);
            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            if ($this->getCurrentUser()->isGuest()) {
                Session::getInstance()->destroy();
            }

            $this->advert->setIdUser($this->getCurrentUser()->getId());
            $this->advert->setUniqueUserCookieId($this->getCurrentUser()->getUniqueCookieId());

            /** @var Category $category */
            $category = $this->getMapper(CategoryMapper::class)->findModelById(
                $this->advert->getCategory()
            );

            // Если категория не платная, то ставим флаг, что объявление оплачено.
            if (!$category->getPaid()) {
                $this->advert->setPayment(1);
            }

            $isNewAdvert = !$this->advert->getId();
            if ($isNewAdvert) {
                $this->advert->setCurrentCreateDateDiffSecond();
            } else {
                $this->advert->setEditDate(new DateTime());
            }

            $this->advert->deleteCache()->save();

            if ($isNewAdvert) {
                $this->sendMailWithPaymentsInfo();
            }

            $merchant = $this->advert->getMerchant();

            $action_vip = $action_special = $action_payment = null;
            $notification_type = Notification::TYPE_NORMAL;
            $notification_header = null;
            $notification_message = null;

            if ($this->advert->getPayment()) {
                // Оплачены все услуги.
                /*if (!Registry::getInstance()->get('PAYMENTS.ENABLED') or
                    $this->advert->getVipDate() && $this->advert->getVipDate() > new DateTime() &&
                    $this->advert->getSpecialDate() && $this->advert->getSpecialDate() > new DateTime()
                ) {
                    $notification_message = $this->getView()->getLang()->get('notification.message.advert_save_with_payments');
                    $remove_notification_flag = true;
                // Оплачен только VIP
                } else*/ if (Registry::getInstance()->get('PAYMENTS.ENABLED') &&
                    $this->advert->getVipDate() && $this->advert->getVipDate() > new DateTime()
                ) {
                    $notification_message = $this->getView()->getLang()->get('notification.message.advert_save_with_payments');
                // Оплачено только спецпредложение
                /*} else if (Registry::getInstance()->get('PAYMENTS.ENABLED') &&
                    $this->advert->getSpecialDate() && $this->advert->getSpecialDate() > new DateTime()
                ) {
                    $notification_message = $this->getView()->getLang()->get('notification.message.advert_save_with_special');*/
                // Не оплачено ничего
                } else if (Registry::getInstance()->get('PAYMENTS.ENABLED')) {
                    $notification_message = $this->getView()->getLang()->get('notification.message.advert_save_with_special');
                }

                $notification_url = '/advert/' . $this->advert->getId() . '.xhtml';
                $action_vip = PaymentActionsEnum::ACTION_TOP;
                $action_special = PaymentActionsEnum::ACTION_SPECIAL;
            } else {
                $notification_type = Notification::TYPE_WARNING;
                $notification_header = $this->getView()->getLang()->get('notification.header.advert_need_payment');
                $notification_message = $this->getView()->getLang()->get('notification.message.advert_need_payment');
                $notification_url = '/advert/payment/id/' . $this->advert->getId();

                $action_payment = PaymentActionsEnum::ACTION_ACTIVATE;
            }

            if ($referrer = $this->getRequest()->getRequest('referrer', Request::SANITIZE_STRING)) {
                $notification_url = $referrer;
            }

            return $this->createNotification($notification_type)
                ->setHeader($notification_header)
                ->setMessage($notification_message)
                ->addParam('id', $this->advert->getId())
                ->addParam('advert_header', $this->advert->getHeader())
                ->addParam('category_name', $category->getName())
                ->setRedirectUrl($notification_url)
                ->addParam('kassa_auth_url_vip', $action_vip ? $merchant->getMerchantUrl($action_vip) : null)
                ->addParam('kassa_auth_url_special', $action_special ? $merchant->getMerchantUrl($action_special) : null)
                ->addParam('kassa_auth_url_payment', $action_payment ? $merchant->getMerchantUrl($action_payment) : null)
                ->run();
        }
    }
}