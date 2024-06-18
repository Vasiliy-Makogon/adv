#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Application;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/** @var int кол-во месяцев, за которые удалить объявления */
const MONTHS_COUNT = 12 * 4;

/**
 * Удаление устаревших объявлений анонимных пользователей, старших MONTHS_COUNT.
 */
try {
    require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');
    require(dirname(dirname(__FILE__)) . '/configuration/bootstrap.php');

    $db = Mysql::create(
        Registry::getInstance()->get('DATABASE.HOST'),
        Registry::getInstance()->get('DATABASE.USER'),
        Registry::getInstance()->get('DATABASE.PASSWORD')
    )->setDatabaseName(Registry::getInstance()->get('DATABASE.NAME'))
        ->setCharset(Registry::getInstance()->get('DATABASE.CHARSET'));

    $mapper = new AdvertMapper(new MapperManager($db));

    if ($titles = $mapper->deleteNonActualGuestAdverts(MONTHS_COUNT)) {
        $logMessage = sprintf(
            "Deleted %s old adverts:\n\n%s\n",
            count($titles),
            implode(PHP_EOL, $titles)
        );
        $logDate = (new DateTime())->formatAsMysqlDatetime();
        echo sprintf('%s - %s', $logDate, $logMessage);
    }
} catch (Throwable $t) {
    ErrorLog::write($t->getMessage());

    $mailQueue = new MailQueue();
    $mailQueue
        ->setSendDate(new DateTime())
        ->setToEmail(Registry::getInstance()->get('EMAIL.ADMIN'))
        ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
        ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
        ->setHeader('Cron error on ' . Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'))
        ->setTemplate(Application::getAnchor('Local')::getPath('/Template/ErrorInfo.mail'))
        ->setMailData([
            'date' => new DateTime(),
            'message' => $t->getMessage(),
            'trace' => $t->getTraceAsString(),
            'line' => $t->getLine(),
            'file' => $t->getFile(),
            'host' => Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'),
            'uri' => __FILE__
        ]);
    (new MailQueueMapper(new MapperManager($db)))->saveModel($mailQueue);
}