#!/opt/php82/bin/php
<?php

use Krugozor\Framework\Registry;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Application;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Database\Mysql;
use Krugozor\Framework\Type\Date\DateTime;
use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;

/**
 * Генерация sitemap.
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

    $host = Registry::getInstance()->get('HOSTINFO.HOST') . '/';


    $staticSitemap = new Sitemap(dirname(dirname(__FILE__)) . '/sitemap_static.xml');
    $staticSitemap->setStylesheet($host . 'example-sitemap-stylesheet.xsl');
    $staticSitemap->addItem($host, time(), Sitemap::HOURLY, 1);

    $staticSitemap->addItem($host . 'advert/frontend-add/', null, Sitemap::MONTHLY, 0.8);
    $staticSitemap->addItem($host . 'advert/frontend-edit-advert/', null, Sitemap::MONTHLY, 0.8);
    $staticSitemap->addItem($host . 'authorization/frontend-login/', null, Sitemap::MONTHLY, 0.5);
    $staticSitemap->addItem($host . 'user/frontend-registration/', null, Sitemap::MONTHLY, 0.5);
    $staticSitemap->addItem($host . 'getpassword/frontend-getpassword/', null, Sitemap::MONTHLY, 0.4);

    $staticSitemap->addItem($host . 'help/b2b/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/contact/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/faq/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/index/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/poleznye_sovety/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/sale/', null, Sitemap::MONTHLY, 0.1);
    $staticSitemap->addItem($host . 'help/site-map/', null, Sitemap::HOURLY, 1);
    $staticSitemap->write();


    // Выборка по странам
    $countriesSitemap = new Sitemap(dirname(dirname(__FILE__)) . '/sitemap_countries.xml');
    $countriesSitemap->setStylesheet($host . 'example-sitemap-stylesheet.xsl');
    $countriesSitemap->setMaxUrls(50000);

    $res = $db->query("SELECT CONCAT('$host', uc.country_name_en, '/categories', `c`.`category_url`) as `url`
                       FROM `category` c
                       JOIN `user-country` uc
                       JOIN `advert-country_count` cnt ON cnt.id_country = uc.id AND c.id = cnt.id_category
                       WHERE uc.country_active = 1 AND cnt.count >= 10");
    while ($url = $res->getOne()) {
        $countriesSitemap->addItem($url, null, Sitemap::HOURLY, 1);
    }
    $countriesSitemap->write();


    // Выборка по регионам.
    $regionsSitemap = new Sitemap(dirname(dirname(__FILE__)) . '/sitemap_regions.xml');
    $regionsSitemap->setStylesheet($host . 'example-sitemap-stylesheet.xsl');
    $regionsSitemap->setMaxUrls(50000);

    $res = $db->query("SELECT CONCAT('$host', uc.country_name_en, '/', ur.region_name_en, '/categories', `c`.`category_url`) as `url`
                       FROM `category` c
                       JOIN `user-country` uc
                       JOIN `user-region` ur ON ur.id_country = uc.id
                       JOIN `advert-region_count` cnt ON cnt.id_region = ur.id AND c.id = cnt.id_category
                       WHERE uc.country_active = 1 AND cnt.count >= 10");
    while ($url = $res->getOne()) {
        $regionsSitemap->addItem($url, null, Sitemap::HOURLY, 1);
    }
    $regionsSitemap->write();


    // Выборка по городам.
    $citiesSitemap = new Sitemap(dirname(dirname(__FILE__)) . '/sitemap_cities.xml');
    $citiesSitemap->setStylesheet($host . 'example-sitemap-stylesheet.xsl');
    $citiesSitemap->setMaxUrls(50000);

    $res = $db->query("SELECT CONCAT('$host', uc.country_name_en, '/', ur.region_name_en, '/', uci.city_name_en, '/categories', `c`.`category_url`) as `url`
                       FROM `category` c
                       JOIN `user-country` uc
                       JOIN `user-region` ur ON ur.id_country = uc.id
                       JOIN `user-city` uci ON uci.id_region = ur.id
                       JOIN `advert-city_count` cnt ON cnt.id_city = uci.id AND c.id = cnt.id_category
                       WHERE uc.country_active = 1 AND cnt.count >= 10");
    while ($url = $res->getOne()) {
        $citiesSitemap->addItem($url, null, Sitemap::HOURLY, 1);
    }
    $citiesSitemap->write();


    // Последние объявления
    $advertsSitemap = new Sitemap(dirname(dirname(__FILE__)) . '/sitemap_adverts.xml');
    $advertsSitemap->setStylesheet($host . 'example-sitemap-stylesheet.xsl');
    $advertsSitemap->setMaxUrls(50000);

    $res = $db->query("SELECT
                        CONCAT('$host', 'advert/', `a`.`id`, '.xhtml') AS `url`,
                        IF (`advert_edit_date` IS NOT NULL, UNIX_TIMESTAMP(`advert_edit_date`), UNIX_TIMESTAMP(`advert_create_date`)) as `date`
                        FROM `advert` AS `a`
                        WHERE `a`.`advert_active` = 1
                        ORDER BY `a`.`id` DESC
                        LIMIT 0, 50000");

    while ($data = $res->fetchAssoc()) {
        $advertsSitemap->addItem($data['url'], $data['date'], Sitemap::WEEKLY, 0.7);
    }
    $advertsSitemap->write();


    $index = new Index(dirname(dirname(__FILE__)) . '/sitemap.xml');
    $index->setStylesheet($host . 'example-sitemap-stylesheet.xsl');

    foreach ($staticSitemap->getSitemapUrls($host) as $sitemapUrl) {
        $index->addSitemap($sitemapUrl);
    }

    foreach ($countriesSitemap->getSitemapUrls($host) as $sitemapUrl) {
        $index->addSitemap($sitemapUrl);
    }

    foreach ($regionsSitemap->getSitemapUrls($host) as $sitemapUrl) {
        $index->addSitemap($sitemapUrl);
    }

    foreach ($citiesSitemap->getSitemapUrls($host) as $sitemapUrl) {
        $index->addSitemap($sitemapUrl);
    }

    foreach ($advertsSitemap->getSitemapUrls($host) as $sitemapUrl) {
        $index->addSitemap($sitemapUrl);
    }

    $index->write();

    echo sprintf("%s - Записано успешно\n", (new DateTime())->formatAsMysqlDatetime());

} catch (Throwable $t) {
    echo $t->getMessage();
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