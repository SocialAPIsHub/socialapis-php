<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 402 or 403 "Insufficient credits": not enough credits left. Top up or upgrade at https://socialapis.io/pricing, then retry.
 */
class InsufficientCreditsException extends ApiException
{
}
