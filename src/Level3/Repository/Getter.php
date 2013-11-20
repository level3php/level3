<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Getter
{
    public function get(ParameterBag $attributes);
}
