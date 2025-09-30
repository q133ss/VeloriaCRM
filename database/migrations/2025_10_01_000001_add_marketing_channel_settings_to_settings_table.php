<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('smsaero_api_key');
            $table->unsignedSmallInteger('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->string('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->nullable()->after('smtp_password');
            $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
            $table->string('whatsapp_api_key')->nullable()->after('smtp_from_name');
            $table->string('whatsapp_sender')->nullable()->after('whatsapp_api_key');
            $table->string('telegram_bot_token')->nullable()->after('whatsapp_sender');
            $table->string('telegram_sender')->nullable()->after('telegram_bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'smtp_from_address',
                'smtp_from_name',
                'whatsapp_api_key',
                'whatsapp_sender',
                'telegram_bot_token',
                'telegram_sender',
            ]);
        });
    }
};
