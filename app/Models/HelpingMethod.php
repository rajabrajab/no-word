<?php

namespace App\Models;

use App\Models\Builders\BaseBuilder;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpingMethod extends BaseModel
{
    use SoftDeletes;

    public function newEloquentBuilder($query): BaseBuilder
    {
        return new BaseBuilder($query);
    }
}

