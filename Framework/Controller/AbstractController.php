<?php

declare(strict_types=1);

namespace Krugozor\Framework\Controller;

use Exception;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Application;
use Krugozor\Framework\Authorization;
use Krugozor\Framework\Context;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\View;
use RuntimeException;

abstract class AbstractController
{
    /**
     * Имя класса представления по умолчанию.
     * Если необходимо задать иное имя класса представления, то оно задается вторым аргументом
     * метода $this->getView() или явно в классе-наследнике.
     *
     * @var string
     */
    protected string $default_view_class_name = View::class;

    /**
     * Объект представления.
     *
     * @var View|null
     */
    private ?View $view = null;

    /**
     * Объект текущего пользователя.
     *
     * @var User|null
     */
    private ?User $currentUser = null;

    /**
     * Менеджер Мэпперов.
     *
     * @var MapperManager|null
     */
    private ?MapperManager $mapperManager = null;

    /**
     * Основной рабочий метод любого конкретного класса контроллера.
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * @param Context $context Звёздный объект-хранилище, содержащий все основные объекты системы
     * @throws MySqlException
     */
    public function __construct(private Context $context)
    {
        if (!isset(static::$disableAuthorization)) {
            $auth = new Authorization($this->getRequest(), $this->getResponse());
            $auth->processSettingsUniqueCookieId($this->getCurrentUser());

            if ($notificationId = $this->getRequest()->getCookie(
                Notification::NOTIFICATION_PARAM_NAME, Request::SANITIZE_INT
            )) {
                $notification = new Notification($this->context->getDatabase());
                $notification->findById($notificationId);

                if ($notification->getId()) {
                    $this->getView()->setNotification($notification);

                    $this->getResponse()->setCookie(
                        Notification::NOTIFICATION_PARAM_NAME,
                        '',
                        time() - 3600,
                        '/',
                        Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                        (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                        session_get_cookie_params()['httponly']
                    );
                }
            }
        }

        if ($this->langs()) {
            $this->getView()->getLang()->loadI18n(...$this->langs());
        }
    }

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [];
    }

    /**
     * Возвращает объект запроса.
     *
     * @return Request|null
     */
    final protected function getRequest(): ?Request
    {
        return $this->context->getRequest();
    }

    /**
     * Возвращает объект ответа.
     *
     * @return Response|null
     */
    final protected function getResponse(): ?Response
    {
        return $this->context->getResponse();
    }

    /**
     * Создает новый объект системного уведомления.
     *
     * @param string $type
     * @return Notification
     */
    final protected function createNotification(string $type = Notification::TYPE_NORMAL): Notification
    {
        return (new Notification($this->context->getDatabase()))->setType($type);
    }

    /**
     * @return MapperManager
     */
    final protected function getMapperManager(): MapperManager
    {
        if ($this->mapperManager === null) {
            $this->mapperManager = new MapperManager($this->context->getDatabase());
        }

        return $this->mapperManager;
    }

    /**
     * @param string
     * @return AbstractMapper
     */
    final protected function getMapper(string $path): AbstractMapper
    {
        return $this->getMapperManager()->getMapper($path);
    }

    /**
     * Возвращает объект текущего пользователя.
     *
     * @return User
     * @throws MySqlException
     */
    final protected function getCurrentUser(): User
    {
        if ($this->currentUser === null) {
            $auth = new Authorization(
                $this->getRequest(),
                $this->getResponse(),
                $this->getMapper(UserMapper::class)
            );

            $this->currentUser = $auth->processAuthentication();
        }

        return $this->currentUser;
    }

    /**
     * Проверяет доступ текущего пользователя на доступ к контроллеру $controller_key модуля $module_key.
     * Если $controller_key или $module_key не указаны, берутся текущие модуль и/или контроллер.
     *
     * @param null|string $module_key
     * @param null|string $controller_key
     * @return bool
     */
    final protected function checkAccess(?string $module_key = null, ?string $controller_key = null): bool
    {
        $module_key = $module_key ?: $this->getRequest()->getModuleName()->getCamelCaseStyle();
        $controller_key = $controller_key ?: $this->getRequest()->getControllerName()->getCamelCaseStyle();

        return $this->currentUser->checkAccesses($module_key, $controller_key);
    }

    /**
     * Возвращает объект представления.
     * Если представление ещё не создано, оно создается на основе двух параметров -
     * имени файла шаблона и имени класса представления.
     *
     * @param string|null $template Имя файла шаблона или null если использовать шаблон по имени контроллера.
     *                              Шаблоны ищутся исключительно в рамках текущего модуля и менять это поведение не нужно.
     * @param string|null $view_class_name Имя файла класса представления или null если использовать класс вида по умолчанию.
     * @return View
     */
    protected function getView(?string $template = null, ?string $view_class_name = null): View
    {
        if ($this->view === null) {
            try {
                if ($view_class_name) {
                    // Дергаем __autoload
                    class_exists($view_class_name);
                }
            } catch (Exception) {
                throw new RuntimeException(sprintf(
                    '%s: Не найден класс вида %s для контроллера %s',
                    __METHOD__,
                    $view_class_name,
                    get_class($this)
                ));
            }

            $view_class_name = $view_class_name ?: $this->default_view_class_name;
            $this->view = new $view_class_name($this->getRealLocalTemplatePath($template));
            $this->view->setDebugInfoFlag((bool) Registry::getInstance()->get('DEBUG.ENABLED_DEBUG_INFO'));
        }

        return $this->view;
    }

    /**
     * Определяет полный путь к локальному файлу шаблона, находящегося в рамках текущего модуля.
     *
     * Если параметр $template не определен, то файл шаблона ищется в
     * Krugozor/Module/ТекущийМодуль/Template/ТекущийКонтроллер.*
     * Если параметр $template определен, то файл шаблона ищется в
     * Krugozor/Module/ТекущийМодуль/Template/$template.*
     *
     * @param null|string $template имя файла шаблона или NULL если использовать шаблон текущего контроллера
     * @return null|string
     */
    final protected function getRealLocalTemplatePath(?string $template = null): ?string
    {
        $anchor = Application::getAnchor($this->getRequest()->getModuleName()->getUriStyle());

        if ($template === null) {
            $template_file_paths = [
                $anchor::getpath(), 'Template', $this->getRequest()->getControllerName()->getCamelCaseStyle()
            ];
        } else {
            $template_file_paths = [$anchor::getpath(), 'Template', $template];
        }

        foreach (array('.phtml', '.mail') as $ext) {
            $file = implode(DIRECTORY_SEPARATOR, $template_file_paths) . $ext;

            if (file_exists($file)) {
                return $file;
            }
        }

        // Тут шаблон явно указан и файл не найден - это ошибка.
        if ($template !== null) {
            throw new RuntimeException(sprintf(
                '%s: Не найден шаблон `%s.*`', __METHOD__, $template
            ));
        }

        // Тут шаблон не указан и не найден - представление не нуждается в шаблоне.
        // Данная ситуация нужна, например, для контроллера, который что-то делает
        // и возвращает в Application только объект Notification.
        return null;
    }
}