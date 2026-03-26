<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEscalationSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'ix_head_after_hours',
        'ceo_after_hours',
        'level_1_role_slug',
        'level_2_role_slug',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'ix_head_after_hours' => 'integer',
        'ceo_after_hours' => 'integer',
    ];

    public static function current(): self
    {
        $setting = self::query()->latest()->first();

        if ($setting) {
            return $setting;
        }

        return self::query()->create([
            'is_enabled' => true,
            'ix_head_after_hours' => 6,
            'ceo_after_hours' => 24,
            'level_1_role_slug' => 'ix_head',
            'level_2_role_slug' => 'ceo',
            'updated_by' => null,
        ]);
    }
}
