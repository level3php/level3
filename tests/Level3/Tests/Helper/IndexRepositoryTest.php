<?php
namespace Level3\Tests\Processor\Wrapper;

use Level3\Tests\TestCase;
use Level3\Helper\IndexRepository;
use Mockery as m;

class IndexRepositoryTest extends TestCase
{
    public function testFind()
    {
        $repository = $this->createRepositoryMock();
        $repository->shouldReceive('getKey')->once()->andReturn('foo');
        $repository->shouldReceive('getDescription')->once()->andReturn('bar');
        $repository->shouldReceive('getURI')->once()->andReturn('bar');

        $hub = $this->createHubMock();
        $hub->shouldReceive('getKeys')->once()->andReturn(['foo']);
        $hub->shouldReceive('get')->with('foo')->once()->andReturn($repository);

        $mapper = $this->createMapperMock();
        $mapper->shouldReceive('getHTTPMethodFromInterface')->once()->andReturn('qux');

        $level3 = $this->createLevel3Mock();
        $level3->shouldReceive('getHub')->andReturn($hub);
        $level3->shouldReceive('getMapper')->andReturn($mapper);

        $index = new IndexRepository($level3);

        $resource = $index->find(
            $this->createParameterBagMock(), 
            $this->createParameterBagMock()
        );

        $this->assertInstanceOf('Level3\Resource\Resource', $resource);

        $subResources = $resource->getResources('repositories');
        $subResource = end($subResources);

        $this->assertCount(1, $resource->getResources('repositories'));

        $links = $subResource->getLinks('actions');
        $this->assertCount(1, $links);
    }
}
