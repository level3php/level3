<?php
namespace Level3\Helper;

use Level3\Hub;
use Level3\Repository;
use Level3\Repository\Finder;
use Level3\Resource\Resource;
use Level3\Resource\Link;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * List of repositories available. This repository.
 */
class IndexRepository extends Repository implements Finder
{
    public function find(ParameterBag $attributes, ParameterBag $filters)
    {
        $resource = new Resource();
        $this->fillResource($resource);

        return $resource;
    }

    protected function fillResource(Resource $resource)
    {
        $resources = [];

        $hub = $this->level3->getHub();
        foreach ($hub->getKeys() as $key) {
            $resources[] = $this->createResourceFromRepository($hub->get($key));
        }

        $resource->addResources('repositories', $resources);
    }

    protected function createResourceFromRepository(Repository $repository)
    {
        $resource = new Resource();
        $resource->setData([
            'name' => $repository->getKey(),
            'description' => $repository->getDescription()
        ]);
            
        $mapper = $this->level3->getMapper();
        
        $links = [];
        foreach (class_implements($repository) as $method) {
            $link = new Link($repository->getURI(null, $method));
            $link->setName($mapper->getHTTPMethodFromInterface($method));
            $link->setTemplated('true');
            
            $links[] = $link;
        }

        $resource->setLinks('actions', $links);

        return $resource;
    }
}
