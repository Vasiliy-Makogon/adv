<?php

declare(strict_types=1);

namespace Krugozor\Framework\View\InternationalizationReader;

use Krugozor\Framework\Application;
use Krugozor\Framework\Registry;

class InternationalizationErrorMessagesReader extends AbstractInternationalizationMessagesReader
{
    /**
     * @param string $module
     * @param string $fileName
     * @return string
     */
    protected function getPathToI18nFile(string $module, string $fileName): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            Application::getAnchor($module)::getPath(),
            'i18n',
            Registry::getInstance()->get('LOCALIZATION.LANG'),
            'validator',
            strtolower($fileName)
        ]) . '.php';
    }
}