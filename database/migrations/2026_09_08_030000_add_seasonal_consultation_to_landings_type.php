<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `seasonal` и `consultation` — рабочие типы сайта: их отдаёт LandingController
 * (шаблоны в resources/views/landings/templates/) и разрешает валидация
 * (LandingStoreRequest, LandingUpdateRequest). Но исходная миграция завела
 * enum только с тремя значениями, и сохранение любого сайта этих двух типов
 * падало на check-constraint базы.
 *
 * Postgres реализует Laravel `enum()` как CHECK CONSTRAINT, а не нативный
 * тип, поэтому расширяем именно так, а не через ALTER TYPE.
 */
return new class extends Migration
{
    private const OLD_TYPES = ['general', 'promotion', 'service'];
    private const NEW_TYPES = ['general', 'promotion', 'service', 'seasonal', 'consultation'];

    public function up(): void
    {
        $this->applyCheck(self::NEW_TYPES);
    }

    public function down(): void
    {
        $this->applyCheck(self::OLD_TYPES);
    }

    private function applyCheck(array $types): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $list = implode(', ', array_map(fn (string $type) => "'" . $type . "'", $types));

        DB::statement('ALTER TABLE landings DROP CONSTRAINT IF EXISTS landings_type_check');
        DB::statement("ALTER TABLE landings ADD CONSTRAINT landings_type_check CHECK (type IN ({$list}))");
    }
};
