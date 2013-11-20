<?php
namespace Level3\Tests\Processor\Wrapper;

use Level3\Tests\TestCase;
use Level3\Processor\Wrapper\Authenticator;
use Level3\Processor\Wrapper\Authenticator\Method;
use Mockery as m;

class AuthenticatorTest extends TestCase
{
    public function createWrapper($httpMethod)
    {
        $this->request = $this->createRequestMockSimple();

        $this->method = $this->makeAuthenticationMethodMock($this->request, $httpMethod);
        $authenticator = new Authenticator();
        $authenticator->addMethod($this->method);

        return $authenticator;
    }

    public function testClearMethods()
    {
        $method = m::mock('Level3\Processor\Wrapper\Authenticator\Method');
        $method->shouldReceive('modifyResponse');

        $wrapper = new Authenticator();
        $wrapper->addMethod($method);
        $wrapper->clearMethods();

        $this->assertCount(0, $wrapper->getMethods());
    }

    /**
     * @dataProvider provider
     */
    public function testAuthentication($method)
    {
        $repository = $this->createRepositoryMock();
        $request = $this->createResponseMock();
        $execution = function ($request) use ($request) {
            return $request;
        };

        $wrapper = $this->createWrapper($method);
        $wrapper->$method($repository, $this->request, $execution);
    }

    public function provider()
    {
        return [
            ['get'],
            ['find'],
            ['post'],
            ['patch'],
            ['put'],
            ['delete'],
        ];
    }

    public function testErrorAuthentication()
    {
        $response = $this->createResponseMock(); ;
        $execution = function ($repository, $request) use ($response) {
            return $response;
        };

        $request = $this->createRequestMockSimple();
        $method = m::mock('Level3\Processor\Wrapper\Authenticator\Method');
        $method->shouldReceive('modifyResponse')
            ->once()->with(m::type('Level3\Messages\Response'), 'error');

        $wrapper = new Authenticator();
        $wrapper->addMethod($method);

        $repository = $this->createRepositoryMock();
        $this->assertInstanceOf(
            'Level3\Messages\Response',
            $wrapper->error($repository, $request, $execution)
        );
    }

    private function makeAuthenticationMethodMock($request, $httpMethod)
    {
        $mock = m::mock('Level3\Processor\Wrapper\Authenticator\Method');
        $mock->shouldReceive('authenticateRequest')
            ->with($request, $httpMethod)->once();

        $mock->shouldReceive('modifyResponse')
            ->with(m::type('Level3\Messages\Response'), $httpMethod)->once();

        return $mock;
    }

    public function testAddProcessorWrapperDefault()
    {
        $methodA = $this->createMethodMock();
        $methodB = $this->createMethodMock();

        $wrapper = new Authenticator();

        $wrapper->addMethod($methodA);
        $wrapper->addMethod($methodB);

        $result = $wrapper->getMethods();
        $this->assertSame($methodA, $result[0]);
        $this->assertSame($methodB, $result[1]);
        $this->assertCount(2, $result);
    }

    public function testAddProcessorWrapperBoth()
    {
        $methodA = $this->createMethodMock();
        $methodB = $this->createMethodMock();

        $wrapper = new Authenticator();

        $wrapper->addMethod($methodA, Authenticator::PRIORITY_LOW);
        $wrapper->addMethod($methodB, Authenticator::PRIORITY_HIGH);

        $result = $wrapper->getMethods();
        $this->assertSame($methodA, $result[0]);
        $this->assertSame($methodB, $result[1]);
        $this->assertCount(2, $result);
    }

    public function testAddProcessorWrapperOne()
    {
        $methodA = $this->createMethodMock();
        $methodB = $this->createMethodMock();

        $wrapper = new Authenticator();

        $wrapper->addMethod($methodA);
        $wrapper->addMethod($methodB, Authenticator::PRIORITY_LOW);

        $result = $wrapper->getMethods();
        $this->assertSame($methodA, $result[1]);
        $this->assertSame($methodB, $result[0]);
        $this->assertCount(2, $result);
    }

    public function testSetAllowCredentialsIfNeeded()
    {
        $corsClass = 'Level3\Processor\Wrapper\CrossOriginResourceSharing';
        $cors = m::mock($corsClass);
        $cors->shouldReceive('setAllowCredentials')
            ->once()->with(true);

        $level3 = $this->createLevel3Mock();
        $level3->shouldReceive('getProcessorWrappersByClass')
            ->once()->with($corsClass)->andReturn($cors);

        $method = m::mock('Level3\Processor\Wrapper\Authenticator\Method');
        $method->shouldReceive('modifyResponse')
            ->once()->with(m::type('Level3\Messages\Response'), 'error');

        $auth = new Authenticator();
        $auth->addMethod($method);
        $auth->setLevel3($level3);

        $repository = $this->createRepositoryMock();
        $request = $this->createRequestMockSimple();
        $response = $this->createResponseMock();
        $execution = function ($repository, $request) use ($response) {
            return $response;
        };

        $auth->error($repository, $request, $execution);
    }
}
