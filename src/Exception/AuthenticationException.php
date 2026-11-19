<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 401: the API key is missing, invalid or was replaced. Retrying won't help.
 */
class AuthenticationException extends ApiException
{
}
