<?php

namespace Level3\Tests;

use Hal\Resource;
use Level3\ResourceAccesor;
use Teapot\StatusCode;
use Mockery as m;

class ResourceAccessorTest extends TestCase
{
    const IRRELEVANT_KEY = 'X';
    const IRRELEVANT_ID = 'Y';
    const IRRELEVANT_RESPONSE = 'XX';
    const IRRELEVANT_RESOURCE = '2X';

    private $responseFactoryMock;
    private $resourceAccessor;

    public function __construct($name = null, $data = array(), $dataName='') {
        parent::__construct($name, $data, $dataName);
        $this->resourceHubMock = m::mock('Level3\ResourceHub');
    }

    public function setUp()
    {
        $this->responseFactoryMock = m::mock('Level3\ResponseFactory');
        $this->resourceHubMock = m::mock('Level3\ResourceHub');
        $this->resourceAccessor = new ResourceAccesor($this->resourceHubMock, $this->responseFactoryMock);
    }

    /**
     * @test
     */
    public function shouldFind()
    {
        $Deleter = $this->createFinderMock();
        $Deleter->shouldReceive('find')->withNoArgs()->once()->andReturn(array());
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $Deleter);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(array(), StatusCode::OK, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->find(self::IRRELEVANT_KEY);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    /**
     * @test
     * @dataProvider exceptionMapping
     */
    public function findShouldFailWithException($exception, $code)
    {
        $finderMock = $this->createFinderMock();
        $finderMock->shouldReceive('find')->andThrow($exception);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $finderMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, $code, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->find(self::IRRELEVANT_KEY);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createFinderMock()
    {
        return m::mock('Level3\ResourceRepository\Finder');
    }

    /**
     * @test
     */
    public function shouldGet()
    {
        $getterMock = $this->createGetterMock();
        $getterMock->shouldReceive('get')->with(self::IRRELEVANT_ID)->once()->andReturn(self::IRRELEVANT_RESOURCE);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $getterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(self::IRRELEVANT_RESOURCE, StatusCode::OK, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->get(self::IRRELEVANT_KEY, self::IRRELEVANT_ID);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    /**
     * @test
     * @dataProvider exceptionMapping
     */
    public function getShouldFailWithException($exception, $code)
    {
        $finderMock = $this->createGetterMock();
        $finderMock->shouldReceive('get')->andThrow($exception);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $finderMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, $code, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->get(self::IRRELEVANT_KEY, self::IRRELEVANT_ID);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createGetterMock()
    {
        return m::mock('Level3\ResourceRepository\Getter');
    }

    /**
     * @test
     */
    public function shouldPost()
    {
        $posterMock = $this->createPosterAndGetterMock();
        $posterMock->shouldReceive('post')->with(self::IRRELEVANT_ID, array())->once();
        $posterMock->shouldReceive('get')->with(self::IRRELEVANT_ID)->once()->andReturn(self::IRRELEVANT_RESOURCE);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $posterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(self::IRRELEVANT_RESOURCE, StatusCode::OK, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->post(self::IRRELEVANT_KEY, self::IRRELEVANT_ID, array());

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createPosterAndGetterMock()
    {
        return m::mock('Level3\ResourceRepository\Poster, Level3\ResourceRepository\Getter');
    }

    /**
     * @test
     * @dataProvider exceptionMapping
     */
    public function postShouldFailWithException($exception, $code)
    {
        $posterMock = $this->createPosterMock();
        $posterMock->shouldReceive('post')->with(self::IRRELEVANT_ID, array())->once()->andThrow($exception);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $posterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, $code, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->post(self::IRRELEVANT_KEY, self::IRRELEVANT_ID, array());

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createPosterMock()
    {
        return m::mock('Level3\ResourceRepository\Poster');
    }

    /**
     * @test
     */
    public function shouldPut()
    {
        $putterMock = $this->createPutterAndGetterMock();
        $putterMock->shouldReceive('put')->with(array())->once()->andReturn(self::IRRELEVANT_ID);
        $putterMock->shouldReceive('get')->with(self::IRRELEVANT_ID)->once()->andReturn(self::IRRELEVANT_RESOURCE);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $putterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(self::IRRELEVANT_RESOURCE, StatusCode::CREATED, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->put(self::IRRELEVANT_KEY, array());

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createPutterAndGetterMock()
    {
        return m::mock('Level3\ResourceRepository\Putter, Level3\ResourceRepository\Getter');
    }

    /**
     * @test
     * @dataProvider exceptionMapping
     */
    public function putShouldFailWithException($exception, $code)
    {
        $putterMock = $this->createPutterMock();
        $putterMock->shouldReceive('put')->with(array())->once()->andThrow($exception);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $putterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, $code, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->put(self::IRRELEVANT_KEY, array());

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createPutterMock()
    {
        return m::mock('Level3\ResourceRepository\Putter');
    }

    /**
     * @test
     */
    public function shouldDelete()
    {
        $deleterMock = $this->createDeleterMock();
        $deleterMock->shouldReceive('delete')->with(self::IRRELEVANT_ID)->once();
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $deleterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, StatusCode::OK, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->delete(self::IRRELEVANT_KEY, self::IRRELEVANT_ID);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    /**
     * @test
     * @dataProvider exceptionMapping
     */
    public function deleteShouldFailWithException($exception, $code)
    {
        $deleterMock = $this->createDeleterMock();
        $deleterMock->shouldReceive('delete')->with(self::IRRELEVANT_ID)->once()->andThrow($exception);
        $this->resourceHubShouldHavePair(self::IRRELEVANT_KEY, $deleterMock);
        $this->responseFactoryCreateResponseShouldReceiveAndReturn(null, $code, self::IRRELEVANT_RESPONSE);

        $response = $this->resourceAccessor->delete(self::IRRELEVANT_KEY, self::IRRELEVANT_ID);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    private function createDeleterMock()
    {
        return m::mock('Level3\ResourceRepository\Deleter');
    }

    private function responseFactoryCreateResponseShouldReceiveAndReturn($value, $statusCode, $return)
    {
        $this->responseFactoryMock->shouldReceive('createResponse')->with($value, $statusCode)->once()->andReturn($return);
    }

    public function resourceRepositoryFindShouldThrow($exception)
    {
        $this->resourceRepositoryMock->shouldReceive('find')->withNoArgs()->once()->andThrow($exception);
    }

    public function exceptionMapping()
    {
        return array(
            array('Level3\ResourceRepository\Exception\Conflict', StatusCode::CONFLICT),
            array('Level3\ResourceRepository\Exception\DataError', StatusCode::BAD_REQUEST),
            array('Level3\ResourceRepository\Exception\NoContent', StatusCode::NO_CONTENT),
            array('Level3\ResourceRepository\Exception\NotFound', StatusCode::NOT_FOUND),
            array('\Exception', StatusCode::INTERNAL_SERVER_ERROR)
        );
    }
}