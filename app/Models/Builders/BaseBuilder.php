<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class BaseBuilder extends Builder
{
    public function search()
    {
        $search = request()->search;

        if (!$search) {
            return $this;
        }

        return $this->where(function ($query) use ($search) {
            $query->whereRaw('LOWER(`name`) LIKE ?', [strtolower("{$search}%")])
                  ->orWhereRaw('LOWER(`name`) LIKE ?', [strtolower("%{$search}%")]);
        })->orderByRaw("
            CASE
                WHEN LOWER(`name`) LIKE ? THEN 1
                ELSE 2
            END
        ", [strtolower("{$search}%")]);
    }
}
