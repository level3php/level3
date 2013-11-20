<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Patcher
{
    public function patch(ParameterBag $attributes, Array $data);
}
