<?php

declare(strict_types=1);

namespace Krugozor\Framework\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Context;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\View;
use Krugozor\Framework\View\AjaxView;

/**
 * Контроллер, обрабатывающий Ajax-запросы.
 */
abstract class AbstractAjaxController extends AbstractController
{
    /**
     * @param Context $context
     * @throws MySqlException
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->default_view_class_name = AjaxView::class;
        $this->getResponse()
            ->setHeader(Response::HEADER_CONTENT_TYPE, 'application/json; charset=utf-8')
            ->setHeader(Response::X_ROBOTS_TAG, 'none,noarchive');
    }

    /**
     * @param string|null $template
     * @param string|null $view_class_name
     * @return View
     */
    protected function getView(?string $template = null, ?string $view_class_name = null): View
    {
        // Для Ajax-запросов вывод отладочной информации в теле документа всегда запрещён.
        return parent::getView($template, $view_class_name)->setDebugInfoFlag(false);
    }
}