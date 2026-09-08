<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Цены дублируются в миграции 2026_09_08_020000: там они догоняют базы,
     * которые уже подняты, здесь — те, что поднимают сейчас.
     *
     * updateOrInsert вместо insert: сидер запускают повторно, и раньше это
     * плодило вторые «lite» и «pro», после чего activePlanSlug() выбирал план
     * по цене из двух одинаковых строк.
     */
    public function run(): void
    {
        $plans = [
            'lite' => 0,
            'pro' => 990,
            'elite' => 1990,
        ];

        foreach ($plans as $name => $price) {
            DB::table('plans')->updateOrInsert(['name' => $name], ['price' => $price]);
        }
    }
}
