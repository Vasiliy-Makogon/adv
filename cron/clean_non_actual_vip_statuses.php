#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Application;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Удаление vip-дат объявлений с истекшим сроком годности.
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

    $logDate = (new DateTime())->formatAsMysqlDatetime();
    if ($advertNonActualVipDates = $mapper->cleanNonActualVipDates()) {
        echo sprintf("%s - Удалено VIP-статусов: %s\n", $logDate, $advertNonActualVipDates);
    } else {
        echo sprintf("%s - VIP-статусов не обнаружено\n", $logDate);
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