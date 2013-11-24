<?php

namespace Level3\Tests;

use Level3\Messages\Request;
use Mockery as m;

class RequestTest extends TestCase
{
    const IRRELEVANT_KEY = 'X';
    const IRRELEVANT_ID = 'XX';
    const IRRELEVANT_URLENCODED_CONTENT = 'foo=bar';
    const IRRELEVANT_JSON_CONTENT = '{"foo":"bar"}';
    const IRRELEVANT_XML_CONTENT = '<xml><foo><qux>bar</qux></foo></xml>';

    private $dummySymfonyRequest;
    private $request;

    public function setUp()
    {
        $this->request = new Request();
    }

    public function testFormatContentDefault()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', [], [], [], [
                
            ],
            self::IRRELEVANT_URLENCODED_CONTENT
        );

        $this->assertSame(['foo' => 'bar'], $request->request->all());
    }

    public function testFormatContentContentType()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', [], [], [], [
                'CONTENT_TYPE' => 'application/json'
            ],
            self::IRRELEVANT_JSON_CONTENT
        );

        $this->assertSame(['foo' => 'bar'], $request->request->all());
    }

    public function testFormatContentJSON()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', ['_format' => 'json'], [], [], [],
            self::IRRELEVANT_JSON_CONTENT
        );

        $this->assertSame(['foo' => 'bar'], $request->request->all());
    }

    public function testFormatContentXML()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', ['_format' => 'xml'], [], [], [],
            self::IRRELEVANT_XML_CONTENT
        );

        $this->assertSame(['foo' => ['qux' => 'bar']], $request->request->all());
    }

    /**
     * @expectedException Level3\Exceptions\BadRequest
     */
    public function testFormatContentInvalid()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', ['_format' => 'xml'], [], [], [],
            self::IRRELEVANT_JSON_CONTENT
        );
    }

    /**
     * @expectedException Level3\Exceptions\BadRequest
     */
    public function testFormatContentNonSupportedFormat()
    {
        $request = Request::create(
            'http://example.com/jsonrpc', 'POST', ['_format' => 'foo'], [], [], [],
            self::IRRELEVANT_JSON_CONTENT
        );
    }

    public function testInitializeRange()
    {
        $request = Request::create('http://example.com/', 'GET');

        $this->assertNull($request->attributes->get('_offset'));
        $this->assertNull($request->attributes->get('_limit'));
    }

    public function testInitializeRangeHeader()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_Range' => 'entity=10-30'
        ]);

        $this->assertSame(10, $request->attributes->get('_offset'));
        $this->assertSame(21, $request->attributes->get('_limit'));
    }

    public function testInitializeRangeHeaderExactRecord()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_Range' => 'entity=30-30'
        ]);

        $this->assertSame(30, $request->attributes->get('_offset'));
        $this->assertSame(1, $request->attributes->get('_limit'));
    }

    public function testInitializeRangeHeaderNegative()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_Range' => 'entity=40-30'
        ]);

        $this->assertNull($request->attributes->get('_offset'));
        $this->assertNull($request->attributes->get('_limit'));
    }


    public function testInitializeRangeHeaderOpen()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_Range' => 'entity=10-'
        ]);

        $this->assertNull($request->attributes->get('_offset'));
        $this->assertNull($request->attributes->get('_limit'));
    }

    public function testInitializeRangeParam()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_limit' => 10,
            '_offset' => 40
        ]);

        $this->assertSame(40, $request->attributes->get('_offset'));
        $this->assertSame(10, $request->attributes->get('_limit'));
    }

    public function testInitializeRangeConflict()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_limit' => 10,
            '_offset' => 40
        ], [], [], [
            'HTTP_Range' => 'entity=10-30'
        ]);

        $this->assertSame(40, $request->attributes->get('_offset'));
        $this->assertSame(10, $request->attributes->get('_limit'));
    }

    public function testInitializeSort()
    {
        $request = Request::create('http://example.com/', 'GET');

        $this->assertNull($request->attributes->get('_sort'));
    }

    public function testInitializeSortHeader()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_X-Sort' => ' foo = 1; bar;baz  =-1'
        ]);

        $this->assertSame([
            'foo' => 1,
            'bar' => 1,
            'baz' => -1
        ], $request->attributes->get('_sort'));
    }

    public function testInitializeSortParam()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_sort' => 'foo,-bar',
        ]);

        $this->assertSame([
            'foo' => 1,
            'bar' => -1
        ], $request->attributes->get('_sort'));
    }

    public function testInitializeSortConflict()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_sort' => 'foo,-bar',
        ], [], [], [
            'HTTP_X-Sort' => ' foo = 1; bar;baz  =-1'
        ]);

        $this->assertSame([
            'foo' => 1,
            'bar' => -1
        ], $request->attributes->get('_sort'));
    }

    public function testInitializeSortConflictMissingField()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_X-Sort' => ' foo = 1; bar;  =-1'
        ]);

        $this->assertSame([
            'foo' => 1,
            'bar' => 1
        ], $request->attributes->get('_sort'));
    }

    public function testInitializeExpand()
    {
        $request = Request::create('http://example.com/', 'GET');

        $this->assertNull($request->attributes->get('_expand'));
    }

    public function testInitializeExpandHeader()
    {
        $request = Request::create('http://example.com/', 'GET', [], [], [], [
            'HTTP_X-Expand-Links' => ' foo,qux.bar'
        ]);

        $this->assertSame([
            ['foo'],
            ['qux','bar']
        ], $request->attributes->get('_expand'));
    }

    public function testInitializeExpandParam()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_expand' => 'foo,qux.bar',
        ]);

        $this->assertSame([
            ['foo'],
            ['qux','bar']
        ], $request->attributes->get('_expand'));
    }

    public function testInitializeExpandConflict()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_expand' => 'foo,qux.bar',
        ], [], [], [
            'HTTP_X-Expand-Links' => ' bar,qux.bar'
        ]);

        $this->assertSame([
            ['foo'],
            ['qux','bar']
        ], $request->attributes->get('_expand'));
    }

    public function testGetAcceptableContentTypes()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_expand' => 'foo,qux.bar',
        ], [], [], [
            'HTTP_ACCEPT' => 'foo/bar'
        ]);

        $this->assertSame(['foo/bar'], $request->getAcceptableContentTypes());
    }


    public function testGetAcceptableContentTypesEmpty()
    {
        $request = Request::create('http://example.com/', 'GET', [
            '_expand' => 'foo,qux.bar',
        ], [], [], [
            'HTTP_ACCEPT' => null
        ]);

        $this->assertSame(['*/*'], $request->getAcceptableContentTypes());
    }
}
