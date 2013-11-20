<?php

namespace Level3\Processor\Wrapper;

use Level3\Repository;
use Level3\Messages\Request;
use Level3\Processor\Wrapper;
use Exception;

class ExceptionHandler extends Wrapper
{
    public function error(
        Repository $repository,
        Request $request,
        Callable $execution
    )
    {
        return $execution($repository, $request);
    }

    protected function processRequest(
        Repository $repository,
        Request $request, 
        Callable $execution,
        $method
    )
    {
        try {
            return $execution($repository, $request);
        } catch (Exception $exception) {
            $processor = $this->getLevel3()->getProcessor();
            return $processor->error($repository->getKey(), $request, $exception);
        }
    }
}
