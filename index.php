<?php
require('./vendor/autoload.php');
require('./configuration/bootstrap.php');

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Context;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Application;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

try {
    $debugMode = 0;

    Context::getInstance()
        ->setRequest(Request::getInstance())
        ->setResponse(Response::getInstance());

    $qsDebugKey = Registry::getInstance()->get('DEBUG.QS_DEBUG_KEY');
    $enabledDebugInfo = Registry::getInstance()->get('DEBUG.ENABLED_DEBUG_INFO');
    $debugMode = Context::getInstance()
        ->getRequest()
        ->getGet($qsDebugKey, Request::SANITIZE_INT) || $enabledDebugInfo;

    $db = Mysql::create(
            Registry::getInstance()->get('DATABASE.HOST'),
            Registry::getInstance()->get('DATABASE.USER'),
            Registry::getInstance()->get('DATABASE.PASSWORD'),
            Registry::getInstance()->get('DATABASE.PORT')
        )
        ->setDatabaseName(Registry::getInstance()->get('DATABASE.NAME'))
        ->setCharset(Registry::getInstance()->get('DATABASE.CHARSET'))
        ->setStoreQueries($debugMode);

    Context::getInstance()
        ->setDatabase($db);

    $memcache = new Memcache();
    $memcache->connect('localhost', 11211);

    Context::getInstance()
        ->setMemcache($memcache);

    $memcacheFlushKey  = Registry::getInstance()->get('DEBUG.QS_MEMCACHE_FLUSH_KEY');
    $memcacheFlush = Context::getInstance()->getRequest()->getRequest($memcacheFlushKey, Request::SANITIZE_INT);
    if ($memcacheFlush) {
        Context::getInstance()->getMemcache()->flush();
    }

    $last_modify = new \DateTime("now", new DateTimeZone(date_default_timezone_get()));
    $expires = clone $last_modify;
    $expires->sub(new DateInterval('P1D'));

    // вынести
    Response::getInstance()
        ->setHeader(Response::HEADER_CONTENT_TYPE, 'text/html; charset=utf-8')
        ->setHeader(Response::HEADER_CONTENT_LANGUAGE, Registry::getInstance()->get('LOCALIZATION.LANG'))
        ->setHeader(Response::HEADER_EXPIRES, $expires->format(DATE_RFC7231))
        ->setHeader(Response::HEADER_LAST_MODIFIED, $last_modify->format(DATE_RFC7231))
        ->setHeader(Response::HEADER_CACHE_CONTROL, 'no-store, no-cache, no-transform, must-revalidate');

    $application = (new Application(Context::getInstance()))
        ->setRoutes((array) require ROUTES_PATH)
        ->run();

} catch (Throwable $t) {
    try {
        // Если НЕ ошибка СУБД или НЕ фатальная ошибки подключения к СУБД, типа:
        // 1045 - Access denied
        // 1049 - Unknown database
        // 2002 - Конечный компьютер отверг запрос на подключение
        // 2019 - Invalid character set
        // ...то кидаем исключение дальше, что бы был репорт по почте (для почтовой очереди используется СУБД).
        if (!$t instanceof MySqlException || !in_array($t->getCode(), [1045, 1049, 2002, 2019])) {
            throw $t;
        }

        // тут СУБД не доступна - пишем только в текстовой лог, см. finally
    } catch (Throwable $t) {
        try {
            $mailQueue = new MailQueue();
            $mailQueue
                ->setSendDate(new DateTime())
                ->setToEmail(Registry::getInstance()->get('EMAIL.ADMIN'))
                ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setHeader('Error on ' . Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'))
                ->setTemplate(Application::getAnchor('Local')::getPath('/Template/ErrorInfo.mail'))
                ->setMailData([
                    'date' => new DateTime(),
                    'message' => $t->getMessage(),
                    'trace' => $t->getTraceAsString(),
                    'line' => $t->getLine(),
                    'file' => $t->getFile(),
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'host' => Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'),
                    'uri' => Context::getInstance()->getRequest()?->getRequestUri()?->getSimpleUriValue()
                ]);

            (new MailQueueMapper(new MapperManager($db)))->saveModel($mailQueue);
        } catch (Throwable $t) {
            // Если СУБД доступна, но неведомая ошибка в почтовой очереди
            ErrorLog::write($t->getMessage());
            ErrorLog::write($t->getTraceAsString());
        }
    } finally {
        ErrorLog::write($t->getMessage());
        ErrorLog::write($t->getTraceAsString());

        Context::getInstance()->getResponse()
            ->setHeader(Response::HEADER_CONTENT_TYPE, 'text/plain; charset=utf-8')
            ->sendHeaders();
        echo "Временные трудности\n\n";

        if ($debugMode) {
            echo
                implode('', [$t->getMessage(), PHP_EOL, PHP_EOL]) .
                implode('', [print_r($t->getTraceAsString(), true), PHP_EOL, PHP_EOL]);
        }
    }
}