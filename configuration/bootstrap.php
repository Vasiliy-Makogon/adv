<?php

use Krugozor\Framework\Registry;

define('TIME_START', microtime(true));
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * DOCUMENT ROOT проекта.
 *
 * @var string
 */
define('DOCUMENTROOT_PATH', dirname(dirname(__FILE__)));

/**
 * Путь к файлу URL-маршрутов.
 *
 * @var string
 */
const ROUTES_PATH = DOCUMENTROOT_PATH . '/configuration/routes.php';

try {
    Registry::getInstance(implode(
        DIRECTORY_SEPARATOR, [DOCUMENTROOT_PATH, 'configuration', 'config.ini']
    ));
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

ini_set('display_errors', Registry::getInstance()->get('DEBUG.DISPLAY_ERRORS'));
ini_set('display_startup_errors', Registry::getInstance()->get('DEBUG.DISPLAY_STARTUP_ERRORS'));

// Это есть в htaccess
// mb_language('Russian');
// mb_internal_encoding('UTF-8');

// ls /usr/share/locale | grep 'ru'
// locale -a -m
call_user_func_array(
    'setlocale',
    array_merge([LC_ALL], Registry::getInstance()->get('LOCALIZATION.LOCALES')->getDataAsArray())
);

date_default_timezone_set(Registry::getInstance()->get('LOCALIZATION.TIMEZONE'));

// Обновляем пути на актуальные, с учетом текущего DOCUMENTROOT_PATH
Registry::getInstance()->get('PATH')->mapAssociative(function ($key, $value) {
    Registry::getInstance()->get('PATH')->setData([$key => DOCUMENTROOT_PATH . DIRECTORY_SEPARATOR . $value]);
});

ini_set('error_log', Registry::getInstance()->get('PATH.PHP_ERROR_LOG'));