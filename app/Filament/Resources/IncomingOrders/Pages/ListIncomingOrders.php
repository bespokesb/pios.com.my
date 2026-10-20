<?php

namespace App\Filament\Resources\IncomingOrders\Pages;

use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomingOrders extends ListRecords
{
    protected static string $resource = IncomingOrderResource::class;
}
