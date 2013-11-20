<?php

namespace Level3\Messages\Request\Modifier;


trait Sort
{
    protected function initializeSort()
    {
        $sort = $this->extractSortFromParameters();
        if (!$sort) {
            $sort = $this->extractSortFromHeader();
        }
        
        $this->attributes->set('_sort', $sort);
    }

    private function extractSortFromParameters()
    {
        if (!$this->query->has('_sort')) {
            return null;
        }

        $sort = [];        
        $parts = explode(',', $this->query->get('_sort'));
        foreach ($parts as $field) {
            if (substr($field, 0, 1) == '-') {
                $sort[substr($field, 1)] = -1;
            } else {
                $sort[$field] = 1;
            }
        }

        $this->query->remove('_sort');
        return $sort;
    }

    private function extractSortFromHeader()
    {
        $header = $this->headers->get('X-Sort');
        if (!$header) {
            return null;
        }

        $sort = [];
        $parts = explode(';', $header);
        foreach ($parts as $part) {
            list($field, $direction) = $this->parseSortPart($part);
            if ($field) {
                $sort[$field] = $direction;
            }
        }

        return $sort;
    }

    private function parseSortPart($part)
    {
        $match = [];
        $pattern = '/^
            \s* (?P<field>\w+) \s* # capture the field
            (?: = \s* (?P<direction>-?1) )? \s* # capture the sort direction if it is there
        $/x';
        preg_match($pattern, $part, $match);
        list($field, $direction) = $this->extractFieldAndDirectionFromRegexMatch($match);

        return [$field, $direction];
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

        return [$field, $direction];
    }
}
