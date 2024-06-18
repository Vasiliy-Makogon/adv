#!/opt/php82/bin/php
<?php

use Krugozor\Database\Mysql;
use Krugozor\Framework\Application;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Денормализация данных для таблицы категорий.
 *
 * Для каждой строки таблицы категорий проставляет:
 *
 * В поле `category_advert_count` - кол-во объявлений, содержащихся в каждой категории и всех её подкатегориях -
 * это статистическая информация для административного интерфейса.
 * В поле `category_indent` - числовой уровень вложенности от корня, корневые элементы имеют уровень 0.
 *
 * В дополнительные таблицы пишет:
 *
 * В `category-category_childs` - перечень идентификаторов непосредственно дочерних узлов категории.
 * В `category-category_all_childs` - перечень идентификаторов ВСЕХ дочерних узлов.
 * В `category-category_all_childs_with_parent` - перечень идентификаторов ВСЕХ дочерних узлов + ID самого
 * родительского узла.
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

    $db->query('TRUNCATE TABLE `category-category_childs`');
    $db->query('TRUNCATE TABLE `category-category_all_childs`');
    $db->query('TRUNCATE TABLE `category-category_all_childs_with_parent`');

    $result = $db->query('SELECT `id` FROM `category`');

    if ($result->getNumRows()) {
        while ($id = $result->getOne()) {
            $db->query('
                UPDATE `category` 
                SET `category_advert_count` = (
                    SELECT COUNT(*) 
                    FROM `advert` FORCE INDEX(`category-active`) 
                    WHERE `advert_category` = ?i AND `advert_active` = 1
                )
                WHERE `id` = ?i', $id, $id);

            $db->query('
                INSERT INTO `category-category_childs` (category_id, child_id) 
                    SELECT ?i, `id` 
                      FROM `category` 
                      WHERE `pid` = ?i', $id, $id);
        }
    }

    /**
     * @param Mysql $db
     * @param int $pid
     * @param int $indent
     * @return array
     */
    function tree(Mysql $db, int $pid = 0, int $indent = 0): array
    {
        $sql = '
            SELECT `id`, `category_advert_count`
            FROM `category` 
            WHERE `pid` = ?i';
        $result = $db->query($sql, $pid);

        $count_all = 0;
        $childs_all = [];

        if ($result->getNumRows()) {
            while ($data = $result->fetchAssoc()) {
                $count = $data['category_advert_count'];

                $sql = '
                    SELECT `child_id`
                    FROM `category-category_childs` FORCE INDEX (`category_id`)
                    WHERE `category_id` = ?i';
                $childsResult = $db->query($sql, $data['id']);

                $childs = [];
                while ($c = $childsResult->fetchAssoc()) {
                    $childs[] = $c['child_id'];
                }

                $from_tree = tree($db, $data['id'], $indent + 1);

                $count += $from_tree['count_all'];
                $count_all += $count;

                $childs = array_merge($childs, $from_tree['ids_all']);
                $childs_all = array_merge($childs, $childs_all);

                asort($childs);
                $sql = '
                    UPDATE `category` 
                    SET `category_advert_count` = ?i, 
                        `category_indent` = ?i 
                    WHERE id = ?i';
                $db->query($sql, $count, $indent, $data['id']);

                $sqlParts = [];
                foreach ($childs as $child_id) {
                    $sqlParts[] = "($data[id], $child_id)";
                }
                if ($sqlParts) {
                    $sql = '
                        INSERT INTO `category-category_all_childs` 
                        VALUES ' . implode(',', $sqlParts);
                    $db->query($sql);

                    $sqlParts[] = "($data[id], $data[id])";
                    $sql = '
                        INSERT INTO `category-category_all_childs_with_parent` 
                        VALUES ' . implode(',', $sqlParts);
                    $db->query($sql);
                }
            }
        }

        return ['count_all' => $count_all, 'ids_all' => $childs_all];
    }

    tree($db);

    echo sprintf("%s - Выполнено\n", (new DateTime())->formatAsMysqlDatetime());

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