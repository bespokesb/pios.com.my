<?php

namespace App\Filament\Resources\CustomerTags\Pages;

use App\Filament\Resources\CustomerTags\CustomerTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerTag extends CreateRecord
{
    protected static string $resource = CustomerTagResource::class;
}
