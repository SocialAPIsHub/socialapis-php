<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * Base class of every exception this SDK throws. Catch it for one handler
 * covering any SDK failure.
 */
class SocialAPIsException extends \RuntimeException
{
}
