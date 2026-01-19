<?php

namespace App\Exceptions;

use Exception;

class AlreadyFollowingException extends Exception
{
    protected $message = 'Already following this action';

    protected $code = 409;
}

class NotFollowingException extends Exception
{
    protected $message = 'Not following this action';

    protected $code = 404;
}
