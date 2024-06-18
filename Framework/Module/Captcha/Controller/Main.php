<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Captcha\Controller;

use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Captcha\Model\Captcha;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Session;
use RuntimeException;

class Main extends AbstractController
{
    public function run(): Captcha
    {
        $this->getResponse()->setHeader(Response::HEADER_CONTENT_TYPE, 'image/png');

        // Поскольку инициировать капчу могут разные страницы сайта, то нужно четко определить,
        // какую именно сессию использовать, ибо браузер при наличии, например, двух сессий
        // пошлёт запрос вида
        // Cookie: EDITADVERT=1rpjjlhunku0dvtpd8iiprbv68hdn06l; CAPTCHASID=0cpgcvjo5njeipm4pqgltjsqhjsd4iff
        // Передавая имя и id сессии в GET-параметре, мы знаем, какую именно сессию запустить.
        $session_name = $this->getRequest()->getGet('session_name', Request::SANITIZE_STRING);
        $session_id = $this->getRequest()->getGet('session_id', Request::SANITIZE_STRING);

        if (empty($session_name)) {
            throw new RuntimeException(sprintf(
                '%s: Отсутствует сессия', __METHOD__
            ));
        }

        $session = Session::getInstance($session_name, $session_id, [
            'cookie_secure' => Registry::getInstance()->get('SECURITY.USE_HTTPS'),
            'cookie_httponly' => session_get_cookie_params()['httponly']
        ]);

        $captcha = new Captcha(Registry::getInstance()->get('PATH.CAPCHA_FONT'));
        $session->code = $captcha->getCode();
        $captcha->create();

        return $captcha;
    }
}