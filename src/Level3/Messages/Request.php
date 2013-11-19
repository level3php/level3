<?php

namespace Level3\Messages;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Level3\Messages\Request\Modifier;
use Level3\Exceptions\BadRequest;
use Exception;

use SimpleXMLElement;

class Request extends BaseRequest
{
    use Modifier\Range;
    use Modifier\Sort;
    use Modifier\Expand;

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
        if (!$this->content) {
            return;
        }

        switch ($this->getRequestFormat()) {
            case 'json':
                $request = $this->getJSONContentAsArray();
                break;
            case 'xml':
                $request = $this->getXMLContentAsArray();
                break;
            default:
                $request = null;
                break;
        }
   
        if (!is_array($request)) {
            throw new BadRequest();
        }

        $this->request->replace($request);
    }

    private function getJSONContentAsArray()
    {
        return json_decode($this->getContent(), true);
    }

    private function getXMLContentAsArray()
    {
        $content = $this->getContent();

        try {
            return $this->xmlToArray(new SimpleXMLElement($content));
        } catch (Exception $e) {
            return null;
        }
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
}
