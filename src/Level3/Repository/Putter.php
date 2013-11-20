<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Putter
{
    public function put(ParameterBag $attributes, Array $data);
}
