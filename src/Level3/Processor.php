<?php

namespace Level3;

use Level3\Messages\Request;
use Level3\Resource\Resource;
use Level3\Messages\Response;
use Level3\Messages\ExceptionResponse;
use Level3\Exceptions\NotFound;
use Level3\Exceptions\NotImplemented;
use Level3\Exceptions\NotAcceptable;
use Teapot\StatusCode;
use RuntimeException;
use Exception;
use Closure;

class Processor
{
    private $level3;

    public function setLevel3(Level3 $level3)
    {
        $this->level3 = $level3;
    }

    public function find($key, Request $request)
    {
        return $this->execute('find', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $resource = $repository->find($request->attributes, $request->query);
            $this->expandLinkedResources($request, $resource);

            return $this->covertResourceToResponse($resource, $request);
        });
    }

    public function get($key, Request $request)
    {
        return $this->execute('get', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $resource = $repository->get($request->attributes);
            $this->expandLinkedResources($request, $resource);

            return $this->covertResourceToResponse($resource, $request);
        });
    }

    protected function expandLinkedResources(Request $request, Resource $resource)
    {
        $paths = $request->attributes->get('_expand');
        if (!$paths) {
            return;
        }

        foreach ($paths as $path) {
            $resource->expandLinkedResourcesTree($path);
        }
    }

    public function post($key, Request $request)
    {
        return $this->execute('post', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $resource = $repository->post($request->attributes, $request->request);

            $response = $this->covertResourceToResponse($resource, $request);
            $response->setStatusCode(StatusCode::CREATED);

            return $response;
        });
    }

    public function patch($key, Request $request)
    {
        return $this->execute('patch', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $resource = $repository->patch($request->attributes, $request->request);

            return $this->covertResourceToResponse($resource, $request);
        });
    }

    public function put($key, Request $request)
    {
        return $this->execute('put', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $resource = $repository->put($request->attributes, $request->request);

            return $this->covertResourceToResponse($resource, $request);
        });
    }

    public function delete($key, Request $request)
    {
        return $this->execute('delete', $key, $request, function (
            Repository $repository, 
            Request $request
        ) {
            $repository->delete($request->attributes);

            return new Response(null, StatusCode::NO_CONTENT);
        });
    }

    public function options($key, Request $request)
    {
        return $this->execute('options', $key, $request, function () {
            throw new NotImplemented();
        });
    }

    public function error($key, Request $request, Exception $exception)
    {
        return $this->execute('error', $key, $request, function (
            Repository $repository, 
            Request $request
        ) use ($exception) {
            return $this->covertExceptionToResponse($exception, $request);
        });
    }

    protected function execute($method, $key, Request $request, Callable $execution)
    {
        $repository = $this->getRepository($key);
        foreach ($this->getProcessorWrappers() as $wrapper) {
            $execution = function (Repository $repository, Request $request) use (
                $wrapper, $method, $repository, $execution
            ) {
                return $wrapper->$method($repository, $request, $execution);
            };
        }

        return $execution($repository, $request);
    }

    protected function getProcessorWrappers()
    {
        return $this->level3->getProcessorWrappers();
    }

    protected function getRepository($key)
    {
        try {
            return $this->level3->getRepository($key);
        } catch (RuntimeException $e) {
            throw new NotFound();
        }
    }

    protected function covertResourceToResponse(Resource $resource, Request $request)
    {
        $response = Response::createFromResource($resource);
        $this->calculateAndSetFormatter($response, $request);

        return $response;
    }

    protected function covertExceptionToResponse(Exception $exception, Request $request)
    {
        $response = ExceptionResponse::createFromException($exception);
        $this->calculateAndSetFormatter($response, $request);

        return $response;
    }

    protected function calculateAndSetFormatter(Response $response, Request $request)
    {
        $formatter = null;

        $contentTypes = $request->getAcceptableContentTypes();
        foreach ($contentTypes as $contentType) {
            $formatter = $this->level3->getFormatterByContentType($contentType);
            if ($formatter) {
                break;
            }
        }

        if (!$formatter) {
            throw new NotAcceptable();
        }

        $response->setFormatter($formatter);
    }

}
