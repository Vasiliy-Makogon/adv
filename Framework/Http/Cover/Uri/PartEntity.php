<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http\Cover\Uri;

use Krugozor\Framework\Statical\Strings;

/**
 * Объект-оболочка для имени модуля или контроллера.
 */
class PartEntity
{
    /**
     * Имя модуля или контроллера в CamelCase-стиле, т.е. в виде, когда
     * разные слова записаны слитно, а каждое новое слово (включая первое)
     * записано с Большой буквы. Например:
     * "FrontendRegistration", "BackendUserEdit", "User" и т.д.
     *
     * @var string|null
     */
    private ?string $camelCaseStyle = null;

	/**
	 * @param string $uriStyle Имя модуля или контроллера в URI-стиле, т.е. в виде, когда
	 * разные слова записаны через дефис. Например:
	 * "frontend-registration", "backend-user-edit", "user" и т.д.
	 */
    public function __construct(private string $uriStyle)
    {}

    /**
     * Возвращает имя модуля или контроллера в CamelCase-стиле.
     *
     * @return string
     */
    public function getCamelCaseStyle(): string
    {
        if ($this->camelCaseStyle === null) {
            $this->camelCaseStyle = Strings::formatToCamelCaseStyle($this->uriStyle);
        }

        return $this->camelCaseStyle;
    }

    /**
     * Возвращает имя модуля или контроллера в URI-стиле.
     *
     * @return string
     */
    public function getUriStyle(): string
    {
        return $this->uriStyle;
    }
}