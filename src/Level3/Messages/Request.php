<?php

namespace Level3\Messages;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Level3\Messages\Request\Modifier;
use Level3\Exceptions\BadRequest;
use Level3\Resource\Format\Writer;
use Hampel\Json\Json;
use Hampel\Json\JsonException;
use SimpleXMLElement;
use Exception;

class Request extends BaseRequest
{
    use Modifier\Range;
    use Modifier\Sort;
    use Modifier\Expand;

    protected static function initializeFormats()
    {
        parent::initializeFormats();
        static::$formats['txt'][] = 'application/x-www-form-urlencoded';
    }

    public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->formatContent();
        $this->initializeRange();
        $this->initializeSort();
        $this->initializeExpand();
    }

    protected function formatContent()
    {
        if (!$this->getContent()) {
            return;
        }

        $format = $this->getRequestFormat(null);
        if (!$format) {
            $format = $this->getContentType();
        }

        switch ($format) {
            case 'json':
                $request = $this->getJSONContentAsArray();
                break;
            case 'xml':
                $request = $this->getXMLContentAsArray();
                break;
            case 'txt':
                $request = $this->getURLEncodedContentAsArray();
                break;
            default:
                $request = null;
                break;
        }

        if (is_array($request)) {
            $this->request->replace($request);
        }
    }

    private function getJSONContentAsArray()
    {
        try {
            return Json::decode($this->getContent(), true);
        } catch (JsonException $e) {
            throw new BadRequest();
        }
    }

    private function getXMLContentAsArray()
    {
        $content = $this->getContent();

        try {
            return $this->xmlToArray(new SimpleXMLElement($content));
        } catch (Exception $e) {
            throw new BadRequest();
        }
    }

    private function getURLEncodedContentAsArray()
    {
        $data = [];
        parse_str($this->getContent(), $data);

        return $data;
    }

    private function xmlToArray(SimpleXMLElement $xml)
    {
        $data = (array) $xml;
        foreach ($data as &$value) {
            if ($value instanceof SimpleXMLElement) {
                $value = $this->xmlToArray($value);
            }
        }

        return $data;
    }

    public function getAcceptableContentTypes()
    {
        $contentTypes = parent::getAcceptableContentTypes();
        if (!$contentTypes) {
            $contentTypes = [Writer::CONTENT_TYPE_ANY];
        }

        return $contentTypes;
    }
}
