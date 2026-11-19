<?php

declare(strict_types=1);

namespace SocialAPIs;

/**
 * Your SocialAPIs account. These calls are free (no credits).
 *
 *     $usage = (new SocialAPIs\Account())->getUsage();
 */
final class Account extends Client
{
    /** Credits left and used, plan, reset date. */
    public function getUsage(): array
    {
        return $this->request('/usage');
    }

    /** Your top-up purchases. */
    public function getTopUps(): array
    {
        return $this->request('/usage/top-ups');
    }

    /** Your plan's limits. */
    public function getLimits(): array
    {
        return $this->request('/usage/limits');
    }
}
