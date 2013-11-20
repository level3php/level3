<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Poster
{
    public function post(ParameterBag $attributes, Array $data);
}
