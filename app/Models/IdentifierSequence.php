<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'prefix', 'next_number', 'padding'])]
class IdentifierSequence extends Model {}
