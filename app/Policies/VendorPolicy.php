<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class VendorPolicy
{
    use AllowsAdminSystemAccess;
}
