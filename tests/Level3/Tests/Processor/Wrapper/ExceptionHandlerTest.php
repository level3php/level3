<?php
namespace Level3\Tests;

use Level3\Processor\Wrapper\ExceptionHandler;
use Level3\Exceptions\NotFound;
use Exception;

class ExceptionHandlerTest extends TestCase
{
    private $wrapper;

    public function setUp()
    {
        $this->processor = $this->createProcessorMock();

        $this->level3 = $this->createLevel3Mock();
        $this->level3->shouldReceive('getProcessor')->andReturn($this->processor);

        $this->wrapper = new ExceptionHandler();
        $this->wrapper->setLevel3($this->level3);
    }

    public function testErrorAuthentication()
    {
        $response = $this->createResponseMock();

        $repository = $this->createRepositoryMock();
        $request = $this->createRequestMockSimple();
        $wrapper = new ExceptionHandler();

        $execution = function ($repository, $request) use ($response) {
            return $response;
        };

        $this->assertInstanceOf(
            'Level3\Messages\Response',
            $wrapper->error($repository, $request, $execution)
        );
    }

    /**
     * @dataProvider provider
     */
    public function testExceptionHandling($method, $exception)
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->shouldReceive('getKey')
            ->withNoArgs()->once()
            ->andReturn('key');

        $request = $this->createRequestMock(null, null, null);
        $this->processor
            ->shouldReceive('error')->once()
            ->with('key', $request, $exception);

        $expected = $this->wrapper->$method(
            $repository, $request,
            function($repository, $request) use ($exception) {
                throw $exception;
            }
        );
    }

    public function provider()
    {
        return [
            ['get', new NotFound()],
            ['get', new Exception()],
            ['find', new NotFound()],
            ['find', new Exception()],
            ['post', new NotFound()],
            ['post', new Exception()],
            ['patch', new NotFound()],
            ['patch', new Exception()],
            ['put', new NotFound()],
            ['put', new Exception()],
            ['delete', new NotFound()],
            ['delete', new Exception()],
            ['options', new Exception()]
        ];
    }
}
