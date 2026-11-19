<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 5xx: the API could not answer. Safe to retry with a backoff.
 */
class ApiServerException extends ApiException
{
}
