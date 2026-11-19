<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 4xx (other than 401, 402, 403 and 429): a missing or invalid parameter. Fix the input before retrying.
 */
class BadRequestException extends ApiException
{
}
