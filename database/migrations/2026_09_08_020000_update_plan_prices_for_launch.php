<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Новая сетка под лендинг: бесплатно отдаём то, чем мастер клиенток привлекает
 * (кабинет, сайт, бот, приложение), деньги берём за то, чем возвращает.
 *
 * Заодно убираем разрыв 999 → 2999. Втрое дороже верхний тариф своим
 * содержимым не оправдывал, и его просто не покупали.
 *
 * Цену правим и здесь, и в сидере: сидер поднимает новые базы, миграция — те,
 * что уже крутятся. Иначе на странице подписки будет одна цена, а на лендинге
 * другая, и первым это заметит клиент.
 */
return new class extends Migration
{
    private const PRICES = [
        'pro' => ['new' => 990, 'old' => 999],
        'elite' => ['new' => 1990, 'old' => 2999],
    ];

    public function up(): void
    {
        foreach (self::PRICES as $name => $price) {
            DB::table('plans')->where('name', $name)->update(['price' => $price['new']]);
        }
    }

    public function down(): void
    {
        foreach (self::PRICES as $name => $price) {
            DB::table('plans')->where('name', $name)->update(['price' => $price['old']]);
        }
    }
};
