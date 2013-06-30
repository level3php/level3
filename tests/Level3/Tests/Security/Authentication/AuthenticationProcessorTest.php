<?php

namespace Level3\Tests\Security\Authentication;

use Level3\Messages\RequestFactory;
use Level3\Security\Authentication\AuthenticationProcessor;
use Teapot\StatusCode;
use Mockery as m;

class AuthenticationProcessorTest extends \PHPUnit_Framework_TestCase
{
    const IRRELEVANT_SIGNATURE = 'X';
    const IRRELEVANT_RESPONSE = 'XX';

    private $requestProcessorMock;
    private $userRepositoryMock;
    private $responseFactoryMock;
    private $requestFactory;
    private $headers;
    private $request;
    private $authenticatedUser;

    private $authenticationProcessor;

    public function __construct($name = null, $data = array(), $dataName='') {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp()
    {
        $this->requestProcessorMock = m::mock('Level3\Messages\Processors\RequestProcessor');
        $this->responseFactoryMock = m::mock('Level3\Messages\ResponseFactory');
        $this->methodMock = m::mock('Level3\Security\Authentication\Method');
        $this->requestMock = m::mock('Level3\Messages\Request');

        $this->authenticationProcessor = new AuthenticationProcessor(
            $this->requestProcessorMock, $this->methodMock, $this->responseFactoryMock
        );
    }

    /**
     * @dataProvider methodsToAuthenticate
     */
    public function testFindWhenAuthenticateRequestThrowsBadCredentials($methodName)
    {
        $this->methodAuthenticateRequestshouldThrowBadCredentials();
        $this->requestFactoryShouldCreateForbiddenResponse();

        $response = $this->authenticationProcessor->$methodName($this->requestMock);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    /**
     * @dataProvider methodsToAuthenticate
     */
    public function testFindWhenAuthenticateRequestThrowsMissingCredentials($methodName)
    {
        $this->methodAuthenticateRequestshouldThrowMissingCredentials();
        $this->requestProcessorMockShouldReceiveCallTo($methodName);

        $response = $this->authenticationProcessor->$methodName($this->requestMock);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    /**
     * @dataProvider methodsToAuthenticate
     */
    public function testFindWhenAuthenticateRequestThrowsInvalidCredentials($methodName)
    {
        $this->methodAuthenticateRequestshouldThrowInvalidCredentials();
        $this->requestFactoryShouldCreateForbiddenResponse();

        $response = $this->authenticationProcessor->$methodName($this->requestMock);

        $this->assertThat($response, $this->equalTo(self::IRRELEVANT_RESPONSE));
    }

    public function methodsToAuthenticate()
    {
        return array(
            array('find'),
            array('get'),
            array('post'),
            array('put'),
            array('delete')
        );
    }

    private function methodAuthenticateRequestShouldThrowBadCredentials()
    {
        $this->methodMock
            ->shouldReceive('authenticateRequest')
            ->with($this->requestMock)->once()
            ->andThrow('Level3\Security\Authentication\Exceptions\BadCredentials');
    }

    private function methodAuthenticateRequestShouldThrowMissingCredentials()
    {
        $this->methodMock
            ->shouldReceive('authenticateRequest')
            ->with($this->requestMock)->once()
            ->andThrow('Level3\Security\Authentication\Exceptions\MissingCredentials');
    }

    private function methodAuthenticateRequestShouldThrowInvalidCredentials()
    {
        $this->methodMock
            ->shouldReceive('authenticateRequest')
            ->with($this->requestMock)->once()
            ->andThrow('Level3\Security\Authentication\Exceptions\InvalidCredentials');
    }

    private function requestFactoryShouldCreateForbiddenResponse()
    {
        $this->responseFactoryMock
            ->shouldReceive('create')->once()->with(null, StatusCode::FORBIDDEN)
            ->andReturn(self::IRRELEVANT_RESPONSE);
    }

    private function requestProcessorMockShouldReceiveCallTo($method)
    {
        $this->requestProcessorMock
            ->shouldReceive($method)->once()
            ->with($this->requestMock)
            ->andReturn(self::IRRELEVANT_RESPONSE);
    }
}
