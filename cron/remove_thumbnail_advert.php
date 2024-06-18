#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Application;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Module\Advert\Model\Thumbnail;

/**
 * Удаление файлов изображений, не привязанным к объявлениям, и информации о них из СУБД.
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
    $thumbnails = $mapper->getThumbnailsNotRelatedToAdverts();

    $count_delete_rows = 0;
    $problem_rows = [];
    foreach ($thumbnails as $thumbnail) {
        try {
            /* @var $thumbnail Thumbnail */
            $num_rows = $thumbnail->delete();

            if (!$num_rows) {
                $problem_rows[] = sprintf("%s: %s", $thumbnail->getId(), $thumbnail->getFileName());
            } else {
                $count_delete_rows += $num_rows;
            }
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
                    'date' => new DateTime(),
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

    $logDate = (new DateTime())->formatAsMysqlDatetime();

    if ($count_delete_rows) {
        $logMessage = sprintf(
            "Removed thumbnails not related to adverts: %s of %s\n",
            $count_delete_rows,
            $thumbnails->count()
        );
        echo sprintf('%s - %s', $logDate, $logMessage);
    }

    if ($problem_rows) {
        $logMessage = sprintf(
            "The following records not removed at the time thumbnails removed:\n\n%s\n",
            implode(PHP_EOL, $problem_rows)
        );
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