<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Controller;

use Exception;
use Krugozor\Framework\Application;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Resource\Model\ResourceFont;

class Fonts extends AbstractController
{
    use DisableAuthorizationTrait;

    /**
     * @throws Exception
     */
    public function run()
    {
        $paths = [
            Application::getAnchor($this->getRequest()->getRequest('module'))::getPath(),
            'resources',
            'fonts',
            $this->getRequest()->getRequest('file')
        ];
        $path = implode(DIRECTORY_SEPARATOR, $paths);

        try {
            $resource = new ResourceFont($path);
            $resourceMimeType = $resource->getResourceMimeType();

            $this->getResponse()
                ->unsetHeader(Response::HEADER_LAST_MODIFIED)
                ->unsetHeader(Response::HEADER_EXPIRES)
                ->unsetHeader(Response::HEADER_CACHE_CONTROL);

            if (!Request::IfModifiedSince($resource->getModificationTime())) {
                return $this->getResponse()->setHttpStatusCode(304);
            }

            $this->getResponse()
                ->setHeader(Response::HEADER_CONTENT_TYPE, $resourceMimeType)
                ->setHeader(Response::HEADER_LAST_MODIFIED, $resource->getModificationTime()->formatHttpDate())
                ->setHeader(Response::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate, no-transform');

            return $resource;
        } catch (Exception $e) {
            throw $e;
        }
    }
}