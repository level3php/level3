<?php
/*
 * This file is part of the Level3 package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Level3\Tests;

use Level3\Resource\Resource;
use Level3\Messages\ExceptionResponse;
use Level3\Resource\Formatter\HAL\JsonFormatter;
use Level3\Exceptions\NotAcceptable;

use Teapot\StatusCode;
use Mockery as m;

class ExceptionResponseTest extends TestCase
{
  
    public function testCreateFromException()
    {
        $formatter = $this->createFormatterMock();
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
        $formatter = $this->createFormatterMock();
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

        $response->setFormatter(new JsonFormatter);

        $response->setDebug(true);
        $this->assertNotEquals('', $response->getContent());

        $this->assertTrue($response->getDebug());

        $response->setDebug(false);
        $this->assertEquals('', $response->getContent());
    }
}
