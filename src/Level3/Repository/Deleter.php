<?php

namespace Level3\Repository;

use Symfony\Component\HttpFoundation\ParameterBag;

interface Deleter
{
    public function delete(ParameterBag $attributes);
}
