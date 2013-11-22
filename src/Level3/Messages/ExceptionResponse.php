<?php

namespace Level3\Messages;

use Level3\Exceptions\HTTPException;
use Level3\Resource\Resource;

use Teapot\StatusCode;
use Exception;

class ExceptionResponse extends Response
{
    protected $debug;

    public static function createFromException(Exception $exception)
    {
        $response = new static();
        $response->setException($exception);

        return $response;
    }

    public function setException(Exception $exception)
    {
        $resource = $this->createResourceFromException($exception);
        $this->setResource($resource);

        $code = $this->calculateStatusCodeFromException($exception);
        $this->setStatusCode($code);
    }

    protected function calculateStatusCodeFromException(Exception $exception)
    {
        $code = StatusCode::INTERNAL_SERVER_ERROR;
        if ($exception instanceof HTTPException) {
            $code = $exception->getCode();
        }

        return $code;
    }

    protected function createResourceFromException(Exception $exception)
    {
        $resource = new Resource();
        $resource->setData($this->convertExceptionToArray($exception));

        return $resource;
    }

    protected function convertExceptionToArray(Exception $exception)
    {
        $data = [];
        $data['type'] = get_class($exception);
        $data['message'] = $exception->getMessage();
        $data['trace'] = [];

        $trace = explode("\n", $exception->getTraceAsString());
        foreach ($trace as $error) {
            $data['trace'][] = $error;
        }

        return $data;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function getContent()
    {
        if (!$this->formatter || !$this->debug) {
            return '';
        }

        $this->resource->setFormatter($this->formatter);

        return (string) $this->resource;
    }
}
