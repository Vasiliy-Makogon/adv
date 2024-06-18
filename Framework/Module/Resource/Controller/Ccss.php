<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Controller;

use InvalidArgumentException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Helper\Format;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Resource\Model\ResourceCss;
use Krugozor\Framework\Registry;

class Ccss extends AbstractController
{
    use DisableAuthorizationTrait;

    /**
     * @return Response|ResourceCss
     */
    public function run(): Response|ResourceCss
    {
        $options = ResourceCss::recompileResourceFileName(
            $this->getRequest()->getRequest('enums', Request::SANITIZE_STRING)
        );

        $currentResourceRealPath = implode(DIRECTORY_SEPARATOR, [
            Registry::getInstance()->get('PATH.CCSS'),
            ResourceCss::createCompileResourceFileNameByOptions($options)
        ]);

        try {
            $currentResource = new ResourceCss($currentResourceRealPath);
        } catch (InvalidArgumentException) {
            $currentResource = null;
        }

        // ресурса ещё нет, надо регенерировать
        $regenerate = is_null($currentResource);

        // Если ресурс есть как скомпилированный файл, запустим проверку и возврат mime-type
        // Если ресурса ещё нет - вернём его mime-type из констант класса ресурса
        $resourceMimeType = !$regenerate
            ? $currentResource->getResourceMimeType()
            : ResourceCss::RESOURCE_INFO[ResourceCss::RESOURCE_EXTENSION];

        $resources = [];

        foreach ($options as $module => $files) {
            foreach ($files as $file) {
                $resource = new ResourceCss(
                    ResourceCss::getRealResourceFilePath($module, $file)
                );
                $resources[] = $resource;

                // Ресурса еще нет или уже определили, что нужна перекомпиляция
                if ($regenerate) {
                    continue;
                }

                if (
                    $currentResource->getModificationTime() < $resource->getModificationTime() or
                    isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && Request::IfModifiedSince($resource->getModificationTime())
                ) {
                    $regenerate = true;
                }
            }
        }

        $this->getResponse()
            ->unsetHeader(Response::HEADER_LAST_MODIFIED)
            ->unsetHeader(Response::HEADER_EXPIRES)
            ->unsetHeader(Response::HEADER_CACHE_CONTROL);

        if (!$regenerate) {
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                return $this->getResponse()->setHttpStatusCode(304);
            } else {
                $this->getResponse()
                    ->setHeader(Response::HEADER_CONTENT_TYPE, $resourceMimeType)
                    ->setHeader(Response::HEADER_LAST_MODIFIED, $currentResource->getModificationTime()->formatHttpDate())
                    ->setHeader(Response::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate, no-transform');
                return $currentResource;
            }
        }

        $content = array_map(function ($resource) {
            return $resource->getResourceContents();
        }, $resources);
        $content = implode(PHP_EOL, $content);
        $content = Format::cleanCss($content);

        if (file_put_contents($currentResourceRealPath, $content) !== false) {
            $resource = new ResourceCss($currentResourceRealPath);

            $this->getResponse()
                ->setHeader(Response::HEADER_CONTENT_TYPE, 'text/css; charset=utf-8')
                ->setHeader(Response::HEADER_LAST_MODIFIED, $resource->getModificationTime()->formatHttpDate())
                ->setHeader(Response::HEADER_CACHE_CONTROL, 'public, no-transform, must-revalidate');

            return $resource;
        }

        // todo
    }
}