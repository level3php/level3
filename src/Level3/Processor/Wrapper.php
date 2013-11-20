<?php

namespace Level3\Processor;

use Level3\Level3;
use Level3\Repository;
use Level3\Messages\Request;
use Closure;

abstract class Wrapper
{
    protected $level3;

    public function setLevel3(Level3 $level3)
    {
        $this->level3 = $level3;
    }

    public function getLevel3()
    {
        return $this->level3;
    }

    public function find(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function get(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function post(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function put(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function patch(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function delete(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function options(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    public function error(Repository $repository, Request $request, Callable $execution)
    {
        return $this->processRequest($repository, $request, $execution, __FUNCTION__);
    }

    abstract protected function processRequest(
        Repository $repository,
        Request $request, 
        Callable $execution,
        $method
    );
}
