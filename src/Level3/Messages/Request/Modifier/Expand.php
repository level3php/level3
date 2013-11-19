<?php

namespace Level3\Messages\Request\Modifier;


trait Expand
{
    protected function initializeExpand()
    {
        $expand = $this->extractExpandFromParameters();
        if (!$expand) {
            $expand = $this->extractExpandFromHeader();
        }
        
        $this->attributes->set('expand', $expand);
    }

    private function extractExpandFromParameters()
    {
        if (!$this->query->has('_expand')) {
            return null;
        }

        $expand = $this->query->get('_expand');
        $this->query->remove('_expand');

        return $this->parseExpandString($expand);
    }

    private function extractExpandFromHeader()
    {
        $header = $this->headers->get('X-Expand-Links');
        if (!$header) {
            return null;
        }

        return $this->parseExpandString($header);
    }

    private function parseExpandString($string)
    {
        $expand = explode(',', trim($string));
        foreach ($expand as &$part) {
            $part = explode('.', trim($part));
        }

        return $expand;
    }
}
