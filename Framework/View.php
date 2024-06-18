<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use Exception;
use InvalidArgumentException;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Helper\Form;
use Krugozor\Framework\Helper\Format;
use Krugozor\Framework\Module\Resource\Model\ResourceCss;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\View\Lang;
use Krugozor\Framework\Http\Request;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class View
{
    /** @var CoverArray Данные view */
    protected CoverArray $data;

    /** @var CoverArray Ошибки post-запросов или валидации моделей */
    protected CoverArray $errors;

    /** @var Lang Хранилище данных файлов интернационализации */
    protected Lang $lang;

    /** @var string Сгенерированный HTML */
    protected string $output;

    /** @var User|null $currentUser Текущий авторизованный пользователь */
    protected ?User $currentUser = null;

    /** @var array Массив объектов-хелперов, работающих с view */
    protected array $helpers = [];

    /** @var Notification|null Объект уведомлений */
    protected ?Notification $notification = null;

    /** @var bool  Разрешено ли выводить отладочную информацию внизу страницы */
    private bool $enabled_debug_info = false;

    /**
     * @param string|null $template_file Путь до файла шаблона
     */
    public function __construct(protected ?string $template_file = null)
    {
        $this->data = new CoverArray();
        $this->errors = new CoverArray();
        $this->lang = new Lang();
    }

    /**
     * Возвращает объект запроса.
     * Это лишь краткая форма записи получения данных запроса из View.
     * Данный метод необходим, т.к. зачастую в шаблонах необходимо иметь
     * данные о параметрах запроса, URL адресе и т.п.
     *
     * @return Request
     */
    final public function getRequest(): Request
    {
        return Context::getInstance()->getRequest();
    }

    /**
     * В некоторых случаях не достаточно вызова магических методов в контексте контроллера, что бы
     * работать полноценно с хранилищем View.
     * Например, в ситуации с JSON-ответом, когда ответ должен состоять из объекта данных списочного типа.
     * В этом случае в контроллере вызывается
     * $this->getView()->getStorage()->clear()->setData($data);
     * что бы можно было пользоваться всеми возможностями CoverArray
     *
     * @return CoverArray
     */
    final public function getStorage(): CoverArray
    {
        return $this->data;
    }

    /**
     * Возвращает элемент из хранилища данных self::data.
     * Этот метод - синтаксический сахар над self::getStorage()
     *
     * @param string $key
     * @return mixed
     * @deprecated удалить
     */
    final public function __get(string $key): mixed
    {
        return $this->getStorage()->get($key);
    }

    /**
     * Добавляет новый элемент в хранилище данных self::data.
     * Этот метод - синтаксический сахар над self::getStorage()
     *
     * @param string $key
     * @param mixed $value
     * @deprecated удалить
     */
    final public function __set(string $key, mixed $value): void
    {
        $this->getStorage()->$key = $value;
    }

    /**
     * Возвращает объект данных интернационализации.
     *
     * @return Lang
     */
    final public function getLang(): Lang
    {
        return $this->lang;
    }

    /**
     * @param User $currentUser
     * @return $this
     */
    final public function setCurrentUser(User $currentUser): self
    {
        $this->currentUser = $currentUser;

        return $this;
    }

    /**
     * @return User|null
     */
    final public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }

    /**
     * Возвращает объект данных ошибок (обычно, POST-запроса).
     *
     * @return CoverArray
     */
    final public function getErrors(): CoverArray
    {
        return $this->errors;
    }

    /**
     * Возвращает объект-хэлпер $helper_name.
     *
     * @param mixed ...$args
     * @return mixed
     * @throws ReflectionException
     * @todo
     */
    final public function getHelper(...$args): mixed
    {
        if (!func_num_args()) {
            throw new InvalidArgumentException(
                'Попытка вызвать метода ' . __METHOD__ . ' без указания класса-помощника'
            );
        }

        $helper_name = array_shift($args);

        switch ($helper_name) {
            // Для хэлпера форм указываем шаблон описания ошибок заполнения полей.
            case Form::class:
            case '\Krugozor\Framework\Helper\Form': // @todo удалить
                if (!isset($this->helpers[$helper_name])) {
                    $this->helpers[$helper_name] = Form::getInstance();
                    $this->helpers[$helper_name]->setFieldErrorTemplate(
                        $this->getRealTemplatePath('Local/FieldError')
                    );
                }
                return $this->helpers[$helper_name];

            default:
                if (!class_exists($helper_name)) {
                    throw new InvalidArgumentException(
                        __METHOD__ . ": Попытка вызвать неизвестный класс-помощник $helper_name"
                    );
                } else {
                    if (!isset($this->helpers[$helper_name])) {
                        $cls = new ReflectionClass($helper_name);

                        // Если хэлпер Singelton, то сохраняем его в хранилище
                        // иначе - просто инстанцируем, возвращаем и "забываем" о нем.
                        if ($cls->hasMethod('getInstance')) {
                            $method = $cls->getMethod('getInstance');

                            if ($method->isStatic()) {
                                $this->helpers[$helper_name] = call_user_func_array(
                                    array($cls->getName(), 'getInstance'),
                                    $args
                                );
                            }
                        } else {
                            return $cls->newInstanceArgs($args);
                        }
                    }

                    return $this->helpers[$helper_name];
                }
        }
    }

    /**
     * Создаёт и возвращает HTML-код на основании текущего шаблона и данных,
     * присутствующих в текущем представлении $this->data.
     */
    final public function run()
    {
        if (!$this->template_file || !file_exists($this->template_file)) {
            throw new RuntimeException(sprintf(
                '%s: Не найден или явно не указан шаблон вида `%s`',
                __METHOD__,
                $this->template_file
            ));
        }

        // Если в шаблоне будет вызван код, генерирующий исключения (например, в методе $this->getRealTemplatePath()),
        // то отлавливаем и бросаем его дальше, в Krugozor\Framework\Application::run().
        try {
            ob_start();
            require $this->template_file;
            $this->output = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * @param bool $trim true, если очищать от HTML от лишних пробельных символов
     * @return string
     */
    public function getOutput($trim = true): string
    {
        if ($trim) {
            $this->output = Format::cleanHtml($this->output);
        }

        return $this->output;
    }

    /**
     * @param Notification $notification
     * @return $this
     */
    final public function setNotification(Notification $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * @return Notification|null
     */
    final public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    /**
     * Возвращает путь до CSS-файла.
     * Если $module и $path не указаны, возвращается ресурс по имени модуля и контроллера.
     *
     * @param string|null $module
     * @param string|null $path
     * @return string
     */
    final public function getCss(?string $module = null, ?string $path = null): string
    {
        if (!$module && !$path) {
            $full_path = $this->getPageId('/') . '.css';
        } else {
            $full_path = "$module/$path";
        }

        return '<link rel="stylesheet" href="/css/' . $full_path . '" type="text/css">' . PHP_EOL;
    }

    /**
     * @param array $options массив опций, пример:
     * [
     *   'local' => ['reset.css', 'tags.css', 'classes.css', 'structure.css'],
     *   'help' => ['help.css'],
     * ]
     * @return string
     */
    public function compileCss(array $options): string
    {
        return
            '<link rel="stylesheet" href="/ccss/' .
            ResourceCss::createCompileResourceFileNameByOptions($options) .
            '" type="text/css">' . PHP_EOL;
    }

    /**
     * Возвращает путь до JS-файла.
     * Если $module и $path не указаны, возвращается ресурс по имени модуля и контроллера.
     *
     * @param string|null $module
     * @param string|null $path
     * @return string
     */
    final public function getJs(?string $module = null, ?string $path = null): string
    {
        if (!$module && !$path) {
            $full_path = $this->getPageId('/') . '.js';
        } else {
            $full_path = "$module/$path";
        }

        return '<script src="/js/' . $full_path . '" defer></script>' . PHP_EOL;
    }

    /**
     * Возвращает строковой ID в виде строки `модуль_контроллер`, например: `advert_frontend-category-list` где
     * advert - имя модуля \Krugozor\Framework\Module\Advert,
     * frontend-category-list - имя контроллера \Krugozor\Framework\Module\Advert\Controller\FrontendCategoryList
     *
     * @param string $separator сепаратор между значениями модуля и контроллера
     * @return string
     */
    final public function getPageId(string $separator = '_'): string
    {
        return
            $this->getRequest()->getModuleName()->getUriStyle() .
            $separator .
            $this->getRequest()->getControllerName()->getUriStyle();
    }

    /**
     * Устанавливает файл шаблона.
     * Метод применяется в случаях, когда необходимо переустановить автоматически определенный файл шаблона.
     *
     * @param string $template_file
     * @return $this
     */
    final public function setTemplateFile(string $template_file): self
    {
        $this->template_file = $template_file;

        return $this;
    }

    /**
     * Устанавливает значение для $this->enabled_debug_info.
     *
     * @param bool $value
     * @return View
     */
    final public function setDebugInfoFlag(bool $value = false): self
    {
        $this->enabled_debug_info = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledDebugInfo(): bool
    {
        return $this->enabled_debug_info;
    }

    /**
     * Метод принимает строку $path вида 'ИмяМодуля/ИмяШаблона' и возвращает
     * "реальный" (физический) путь к шаблону. Пример:
     *
     * <html>
     * <?php include $this->getRealTemplatePath('Common/Navigation') ?>
     * </html>
     *
     * @param string $path абстрактный путь до файла шаблона
     * @return string физический путь к файлу шаблона
     */
    final protected function getRealTemplatePath(string $path): string
    {
        list($module, $file) = explode('/', $path);

        if (!$module) {
            throw new RuntimeException(sprintf(
                "%s: Не указан модуль при подключении второстепенного шаблона `%s`",
                __METHOD__,
                $path
            ));
        }

        if (!$file) {
            throw new RuntimeException(sprintf(
                "%s: Не указан файл при подключении второстепенного шаблона `%s`",
                __METHOD__,
                $path
            ));
        }

        $path = implode(DIRECTORY_SEPARATOR, [
            Application::getAnchor($module)::getPath(),
            'Template',
            $file
        ]) . '.phtml';

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(
                "%s: Не найден подключаемый файл второстепенного шаблона `%s`",
                __METHOD__,
                $path
            ));
        }

        return $path;
    }
}