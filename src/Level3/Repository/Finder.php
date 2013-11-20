<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Finder
{
    public function find(ParameterBag $attributes, ParameterBag $query);
}
