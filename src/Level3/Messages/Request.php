<?php

namespace Level3\Messages;

use Level3\FormatterFactory;
use Level3\Processor\Wrapper\Authentication\AuthenticatedCredentials;
use Level3\Processor\Wrapper\Authentication\AnonymousCredentials;
use Level3\Processor\Wrapper\Authentication\Credentials;
use Level3\Processor\Wrapper\Authentication\User;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

use Level3\Resource\Parameters;
use Level3\Repository;

class Request extends SymfonyRequest
{
    const HEADER_SORT = 'X-Sort';
    const HEADER_RANGE = 'Range';
    const HEADER_RANGE_UNIT_SEPARATOR = '=';
    const HEADER_RANGE_SEPARATOR = '-';

    protected $availableHeaders = array(self::HEADER_SORT, self::HEADER_SORT);

    private $credentials;
    private $id;
    private $key;

    public function __construct($key, SymfonyRequest $origin)
    {           
        $query = $request = $attributes = $cookies = $files = $server = null;

        $this->key = $key;

        $content = $origin->getContent();
        if ($origin->query) $query = $origin->query->all();
        if ($origin->request) $request = $origin->request->all();
        if ($origin->attributes) $attributes = $origin->attributes->all();
        if ($origin->cookies) $cookies = $origin->cookies->all();
        if ($origin->files) $files = $origin->files->all();
        if ($origin->server) $server = $origin->server->all();

        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->credentials = new AnonymousCredentials();
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getFormatter()
    {
        $contentTypes = $this->getAcceptableContentTypes();

        return $this->getFormatterFactory()->create($contentTypes, true);
    }

    protected function getFormatterFactory()
    {
        return new FormatterFactory();
    }

    public function getCredentials()
    {
        return $this->credentials;
    }

    public function setCredentials(Credentials $credentials)
    {
        $this->credentials = $credentials;
    }

    public function getAttributes()
    {
        return new Parameters($this->attributes->all());
    }

    public function getFilters()
    {
        return new Parameters(array(
            'range' => $this->getRange(),
            'criteria' => $this->getCriteria(),
            'sort' => $this->getSort()
        ));
    }

    public function getContent($none = false)
    {
        $content = parent::getContent();

        return $this->getFormatter()->fromRequest($content);
    }

    public function getRange()
    {
        if (!$this->headers->has(self::HEADER_RANGE)) {
            return array(0, 0);
        }

        $range = $this->extractRangeFromHeader();

        if ('' === ($range[0])) {
            $range[0] = 0;
        }

        if ('' === $range[1]) {
            $range[1] = 0;
        }

        return $range;
    }

    private function extractRangeFromHeader()
    {
        $range = $this->headers->get(self::HEADER_RANGE);

        $range = explode(self::HEADER_RANGE_UNIT_SEPARATOR, $range);
        $range = $range[1];

        $range = explode(self::HEADER_RANGE_SEPARATOR, $range);
        return $range;
    }

    public function isAuthenticated()
    {
        return $this->credentials->isAuthenticated();
    }

    public function getHeader($header)
    {
        return $this->headers->get($header);
    }

    public function getCriteria()
    {
        $result = array();
        parse_str($this->getQueryString(), $result);

        return $result;
    }

    public function getSort()
    {
        if (!$this->headers->has(self::HEADER_SORT)) return null;

        $sortHeader = $this->headers->get(self::HEADER_SORT);
        return $this->parseSortHeader($sortHeader);
    }

    private function parseSortHeader($sortHeader)
    {
        $sort = array();
        $parts = explode(';', $sortHeader);
        foreach ($parts as $part) {
            list($field, $direction) = $this->parseSortPart($part);
            if ($field) $sort[$field] = $direction;
        }
        return $sort;
    }

    private function parseSortPart($part)
    {
        $match = array();
        $pattern = '/^
            \s* (?P<field>\w+) \s* # capture the field
            (?: = \s* (?P<direction>-?1) )? \s* # capture the sort direction if it is there
        $/x';
        preg_match($pattern, $part, $match);
        list($field, $direction) = $this->extractFieldAndDirectionFromRegexMatch($match);
        return array($field, $direction);
    }

    private function extractFieldAndDirectionFromRegexMatch($match)
    {
        if (isset($match['field'])) {
            $field = $match['field'];
        } else {
            $field = null;
        }

        if (isset($match['direction'])) {
            $direction = (int) $match['direction'];
        } else {
            $direction = 1;
        }

        return array($field, $direction);
    }
}
