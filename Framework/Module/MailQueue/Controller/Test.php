<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\MailQueue\Controller;

use Krugozor\Framework\Application;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Type\Date\DateTime;

class Test extends AbstractController
{
    public function run()
    {
        if (!$this->getCurrentUser()->isAdministrator()) {
            echo "Тестировать MailQueue может только администратор";
            exit;
        }

        $mailQueue = new MailQueue();
        $mailQueue
            ->setSendDate(new DateTime())
            ->setTemplate(Application::getAnchor('MailQueue')::getPath('/Template/Test.mail'))
            ->setToEmail(Registry::getInstance()->get('EMAIL.ADMIN'))
            ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
            ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
            ->setHeader('Тестовое письмо')
            ->setMailData([
                'name' => 'Вася',
            ]);

        echo $this->getMapper(MailQueueMapper::class)->saveModel($mailQueue)->getId();
        exit;
    }
}