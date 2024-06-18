<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\View\InternationalizationReader\InternationalizationErrorMessagesReader;

final class Validator
{
    /** @var InternationalizationErrorMessagesReader|null */
    protected ?InternationalizationErrorMessagesReader $i18nErrorMessagesReader = null;

    /**
     * Массив описания ошибок из текстовых файлов.
     *
     * @var CoverArray
     */
    private CoverArray $i18nErrorMessages;

    /**
     * Массив валидаторов.
     *
     * @var array
     */
    private array $validators = [];

    /**
     * Ошибки, возвращённые валидаторами.
     *
     * @var array
     */
    private array $errors = [];

    /**
     * Принимает неограниченное количество параметров - строк, которые являются путями
     * к файлам интернационализации описания ошибок. Каждая строка имеет вид `ModuleName/fileName`, где
     * ModuleName - имя модуля
     * fileName   - имя php-файла описания ошибок валидаций (без расширения *.php)
     *
     * @param string ...$args
     */
    public function __construct(string ...$args)
    {
        $this->i18nErrorMessages = new CoverArray();
        $this->i18nErrorMessagesReader = new InternationalizationErrorMessagesReader($this->i18nErrorMessages);
        $this->loadI18n(...$args);
    }

    /**
     * @param string ...$args
     * @return $this
     */
    public function loadI18n(string ...$args): self
    {
        $this->i18nErrorMessagesReader->loadI18n(...$args);

        return $this;
    }

    /**
     * Добавляет валидатор $validator под ключом $key в коллекцию валидаторов.
     *
     * @param string $key ключ, соответствующий имени проверяемого свойства
     * @param AbstractValidator $validator конкретный валидатор
     * @return Validator
     */
    final public function add(string $key, AbstractValidator $validator): self
    {
        if (!isset($this->validators[$key])) {
            $this->validators[$key] = [];
        }

        $this->validators[$key][] = $validator;

        return $this;
    }

    /**
     * Проходит по всем валидаторам, добавленным в данный класс, поочерёдно производя валидацию каждого из них.
     * Если валидатор не проходит валидацию, т.е. есть ошибки, метод помещает в массив $this->errors пару ключ => значение,
     * где ключ - ключ, соответствующий имени проверяемого свойства, а значение - массив информации об ошибке.
     *
     * @return $this
     */
    final public function validate(): self
    {
        foreach ($this->validators as $key => $validators) {
            foreach ($validators as $validator) {
                if (!$validator->validate()) {
                    $this->errors[$key][] = $validator->getError();

                    if ($validator->getBreak()) {
                        break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Добавляет ошибку, имитируя добавленную ошибку валидатора.
     *
     * @param string $user_key ключ возвращаемого значения
     * @param string $error_key ключ ошибки из файлов описания ошибок
     * @param array $placeholders ассоциативный массив аргументов-заполнителей
     * @return Validator
     */
    final public function addError(string $user_key, string $error_key, array $placeholders = []): self
    {
        $this->errors[$user_key][] = array($error_key, $placeholders);

        return $this;
    }

    /**
     * Добавляет ошибки модели.
     *
     * @param AbstractModel $model
     * @return $this
     */
    final public function addModelErrors(AbstractModel $model): self
    {
        $this->addErrors($model->getValidateErrors());

        return $this;
    }

    /**
     * Добавляет ошибки (возвращенные моделью).
     *
     * @param array $errors
     * @return Validator
     */
    final public function addErrors(array $errors = []): self
    {
        foreach ($errors as $key => $data) {
            foreach ($data as $params) {
                $this->addError($key, $params[0], $params[1]);
            }
        }

        return $this;
    }

    /**
     * Возвращает конечный массив с человекопонятными сообщениями об ошибках.
     *
     * @return array
     */
    final public function getErrors(): array
    {
        $output = [];

        if ($this->errors) {
            foreach ($this->errors as $key => $errors) {
                $output[$key] = [];

                foreach ($errors as $id => $params) {
                    if (empty($this->i18nErrorMessages[$params[0]])) {
                        trigger_error("Не найдено описание ключа ошибки $params[0]", E_USER_WARNING);

                        $output[$key][$id] = $params[0];
                    } else {
                        $output[$key][$id] = Strings::createMessageFromParams(
                            $this->i18nErrorMessages[$params[0]], $params[1]
                        );
                    }
                }
            }
        }

        return $output;
    }
}