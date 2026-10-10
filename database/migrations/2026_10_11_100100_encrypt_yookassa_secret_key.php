<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An encrypted value is longer than the 255 chars of the old string column.
        Schema::table('settings', function (Blueprint $table) {
            $table->text('yookassa_secret_key')->nullable()->change();
        });

        DB::table('settings')
            ->whereNotNull('yookassa_secret_key')
            ->where('yookassa_secret_key', '!=', '')
            ->orderBy('id')
            ->each(function ($row) {
                if ($this->isEncrypted($row->yookassa_secret_key)) {
                    return;
                }

                DB::table('settings')->where('id', $row->id)->update([
                    'yookassa_secret_key' => Crypt::encryptString($row->yookassa_secret_key),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereNotNull('yookassa_secret_key')
            ->where('yookassa_secret_key', '!=', '')
            ->orderBy('id')
            ->each(function ($row) {
                if (! $this->isEncrypted($row->yookassa_secret_key)) {
                    return;
                }

                DB::table('settings')->where('id', $row->id)->update([
                    'yookassa_secret_key' => Crypt::decryptString($row->yookassa_secret_key),
                ]);
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
