<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use Exception;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Uri\PartEntity;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Captcha\Model\Captcha;
use Krugozor\Framework\Module\Resource\Model\AbstractResource;
use Krugozor\Framework\Statical\Arrays;
use Krugozor\Framework\Statical\Strings;
use RuntimeException;

final class Application
{
    /**
     * Массив допустимых URL-адресов проекта в виде
     * массивов регулярных выражений (см. /config/routes.php).
     *
     * @var array
     */
    private array $routes = [];

    /**
     * @param Context $context
     */
    public function __construct(private Context $context)
    {}

    /**
     * Принимает массив допустимых маршрутов URL.
     *
     * @param array $routes
     * @return static
     */
    public function setRoutes(array $routes): static
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Основной метод приложения, запускающий на основе $requestUri контроллер и отдающий в output результат.
     *
     * @return mixed
     * @throws Exception
     */
    public function run(): void
    {
        if (!$this->compareRequestWithUriRoutes()) {
            if (!$this->compareRequestWithStandardUriMap()) {
                $this->context->getRequest()->setModuleName(new PartEntity('not-found'));
                $this->context->getRequest()->setControllerName(new PartEntity('not-found'));
            }
        }

        $controllerClassName = self::getControllerClassName(
            $this->context->getRequest()->getModuleName()->getCamelCaseStyle(),
            $this->context->getRequest()->getControllerName()->getCamelCaseStyle()
        );

        /** @var AbstractController $controller */
        $controller = new $controllerClassName($this->context);
        $result = $controller->run();

        if (!is_object($result)) {
            throw new RuntimeException(sprintf(
                '%s: Не получен результат от работы контроллера %s', __METHOD__ , $controllerClassName
            ));
        }

        switch ($result) {
            case $result instanceof View:
                $result->run();
                $this->context->getResponse()->sendCookie()->sendHeaders();
                $debugKey = Registry::getInstance()->get('DEBUG.QS_DEBUG_KEY');
                $trimmer = !(
                    !empty($debugKey) && $this->context->getRequest()->getRequest($debugKey) ||
                    Registry::getInstance()->get('DEBUG.ENABLED_DEBUG_INFO')
                );
                echo $result->getOutput($trimmer);
                break;

            case $result instanceof Notification:
                $this->context->getResponse()
                    ->setHeader(Response::HEADER_LOCATION, $result->getRedirectUrl())
                    ->setCookie(
                        Notification::NOTIFICATION_PARAM_NAME,
                        (string) $result->getLastInserId() ?: '',
                        0,
                        '/',
                        Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                        (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                        session_get_cookie_params()['httponly']
                    )
                    ->sendCookie()
                    ->sendHeaders();
                break;

            case $result instanceof Captcha:
                $this->context->getResponse()->sendHeaders();
                $result->showCaptcha();
                break;

            case $result instanceof Response:
                $this->context->getResponse()->sendCookie()->sendHeaders();
                break;

            case $result instanceof AbstractResource:
                $this->context->getResponse()->sendCookie()->sendHeaders();
                echo $result->getResourceContents();
                break;

            default:
                throw new RuntimeException(sprintf(
                    '%s: Результат от работы контроллера не определён', __METHOD__
                ));
        }
    }

    /**
     * Возвращает Anchor-объект модуля $moduleName.
     *
     * @param string $moduleName
     * @return AbstractAnchor
     */
    final public static function getAnchor(string $moduleName): AbstractAnchor
    {
        $anchor = sprintf(
            'Krugozor\Framework\Module\%s\Anchor',
            Strings::formatToCamelCaseStyle($moduleName)
        );
        if (!class_exists($anchor)) {
            throw new RuntimeException(sprintf(
                "%s: Not found Anchor-file at path '%s'", __METHOD__, $anchor
            ));
        }

        return new $anchor;
    }

    /**
     * Разбирает текущий URI-запрос, который передается в качестве аргумента,
     * и сравнивает его с одним из паттернов URL-карты $this->routes.
     * Если совпадение найдено, то в объект-оболочку Request записывается информация
     * из карт, такая как:
     * - имя модуля
     * - имя контролера
     * - запрошеный URI-адрес
     * - параметры запроса.
     *
     * @return boolean true если для текущего HTTP-запроса найдены совпадения в
     * $this->routes и false в противном случае.
     */
    private function compareRequestWithUriRoutes(): bool
    {
        if (!$canonicalRequestUri = $this->context->getRequest()->getCanonicalRequestUri()->getSimpleUriValue()) {
            return false;
        }

        foreach ($this->routes as $map) {
            if (preg_match($map['pattern'], $canonicalRequestUri, $params)) {
                array_shift($params);

                foreach ($params as $index => $value) {
                    $this->context->getRequest()->getRequest()->{$map['aliases'][$index]} = $value;
                }

                if (!empty($map['default']) && is_array($map['default'])) {
                    foreach ($map['default'] as $key => $value) {
                        $this->context->getRequest()->getRequest()->{$key} = $value;
                    }
                }

                if (class_exists(self::getControllerClassName(
                    Strings::formatToCamelCaseStyle($map['module']),
                    Strings::formatToCamelCaseStyle($map['controller'])
                ), true)) {
                    $this->context->getRequest()->setModuleName(new PartEntity($map['module']));
                    $this->context->getRequest()->setControllerName(new PartEntity($map['controller']));

                    return true;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * По символу "/" разбирает REQUEST_URI таким образом, что четное число получившихся при разборе
     * значений образуют пары вида "ключ" => "значение". Данные пары помещаются в Request.
     * Первая пара является именем модуля и именем контроллера. Например, URI-запрос вида:
     *
     * /ajax/region/country/155
     *
     * метод распарсит таким образом, что при наличие соответствующего класса, в @see Request будет помещена
     * информация о текущем модуле Ajax, контроллере Region и переменной запроса country со значением 155.
     *
     * @return bool true если для запроса $uri найден контроллер и false в противном случае.
     */
    private function compareRequestWithStandardUriMap(): bool
    {
        if (!$canonicalRequestUri = $this->context->getRequest()->getCanonicalRequestUri()->getSimpleUriValue()) {
            return false;
        }

        $uriParts = explode('/', trim($canonicalRequestUri, ' /'));
        $countParams = count($uriParts);

        if ($countParams % 2) {
            return false;
        }

        for ($i = 0; $i < $countParams; $i++) {
            $params[$uriParts[$i]] = $uriParts[++$i];
        }

        $first_element = Arrays::array_kshift($params);
        $module = key($first_element);
        $controller = current($first_element);

        if (class_exists(self::getControllerClassName(
            Strings::formatToCamelCaseStyle($module),
            Strings::formatToCamelCaseStyle($controller)
        ), true)) {
            $this->context->getRequest()->setModuleName(new PartEntity($module));
            $this->context->getRequest()->setControllerName(new PartEntity($controller));
            $this->context->getRequest()->getRequest()->setData($params);

            return true;
        }

        return false;
    }

    /**
     * Возвращает полное имя класса контроллера.
     *
     * @param string $module имя модуля
     * @param string $controller имя контроллера
     * @return string полное имя класса контроллера
     */
    private static function getControllerClassName(string $module, string $controller): string
    {
        return sprintf(
            'Krugozor\Framework\Module\%s\Controller\%s',
            ucfirst($module),
            ucfirst($controller)
        );
    }
}