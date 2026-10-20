<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('actor_id')
                    ->relationship('actor', 'name'),
                TextInput::make('auditable_type'),
                TextInput::make('auditable_id')
                    ->numeric(),
                TextInput::make('event')
                    ->required(),
                TextInput::make('old_values'),
                TextInput::make('new_values'),
                TextInput::make('ip_address'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
            ]);
    }
}
