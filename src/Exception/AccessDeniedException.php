<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 403 (other than credits): the account is deactivated (code SUBSCRIPTION_PAUSED)
 * or its plan has expired (code SUBSCRIPTION_EXPIRED). See errorCode().
 */
class AccessDeniedException extends ApiException
{
    public function errorCode(): ?string
    {
        return isset($this->body['code']) && is_string($this->body['code']) ? $this->body['code'] : null;
    }
}
