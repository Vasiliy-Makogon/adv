#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Registry;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\MailQueue\Mail;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

try {
    require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');
    require(dirname(dirname(__FILE__)) . '/configuration/bootstrap.php');

    $db = Mysql::create(
        Registry::getInstance()->get('DATABASE.HOST'),
        Registry::getInstance()->get('DATABASE.USER'),
        Registry::getInstance()->get('DATABASE.PASSWORD')
    )->setDatabaseName(Registry::getInstance()->get('DATABASE.NAME'))
        ->setCharset(Registry::getInstance()->get('DATABASE.CHARSET'));

    $mailQueueMapper = new MailQueueMapper(new MapperManager($db));

    $queues = $mailQueueMapper->findModelListByParams([
        'where' => [
            '`send_date` < now() AND `sended` = 0' => []
        ],
        'limit' => [
            'start' => 0, 'stop' => 20
        ]
    ]);

    if (!$queues->count()) {
        exit;
    }

    /** @var MailQueue $queue */
    foreach ($queues as $queue) {
        try {
            if (!file_exists($queue->getTemplate())) {
                throw new RuntimeException(sprintf(
                    '%s: Not found mail queue template by path `%s`',
                    __METHOD__,
                    $queue->getTemplate()
                ));
            }

            $mail = new Mail();
            $mail
                ->setHeader($queue->getHeader())
                ->setTo($queue->getToEmail())
                ->setFrom($queue->getFromEmail())
                ->setReplyTo($queue->getReplyEmail())
                ->setCc($queue->getCcEmail())
                ->setTemplate($queue->getTemplate());

            if ($data = $queue->getMailData()) {
                foreach ($data as $key => $value) {
                    $mail->$key = $value;
                }
            }

            if (!$mail->send()) {
                throw new RuntimeException(sprintf(
                    "%s: Mail queue with ID %s not sent.",
                    __METHOD__,
                    $queue->getId()
                ));
            }
            $queue->setSended(MailQueue::STATUS_OK);
        } catch (Throwable $t) {
            $queue->setSended(MailQueue::STATUS_FAIL);

            throw $t;
        } finally {
            $mailQueueMapper->saveModel($queue);

            echo sprintf(
                "%s - MailQueue %s: %s, %s, '%s'\n",
                (new DateTime())->formatAsMysqlDatetime(),
                $queue->getId(),
                $queue->getSended() == 1 ? 'Успешно' : 'Ошибка',
                $queue->getToEmail(),
                $queue->getHeader()
            );
        }
    }
} catch (Throwable $t) {
    ErrorLog::write($t->getMessage());
}