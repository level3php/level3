<?php

namespace Level3;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Mapper
{
    const SLASH_CHARACTER = '/';
    const DEFAULT_INTERFACE = 'Level3\Repository\Getter';

    protected $baseURI = '';
    protected $skipCurieSegments = 0;

    protected $interfacesWithOutParams = [
        'Level3\Repository\Poster' => 'POST',
        'Level3\Repository\Finder' => 'GET'
    ];

    protected $interfacesWithParams = [
        'Level3\Repository\Deleter' => 'DELETE',
        'Level3\Repository\Getter' => 'GET',
        'Level3\Repository\Putter' => 'PUT',
        'Level3\Repository\Patcher' => 'PATCH'
    ];

    public function setBaseURI($uri)
    {
        $this->baseURI = $this->removeSlashToUri($uri);
    }

    public function setSkipCurieSegments($skip)
    {
        $this->skipCurieSegments = $skip;
    }

    private function doesEndInSlash($uri)
    {
        if (!strlen($uri)) {
            return false;
        }

        return $uri[strlen($uri) - 1] == self::SLASH_CHARACTER;
    }

    private function removeSlashToUri($uri)
    {
        if (!$this->doesEndInSlash($uri)) {
            return $uri;
        }

        return substr($uri, 0, strlen($uri)-1);
    }

    public function getBaseURI()
    {
        return $this->baseURI;
    }

    private function transformCurieURI($curieURI, ParameterBag $attributes = null)
    {
        if (!$attributes) {
            return $curieURI;
        }

        foreach ($attributes->all() as $key => $value) {
            $curieURI = str_replace(sprintf('{%s}', $key), $value, $curieURI);
        }

        return $curieURI;
    }

    public function boot(Hub $hub)
    {
        foreach ($hub->getKeys() as $resourceKey) {
            $this->mapRepositoryToRoutes($hub, $resourceKey);
        }
    }

    private function mapRepositoryToRoutes(Hub $hub, $repositoryKey)
    {
        $repository = $hub->get($repositoryKey);

        $uris = [];
        foreach (class_implements($repository) as $interface => $method) {
            $uri = $this->mapMethodIfNeededAndReturnURIs($repository, $interface);
            if ($uri) {
                $uris[$uri] = true;
            }
        }

        $this->mapOptionsMethod($repository, array_keys($uris));
    }

    private function mapMethodIfNeededAndReturnURIs(Repository $repository, $interface)
    {
        if ($repository instanceof $interface) {
            return $this->callToMapMethod($repository, $interface);
        }

        return null;
    }

    private function mapOptionsMethod(Repository $repository, Array $uris)
    {
        $repositoryKey = $repository->getKey();

        foreach ($uris as $uri) {
            $this->mapOptions($repositoryKey, $uri);
        }
    }

    private function callToMapMethod(Repository $repository, $interface)
    {
        $namespace = explode('\\', $interface);
        $name = ucfirst(strtolower(end($namespace)));
        $method = sprintf('map%s', $name);

        $repositoryKey = $repository->getKey();
        $curieURI = $this->getCurieURI($repositoryKey, $interface);

        $this->$method($repositoryKey, $curieURI);

        return $curieURI;
    }

    public function getURI(
        $repositoryKey,
        $interface = self::DEFAULT_INTERFACE,
        ParameterBag $attributes = null
    )
    {
        $curieURI = $this->getCurieURI($repositoryKey, $interface);

        return $this->transformCurieURI($curieURI, $attributes);
    }

    public function getCurieURI($repositoryKey, $interface = null)
    {
        if (!$interface) {
            $interface = self::DEFAULT_INTERFACE;
        }

        foreach ($this->interfacesWithOutParams as $interfaceName => $method) {
            if ($interface == $interfaceName) {
                return $this->generateCurieURI($repositoryKey);
            }
        }

        foreach ($this->interfacesWithParams as $interfaceName => $method) {
            if ($interface == $interfaceName) {
                return $this->generateCurieURI($repositoryKey, true);
            }
        }
    }

    public function getHTTPMethodFromInterface($interface)
    {
        if (isset($this->interfacesWithOutParams[$interface])) {
            return $this->interfacesWithOutParams[$interface];
        }

        if (isset($this->interfacesWithParams[$interface])) {
            return $this->interfacesWithParams[$interface];
        }

        return null;
    }

    protected function generateCurieURI($repositoryKey, $specific = false)
    {
        $uri = $this->baseURI;

        if ($repositoryKey !== Hub::INDEX_REPOSITORY_KEY) {
            $names = explode(self::SLASH_CHARACTER, $repositoryKey);
        } else {
            $names = [''];
        }

        $max = count($names);
        for ($i=0; $i<$max; $i++) {
            $uri .= self::SLASH_CHARACTER . $names[$i];
            if (($specific || $max > $i+1) && $i >= $this->skipCurieSegments) {
                $uri .= self::SLASH_CHARACTER . $this->createCurieParamFromName($names[$i]);
            }
        }


        return $uri;
    }

    protected function createCurieParamFromName($name)
    {
        return sprintf('{%sId}', $name);
    }

    public function getMethods(Repository $repository)
    {
        $interfaces = array_merge(
            $this->interfacesWithOutParams,
            $this->interfacesWithParams
        );

        $methods = [];
        foreach ($interfaces as $interface => $method) {
            if ($repository instanceof $interface) {
                $methods[] = $method;
            }
        }

        $methods = array_unique($methods);
        sort($methods);

        return $methods;
    }

    abstract public function mapFinder($repositoryKey, $uri);
    abstract public function mapGetter($repositoryKey, $uri);
    abstract public function mapPoster($repositoryKey, $uri);
    abstract public function mapPutter($repositoryKey, $uri);
    abstract public function mapPatcher($repositoryKey, $uri);
    abstract public function mapDeleter($repositoryKey, $uri);
    abstract public function mapOptions($repositoryKey, $uri);
}
