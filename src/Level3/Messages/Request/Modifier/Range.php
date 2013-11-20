<?php

namespace Level3\Messages\Request\Modifier;


trait Range
{
    protected function initializeRange()
    {
        $range = $this->extractRangeFromParameters();
        if (!$range['offset'] && !$range['limit']) {
            $range = $this->extractRangeFromHeader();
        }

        $this->attributes->set('_offset', $range['offset']);
        $this->attributes->set('_limit', $range['limit']);
    }

    private function extractRangeFromParameters()
    {
        return [
            'offset' => $this->getAndRemoveFromQuery('_offset'),
            'limit' => $this->getAndRemoveFromQuery('_limit')
        ];
    }

    private function getAndRemoveFromQuery($param)
    {
        $value = $this->query->get($param);
        $this->query->remove($param);

        return $value;
    }

    private function extractRangeFromHeader()
    {
        $header = $this->headers->get('Range');
        if (!$header) {
            return null;
        }

        preg_match('/(?P<entity>\w+)=(?P<start>\d+)-(?P<end>\d+)/', $header, $matches);

        if (!isset($matches['start']) || !isset($matches['end'])) {
            return null;
        }

        $limit = (int) $matches['end'] - (int) $matches['start'];
        if ($limit <= 0) {
            return null;
        }

        return [
            'offset' => (int) $matches['start'],
            'limit' => $limit
        ];
    }

}
