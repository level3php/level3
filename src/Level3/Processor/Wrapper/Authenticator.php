<?php

namespace Level3\Processor\Wrapper;

use Level3\Repository;
use Level3\Messages\Request;
use Level3\Messages\Response;
use Level3\Processor\Wrapper;
use Level3\Processor\Wrapper\Authenticator\Method;

class Authenticator extends Wrapper
{
    const PRIORITY_LOW = 10;
    const PRIORITY_NORMAL = 20;
    const PRIORITY_HIGH = 30;

    protected $methods = [];

    public function clearMethods()
    {
        $this->methods = [];
    }

    public function addMethod(Method $method, $priority = self::PRIORITY_NORMAL)
    {
        $this->methods[$priority][] = $method;
    }

    public function getMethods()
    {
        $result = [];

        ksort($this->methods);
        foreach ($this->methods as $priority => $methods) {
            $result = array_merge($result, $methods);
        }

        return $result;
    }

    public function error(
        Repository $repository = null,
        Request $request,
        Callable $execution
    )
    {
        $this->setAllowCredentialsIfNeeded();

        $response = $execution($repository, $request);
        $this->modifyResponse($response, 'error');

        return $response;
    }

    protected function processRequest(
        Repository $repository = null,
        Request $request,
        Callable $execution,
        $method
    )
    {
        $this->setAllowCredentialsIfNeeded();

        $this->authenticateRequest($request, $method);
        $response = $execution($repository, $request);
        $this->modifyResponse($response, $method);

        return $response;
    }

    protected function authenticateRequest(Request $request, $httpMethod)
    {
        foreach ($this->getMethods() as $method) {
            $this->authenticateWithMethod($method, $request, $httpMethod);
        }
    }

    protected function authenticateWithMethod(Method $method, Request $request, $httpMethod)
    {
        $method->authenticateRequest($request, $httpMethod);
    }

    protected function modifyResponse(Response $response, $httpMethod)
    {
        foreach ($this->getMethods() as $method) {
            $this->modifyResponseWithMethod($method, $response, $httpMethod);
        }
    }

    protected function modifyResponseWithMethod(Method $method, Response $response, $httpMethod)
    {
        $method->modifyResponse($response, $httpMethod);
    }

    protected function setAllowCredentialsIfNeeded()
    {
        if (!$level3 = $this->getLevel3()) {
            return false;
        }

        $corsClass = 'Level3\Processor\Wrapper\CrossOriginResourceSharing';
        $cors = $level3->getProcessorWrappersByClass($corsClass);

        if ($cors) {
            $cors->setAllowCredentials(true);
        }
    }
}
