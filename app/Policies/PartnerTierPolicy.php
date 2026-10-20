<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class PartnerTierPolicy
{
    use AllowsAdminSystemAccess;
}
