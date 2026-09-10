<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a client-dashboard route is hit with no active session at
 * all (as opposed to a session whose access has been explicitly revoked
 * or expired) — rendered as a redirect to the login page rather than a
 * 403, since "you're not signed in" isn't an error the client caused.
 */
class ClientGuestException extends RuntimeException
{
    //
}
