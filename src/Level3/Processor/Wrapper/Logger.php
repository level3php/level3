<?php

namespace Level3\Processor\Wrapper;

use Level3\Repository;
use Level3\Messages\Request;
use Level3\Messages\Response;
use Level3\Processor\Wrapper;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

class Logger extends Wrapper
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function processRequest(
        Repository $repository,
        Request $request, 
        Callable $execution,
        $method
    )
    {
        $response = $execution($repository, $request);

        $level = $this->getLogLevel($request, $response, $method);
        $log = $this->getLogMessage($repository, $request, $response, $method);

        $this->logger->$level($log, [
            'headers' => $response->headers->all()
        ]);

        return $response;
    }

    protected function getLogLevel(Request $request, Response $response, $method)
    {
        $code = $response->getStatusCode();

        if ($code >= 200 && $code < 400) {
            return LogLevel::INFO;
        } elseif ($code >= 400 && $code < 500) {
            return LogLevel::WARNING;
        } else {
            return LogLevel::ERROR;
        }
    }

    protected function getLogMessage(Repository $repository, Request $request, Response $response, $method)
    {
        $key = $repository->getKey();

        return sprintf('%s::%s - %s', $key, $method, null);
    }
}
