<?php

namespace Level3\Messages;

use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Level3\Resource\Resource;
use Level3\Resource\Formatter;
use Teapot\StatusCode;
use Exception;
use DateTime;
use DateInterval;

class Response extends BaseResponse
{
    protected $resource;
    protected $formatter;

    public static function createFromResource(Resource $resource)
    {
        $response = new static();
        $response->setStatusCode(StatusCode::OK);
        $response->setResource($resource);

        return $response;
    }

    public function setResource(Resource $resource)
    {
        $this->configureCacheWithResource($resource);
        $this->configureETagFromResource($resource);
        $this->configureLastModifierFromResource($resource);
        
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    protected function configureCacheWithResource(Resource $resource)
    {
        $cache = $resource->getCache();
        if (!$cache) {
            return;
        }

        $date = new DateTime();
        $date->add(new DateInterval(sprintf('PT%dS', $cache)));

        $this->setExpires($date);
        $this->setTTL($cache);
    }

    protected function configureETagFromResource(Resource $resource)
    {
        $id = $resource->getId();
        if (!$id) {
            return;
        }

        $this->setEtag($id);
    }

    protected function configureLastModifierFromResource(Resource $resource)
    {
        $date = $resource->getLastUpdate();
        if (!$date) {
            return;
        }

        $this->setLastModified($date);
    }

    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
        $this->setContentTypeFromFormatter($formatter);
    }

    protected function setContentTypeFromFormatter(Formatter $formatter)
    {
        $this->headers->set('Content-Type', $formatter->getContentType());
    }

    public function getContent()
    {
        if (!$this->formatter) {
            return '';
        }

        $this->resource->setFormatter($this->formatter);

        return (string) $this->resource;
    }

    public function sendContent()
    {
        echo $this->getContent();

        return $this;
    }

}
