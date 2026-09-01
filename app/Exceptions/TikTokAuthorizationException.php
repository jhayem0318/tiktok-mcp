<?php

namespace App\Exceptions;

use RuntimeException;

class TikTokAuthorizationException extends RuntimeException
{
    // This exception intentionally carries no token request details or secrets.
}
