<?php

namespace Level3\Tests;

use Level3\Resource\Resource;
use Level3\Messages\ExceptionResponse;
use Level3\Resource\Format\Writer\HAL\JsonWriter;
use Level3\Exceptions\NotAcceptable;

use Teapot\StatusCode;

class ExceptionResponseTest extends TestCase
{

    public function testCreateFromException()
    {
        $formatter = $this->createFormatWriterMock();
        $request = $this->createRequestMockSimple();

        $exception = new \Exception('foo');
        $response = ExceptionResponse::createFromException($exception);

        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $resource = $response->getResource();
        $this->assertInstanceOf('Level3\Resource\Resource', $resource);

        $data = $resource->getData();
        $this->assertSame('Exception', $data['type']);
        $this->assertSame('foo', $data['message']);
        $this->assertCount(13, $data['trace']);
    }

    public function testCreateFromHTTPException()
    {
        $formatter = $this->createFormatWriterMock();
        $request = $this->createRequestMockSimple();

        $exception = new NotAcceptable('foo');
        $response = ExceptionResponse::createFromException($exception);

        $this->assertSame(StatusCode::NOT_ACCEPTABLE, $response->getStatusCode());

        $resource = $response->getResource();
        $this->assertInstanceOf('Level3\Resource\Resource', $resource);

        $data = $resource->getData();
        $this->assertSame('Level3\Exceptions\NotAcceptable', $data['type']);
        $this->assertSame('foo', $data['message']);
        $this->assertCount(13, $data['trace']);

        $response->setFormatWriter(new JsonWriter);

        $response->setDebug(true);
        $this->assertNotEquals('', $response->getContent());

        $this->assertTrue($response->getDebug());

        $response->setDebug(false);
        $this->assertEquals('', $response->getContent());
    }
}
