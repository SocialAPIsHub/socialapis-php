<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * Network failure, timeout or an answer that isn't JSON. Usually transient:
 * safe to retry with a backoff.
 */
class ApiConnectionException extends SocialAPIsException
{
}
