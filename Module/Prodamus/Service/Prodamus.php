<?php

namespace Krugozor\Framework\Module\Prodamus\Service;

use InvalidArgumentException;
use Krugozor\Framework\Module\Advert\Controller\KassaLocation;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\PaymentActionsEnum;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Registry;

class Prodamus
{
    /** @var Advert */
    private Advert $advert;

    /** @var User */
    private User $user;

    /**
     * @param Advert $advert
     * @return $this
     */
    public function setAdvert(Advert $advert): self
    {
        $this->advert = $advert;

        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param int $action тип услуги
     * @return string
     */
    public function getMerchantUrl(int $action): string
    {
        return sprintf(
            '%s?%s',
            Registry::getInstance()->get('PRODAMUS.MERCHANT_URL'),
            http_build_query($this->getParams($action))
        );
    }

    /**
     * Возвращает "короткий" URL на действие по оплате услуги
     *
     * @param int $action
     * @return string
     * @see KassaLocation
     */
    public function getShortMerchantUrl(int $action): string
    {
        return Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT') .
            '/advert/kassa-location' .
            '/id/' . $this->advert->getId() .
            '/action/' . $action . '/';
    }

    /**
     * Возвращает массив параметров, необходимый для процедуры оплаты.
     *
     * @param int $action тип услуги
     * @return array
     */
    private function getParams(int $action): array
    {
        if ($this->advert === null) {
            throw new InvalidArgumentException(sprintf(
                '%s: Не передан объект объявления', __METHOD__
            ));
        }

        $this->checkAction($action);

        $phone = null;
        if ($this->advert->getMainPhone() && $this->user->getPhone()) {
            $phone = $this->user->getPhone();
        } elseif ($this->advert->getPhone()) {
            $phone = $this->advert->getPhone();
        }
        if ($phone) {
            $phone = preg_replace('~[^0-9+]~', '', $phone);
        }

        $email = null;
        if ($this->advert->getEmail()->getValue()) {
            $email = $this->advert->getEmail()->getValue();
        } elseif ($this->user->getEmail()->getValue()) {
            $email = $this->user->getEmail()->getValue();
        }

        $data = [
            'order_id' => $this->advert->getId() . '_' . time(),
            'products' => [
                [
                    // id товара в системе интернет-магазина
                    'sku' => $this->advert->getId(),
                    // название товара - необходимо прописать название вашего товара
                    'name' => self::getDescription($action) . $this->advert->getId(),
                    // цена за единицу товара
                    'price' => self::getPaymentCost($action),
                    // количество товара
                    'quantity' => '1',

                    // Тип оплаты, с возможными значениями (при необходимости заменить):
                    //	1 - полная предварительная оплата до момента передачи предмета расчёта;
                    //	2 - частичная предварительная оплата до момента передачи
                    //      предмета расчёта;
                    //	3 - аванс;
                    //	4 - полная оплата в момент передачи предмета расчёта;
                    //	5 - частичная оплата предмета расчёта в момент его передачи
                    //      с последующей оплатой в кредит;
                    //	6 - передача предмета расчёта без его оплаты в момент
                    //      его передачи с последующей оплатой в кредит;
                    //	7 - оплата предмета расчёта после его передачи с оплатой в кредит.
                    // (не обязательно, если не указано будет взято из настроек
                    //     Магазина на стороне системы)
                    'paymentMethod' => 1,

                    // Тип оплачиваемой позиции, с возможными
                    //     значениями (при необходимости заменить):
                    //	1 - товар;
                    //	2 - подакцизный товар;
                    //	3 - работа;
                    //	4 - услуга;
                    //	5 - ставка азартной игры;
                    //	6 - выигрыш азартной игры;
                    //	7 - лотерейный билет;
                    //	8 - выигрыш лотереи;
                    //	9 - предоставление РИД;
                    //	10 - платёж;
                    //	11 - агентское вознаграждение;
                    //	12 - составной предмет расчёта;
                    //	13 - иной предмет расчёта.
                    // (не обязательно, если не указано будет взято из настроек Магазина на стороне системы)
                    'paymentObject' => 4,
                ],
            ],

            // дополнительные данные
            'customer_extra' => self::getDescription($action) . $this->advert->getId(),

            // для интернет-магазинов доступно только действие "Оплата"
            'do' => 'pay',

            // url-адрес для возврата пользователя без оплаты
            'urlReturn' => Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/' . $this->advert->getId() . '.xhtml',

            // url-адрес для возврата пользователя при успешной оплате
            'urlSuccess' => urldecode(Registry::getInstance()->get('HOSTINFO.HOST') . '/payment/success.xhtml?advert=' . $this->advert->getId() . '&action=' . $action),

            // служебный url-адрес для уведомления интернет-магазина
            'urlNotification' => urldecode(Registry::getInstance()->get('HOSTINFO.HOST') . '/payment/result.xhtml'),

            // произвольный сквозной параметр
            '_param_action' => $action,
            '_param_advert_id' => $this->advert->getId(),
            'demo_mode' => Registry::getInstance()->get('PRODAMUS.DEMO_MODE'),

            // код системы интернет-магазина, запросить у поддержки,
            // для самописных систем можно оставлять пустым полем
            'sys' => '',

            // метод оплаты, выбранный клиентом
            // 	     если есть возможность выбора на стороне интернет-магазина,
            // 	     иначе клиент выбирает метод оплаты на стороне платежной формы
            //       варианты (при необходимости прописать значение):
            // 	AC - банковская карта
            // 	PC - Яндекс.Деньги
            // 	QW - Qiwi Wallet
            // 	WM - Webmoney
            // 	GP - платежный терминал
            'payment_method' => '',

            // тип плательщика, с возможными значениями:
            //     FROM_INDIVIDUAL - Физическое лицо
            //     FROM_LEGAL_ENTITY - Юридическое лицо
            //     FROM_FOREIGN_AGENCY - Иностранная организация
            //     (не обязательно. если форма работает в режиме самозанятого
            //      значение по умолчанию: FROM_INDIVIDUAL)
            'npd_income_type' => 'FROM_INDIVIDUAL',
        ];

        if ($phone) {
            $data['customer_phone'] = $phone;
        }

        if ($email) {
            $data['customer_email'] = $email;
        }

        $data['signature'] = self::create($data, Registry::getInstance()->get('PRODAMUS.SECRET_KEY'));

        return $data;
    }

    /**
     * Возвращает сумму для оплаты услуги.
     *
     * @param int $action
     * @return string|null
     */
    private static function getPaymentCost(int $action): ?string
    {
        self::checkAction($action);

        return match ($action) {
            PaymentActionsEnum::ACTION_ACTIVATE => Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_ACTIVATE'),
            PaymentActionsEnum::ACTION_TOP => Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_TOP'),
            PaymentActionsEnum::ACTION_SPECIAL => Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_SPECIAL'),
            default => null
        };
    }

    /**
     * Возвращает описание услуги.
     *
     * @param int $action
     * @return null|string
     */
    private static function getDescription(int $action): ?string
    {
        self::checkAction($action);

        return match ($action) {
            PaymentActionsEnum::ACTION_ACTIVATE => Registry::getInstance()->get('PAYMENTS.DESCRIPTION_ACTION_ACTIVATE'),
            PaymentActionsEnum::ACTION_TOP => Registry::getInstance()->get('PAYMENTS.DESCRIPTION_ACTION_TOP'),
            PaymentActionsEnum::ACTION_SPECIAL => Registry::getInstance()->get('PAYMENTS.DESCRIPTION_ACTION_SPECIAL'),
            default => null
        };
    }

    /**
     * Проверяет переданный action.
     *
     * @param int $action
     */
    private static function checkAction(int $action): void
    {
        if (!in_array($action, PaymentActionsEnum::all())) {
            throw new InvalidArgumentException(sprintf(
                '%s: Неизвестный тип услуги: %s', __METHOD__, $action
            ));
        }
    }

    // Код продамуса

    /**
     * @param $data
     * @param $key
     * @param string $algo
     * @return false|string
     */
    private static function create($data, $key, $algo = 'sha256')
    {
        if (!in_array($algo, hash_algos()))
            return false;
        $data = (array) $data;
        array_walk_recursive($data, function (&$v) {
            $v = strval($v);
        });
        self::_sort($data);
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $data = preg_replace_callback('/((\\\u[01-9a-fA-F]{4})+)/', function ($matches) {
                return json_decode('"' . $matches[1] . '"');
            }, json_encode($data));
        } else {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return hash_hmac($algo, $data, $key);
    }

    /**
     * @param $data
     * @param $key
     * @param $sign
     * @param string $algo
     * @return bool
     */
    public static function verify($data, $key, $sign, $algo = 'sha256')
    {
        $_sign = self::create($data, $key, $algo);
        return ($_sign && (strtolower((string) $_sign) == strtolower((string) $sign)));
    }

    /**
     * @param $data
     */
    private static function _sort(&$data)
    {
        ksort($data, SORT_REGULAR);
        foreach ($data as &$arr)
            is_array($arr) && self::_sort($arr);
    }
}