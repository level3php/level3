<?php

namespace Level3\Tests;

use Teapot\StatusCode;
use Level3\Repository;
use Level3\Processor;
use Level3\Processor\Wrapper;
use Level3\Messages\Request;
use Level3\Messages\Response;
use Level3\Exceptions\NotFound;

use Closure;
use RuntimeException;

class ProcessorTest extends TestCase
{
    private $processor;

    public function setUp()
    {
        $this->level3 = $this->createLevel3Mock();
        $this->level3->shouldReceive('getProcessorWrappers')
            ->withNoArgs()->andReturn([
                new WrapperMock(),
                new WrapperMock(),
                new WrapperMock()
            ]);

        $this->processor = new Processor();
        $this->processor->setLevel3($this->level3);
    }

    /**
     * @expectedException Level3\Exceptions\NotFound
     */
    public function testMissingRepository()
    {
        $this->level3->shouldReceive('getRepository')
            ->with(self::IRRELEVANT_KEY)
            ->once()
            ->andThrow(new RuntimeException());

        $request = $this->createRequestMock();
        $this->processor->get(self::IRRELEVANT_KEY, $request);
    }

    /**
     * @expectedException Level3\Exceptions\NotImplemented
     */
    public function testOptions()
    {
        $this->repository = $this->createRepositoryMock();

        $this->level3->shouldReceive('getRepository')
            ->with(self::RELEVANT_KEY)
            ->once()
            ->andReturn($this->repository);

        $request = $this->createRequestMockSimple();
        $this->processor->options(self::RELEVANT_KEY, $request);
    }

    /**
     * @expectedException Level3\Exceptions\NotAcceptable
     */
    public function testNotMatchingFormatter()
    {
        $repository = $this->createRepositoryMock();
    
        $this->level3->shouldReceive('getRepository')
            ->with(self::RELEVANT_KEY)
            ->once()
            ->andReturn($repository);


        $request = $this->createRequestMockSimple();
        $request
            ->shouldReceive('getAcceptableContentTypes')
            ->withNoArgs()->once()
            ->andReturn(['foo/bar']);

        $this->level3->shouldReceive('getFormatWriterByContentType')
            ->with('foo/bar')->once()
            ->andReturn(null);

        $request->attributes
            ->shouldReceive('get');


        $repository->shouldReceive('get')->andReturn($this->createResourceMock());
        $this->processor->get(self::RELEVANT_KEY, $request);
    }

    /**
     * @dataProvider errorProvider
     */
    public function testError($statusCode, $exception)
    {
        $repository = $this->createRepositoryMock();
    
        $this->level3->shouldReceive('getRepository')
            ->with(self::RELEVANT_KEY)
            ->once()
            ->andReturn($repository);

        $request = $this->createRequestMockSimple();
        $request
            ->shouldReceive('getAcceptableContentTypes')
            ->withNoArgs()->once()
            ->andReturn(['foo/bar']);

        $formatter = $this->createFormatWriterMock();
        $this->level3->shouldReceive('getFormatWriterByContentType')
            ->with('foo/bar')->once()
            ->andReturn($formatter);


        $response = $this->processor->error(self::RELEVANT_KEY, $request, $exception);

        $this->assertSame($statusCode, $response->getStatusCode());
    }

    public function errorProvider()
    {
        return [
            [
                StatusCode::INTERNAL_SERVER_ERROR,
                new \Exception
            ],
            [   StatusCode::NOT_FOUND, 
                new NotFound
            ]
        ];
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testMethods(
        $statusCode, $method, $repository, $resource,
        $attributes, $query, $request, $expand
    )
    {
        $this->level3
            ->shouldReceive('getRepository')->with(self::RELEVANT_KEY)->once()
            ->andReturn($repository);

        $httpRequest = $this->createRequestMock();
        $httpRequest->attributes = $attributes;
        $httpRequest->query = $query;
        $httpRequest->request = $request;

        if ($statusCode != StatusCode::NO_CONTENT) {
            $httpRequest
                ->shouldReceive('getAcceptableContentTypes')
                ->withNoArgs()->once()
                ->andReturn(['foo/bar']);

            $formatter = $this->createFormatWriterMock();
            $this->level3->shouldReceive('getFormatWriterByContentType')
                ->with('foo/bar')->once()
                ->andReturn($formatter);
        }
 
        if ($query) {
            $repository
                ->shouldReceive($method)->with($attributes, $query)->once()
                ->andReturn($resource);
        } else if ($request) {
            $repository
                ->shouldReceive($method)->with($attributes, $request)->once()
                ->andReturn($resource);
        } else {
            $repository
                ->shouldReceive($method)->with($attributes)->once()
                ->andReturn($resource);
        }

        $attributes
            ->shouldReceive('get')->with('_expand')->once()
            ->andReturn($expand);

        if ($expand) {
            $resource
                ->shouldReceive('expandLinkedResourcesTree')->with($expand[0])->once()
                ->andReturn(null);
        }
        
        $response = $this->processor->$method(self::RELEVANT_KEY, $httpRequest);

        $this->assertSame($statusCode, $response->getStatusCode());
        if ($statusCode != StatusCode::NO_CONTENT) {
            $this->assertSame($resource, $response->getResource());
        }
    }

    public function methodsProvider()
    {
        return [
            [
                StatusCode::OK,
                'find', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                $this->createParameterBagMock(),
                null,
                null
            ],
            [
                StatusCode::OK,
                'find', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                $this->createParameterBagMock(),
                null,
                [['foo']]
            ],
            [
                StatusCode::OK,
                'get', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                null,
                null
            ],
            [
                StatusCode::CREATED,
                'post', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                $this->createParameterBagMock(),
                null
            ],
            [
                StatusCode::OK,
                'patch', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                $this->createParameterBagMock(),
                null
            ],
            [
                StatusCode::OK,
                'put', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                $this->createParameterBagMock(),
                null
            ],
            [
                StatusCode::NO_CONTENT,
                'delete', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                null,
                null
            ],
            [
                StatusCode::NO_CONTENT,
                'delete', 
                $this->createFinderMock(),
                $this->createResourceMock(),
                $this->createParameterBagMock(), 
                null,
                null,
                null
            ]
        ];
    }

    protected function level3ShouldHavePair($key, $repository)
    {
        $this->level3->shouldReceive('getRepository')
            ->with($key)->once()->andReturn($repository);
    }
}

class WrapperMock extends Wrapper
{
    protected function processRequest(
        Repository $repository, 
        Request $request, 
        Callable $execution, 
        $method
    )
    {
        $response = $execution($repository, $request);

        return $response;
    }
}
