#!/opt/php82/bin/php
<?php

use Krugozor\Database\Statement;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Application;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Удаление файлов изображений, информации о которых нет в СУБД и которые модифицированы 5 дней назад.
 * Фактически, убирается мусор, информация о котором не присутствует в СУБД.
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

    function execute($dir_path)
    {
        global $db;
        $logDate = (new DateTime())->formatAsMysqlDatetime();

        $file = Registry::getInstance()->get('PATH.LOGS_DIR') . 'thumbnails.txt';

        exec("find $dir_path -type f -mtime -5 > " . $file);

        $fp = fopen($file, "r");

        $remove_count = 0;
        while (($file_name = fgets($fp)) !== false) {
            $file_name = trim($file_name);

            if (basename($file_name) === '.gitignore') {
                continue;
            }

            $result = $db->query('
                SELECT `id` 
                FROM `advert-thumbnail` 
                WHERE `file_name` = "?s"
              ', basename($file_name)
            );

            if (is_object($result) && $result instanceof Statement && $result->getNumRows() == 0) {
                if (@unlink($file_name)) {
                    $remove_count++;
                    $logMessage = "Remove file $file_name" . PHP_EOL;
                } else {
                    $logMessage = sprintf("Failed to delete the file %s" . PHP_EOL, $file_name);
                }
                echo sprintf('%s - %s', $logDate, $logMessage);
            }
        }

        if ($remove_count) {
            $logMessage =  "Search in $dir_path done, remove: $remove_count" . PHP_EOL;
            echo sprintf('%s - %s', $logDate, $logMessage);
        }

        fclose($fp);

        unlink($file);
    }

    $directories = [
        Registry::getInstance()->get('UPLOAD.THUMBNAIL_SMALL'),
        Registry::getInstance()->get('UPLOAD.THUMBNAIL_800x800')
    ];
    foreach ($directories as $directory) {
        execute(DOCUMENTROOT_PATH . $directory);
        sleep(5);
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