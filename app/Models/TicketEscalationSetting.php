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
            // IRINN portal no longer uses legacy IX roles.
            // Normalize saved escalation role slugs to the active IRINN chain.
            $needsUpdate = false;

            if (($setting->level_1_role_slug ?? null) === 'ix_head') {
                $setting->level_1_role_slug = 'hostmaster';
                $needsUpdate = true;
            }

            if (($setting->level_2_role_slug ?? null) === 'ceo') {
                $setting->level_2_role_slug = 'billing';
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $setting->save();
            }

            return $setting;
        }

        return self::query()->create([
            'is_enabled' => true,
            'ix_head_after_hours' => 6,
            'ceo_after_hours' => 24,
            'level_1_role_slug' => 'hostmaster',
            'level_2_role_slug' => 'billing',
            'updated_by' => null,
        ]);
    }
}
