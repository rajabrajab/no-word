<?php

namespace App\Models;

class PoliciesCondition extends BaseModel
{
    public $translatable = ['title', 'content'];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
    ];
}
