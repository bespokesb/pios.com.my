<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class CustomerTagPolicy
{
    use AllowsAdminSystemAccess;
}
