<?php

namespace Krugozor\Framework\Module\Category\Helper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Helper\Format;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\View;

class AllCategoryRenderingHelper
{
    /**
     * @param View $view
     */
    public function __construct(protected View $view)
    {
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->createRows(
            $this->view->getStorage()->offsetGet('categories')
        );
    }

    /**
     * @param CoverArray $tree
     * @return string
     */
    private function createRows(CoverArray $tree): string
    {
        $str = '';

        /** @var Category $category */
        foreach ($tree as $category) {
            ob_start();
            ?>
            <tr id="category_<?= $category->getId() ?>">
                <td id="category_<?= $category->getId() ?>"><?= $category->getId() ?></td>
                <td data-content-yes-no="<?= $category->getPaid() ?>">
                    <? if ($category->getPaid()): ?>
                        <?= $this->view->getLang()->get('content.yes') ?>
                    <? else: ?>
                        <?= $this->view->getLang()->get('content.no') ?>
                    <? endif; ?>
                </td>
                <td><?= $category->getIndent() ?></td>
                <td data-content-yes-no="<?= $category->getPaid() ?>">
                    <?php if ($category->getIsService()): ?>
                        <?= $this->view->getLang()->get('content.yes') ?>
                    <?php else: ?>
                        <?= $this->view->getLang()->get('content.no') ?>
                    <?php endif; ?>
                </td>
                <td style="padding-left:<?= (($category->getIndent() + 1) * 25) ?>px; <? if (!$category->getPid()) : ?>font-weight:bold;<? endif; ?>">
                    <?= Format::hsc($category->getName()) ?>
                    (<a target="_blank"
                        href="/advert/backend-main/?category=<?= $category->getId() ?>"><?= $category->getAdvertCount() ?></a>)
                </td>
                <td>
                    <?= Format::hsc($category->getUrl()) ?>
                </td>
                <td>
                    <a href="/category/backend-edit/?pid=<?= $category->getId() ?>">
                        <img src="/img/local/system/icon/add.png" alt="">
                    </a>
                </td>
                <td>
                    <a href="/category/backend-add-list/?id=<?= $category->getId() ?>">
                        <img src="/img/local/system/icon/add.png" alt="">
                    </a>
                </td>
                <td>
                    <a href="/category/backend-edit/?id=<?= $category->getId() ?>">
                        <img alt="" src="/img/local/system/icon/edit.png">
                    </a>
                </td>
                <td>
                    <?php if ($category->getAdvertCount() || $category->getTree()->count()): ?>
                        <img title="Невозможно удалить категорию, пока в ней есть элементы или категории-потомки"
                             src="/img/local/system/icon/delete_empty.png" alt="">
                    <?php else: ?>
                        <?php
                        $msg = 'Вы действительно хотите удалить категорию «{title}» (id: {id})?';
                        $data = ['title' => $category->getName(), 'id' => $category->getId()];
                        $msg = Format::js($msg, $data);
                        ?>
                        <a onclick='return confirm(<?= $msg ?>)'
                           href="/category/backend-delete/?id=<?= $category->getId() ?>&amp;referer=<?=
                           urlencode(sprintf('/category/backend-main/#category_%s', $category->getPid())) ?>">
                            <img src="/img/local/system/icon/delete.png" alt="">
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/category/backend-motion/?tomotion=up&amp;id=<?= $category->getId() ?>">
                        <img src="/img/local/system/icon/up.gif" title="Поднять запись на одну позицию выше" alt="">
                    </a>
                </td>
                <td>
                    <a href="/category/backend-motion/?tomotion=down&amp;id=<?= $category->getId() ?>">
                        <img src="/img/local/system/icon/down.gif" alt="">
                    </a>
                </td>
            </tr>
            <?php
            $str .= ob_get_contents();
            ob_end_clean();

            if ($category->getTree() && $category->getTree()->count()) {
                $str .= $this->createRows($category->getTree());
            }
        }

        return $str;
    }
}