<?php

namespace App\Policies;

use App\Policies\Concerns\AllowsAdminSystemAccess;

class ProductTypePolicy
{
    use AllowsAdminSystemAccess;
}
