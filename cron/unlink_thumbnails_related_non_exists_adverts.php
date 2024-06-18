#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Module\Advert\Model\Thumbnail;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Application;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Отвязка файлов изображений, привязанным к несуществующим объявлениям, т.е.
 * когда записи объявления нет, а поле `id_advert` в таблицы `advert-thumbnail`
 * содержит значение - идентификатор несуществующего объявления.
 */
try {
    require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');
    require(dirname(dirname(__FILE__)) . '/configuration/bootstrap.php');

    $db = Mysql::create(
        Registry::getInstance()->get('DATABASE.HOST'),
        Registry::getInstance()->get('DATABASE.USER'),
        Registry::getInstance()->get('DATABASE.PASSWORD')
    )->setDatabaseName(Registry::getInstance()->get('DATABASE.NAME'))
        ->setCharset(Registry::getInstance()->get('DATABASE.CHARSET'))
        ->setStoreQueries(false);

    $mapper = new ThumbnailMapper(new MapperManager($db));
    $thumbnails = $mapper->getThumbnailsRelatedToNonExistsAdverts();

    $count_delete_rows = 0;
    foreach ($thumbnails as $thumbnail) {
        try {
            /* @var Thumbnail $thumbnail */
            $num_rows = $thumbnail->unlink();
            $count_delete_rows += $num_rows;
        } catch (RuntimeException $e) {
            error_log($e->getMessage(), 0);

            $mailQueue = new MailQueue();
            $mailQueue
                ->setSendDate(new DateTime())
                ->setToEmail(Registry::getInstance()->get('EMAIL.ADMIN'))
                ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setHeader('Cron error on ' . Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'))
                ->setTemplate(Application::getAnchor('Local')::getPath('/Template/ErrorInfo.mail'))
                ->setMailData([
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'host' => Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'),
                    'uri' => __FILE__
                ]);
            (new MailQueueMapper(new MapperManager($db)))->saveModel($mailQueue);
        }
    }

    if ($count_delete_rows) {
        $logMessage = sprintf(
            'Remove thumbnails related to non exists adverts: %s of %s' . PHP_EOL,
            $count_delete_rows,
            $thumbnails->count()
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