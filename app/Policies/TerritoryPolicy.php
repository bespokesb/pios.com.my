<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class TerritoryPolicy
{
    use AllowsAdminSystemAccess;
}
