<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class FeeSettingPolicy
{
    use AllowsAdminSystemAccess;
}
