<?php

namespace Level3\Exceptions;

use Teapot\StatusCode;

class TooManyRequest extends HTTPException
{
    public function __construct($message = '')
    {
        parent::__construct($message, StatusCode::TOO_MANY_REQUESTS);
    }
}
