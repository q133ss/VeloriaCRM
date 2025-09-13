<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('plan', 20);
            $table->string('status', 20);
            $table->timestampsTz();
            $table->index('created_at');
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('birthday')->nullable();
            $table->jsonb('tags')->nullable();
            $table->jsonb('allergies')->nullable();
            $table->jsonb('preferences')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('last_visit_at')->nullable();
            $table->string('loyalty_level')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('created_at');
            $table->unique(['tenant_id', 'phone']);
            $table->unique(['tenant_id', 'email']);
        });
        DB::statement('CREATE INDEX clients_tags_gin ON clients USING gin (tags)');
        DB::statement('CREATE INDEX clients_allergies_gin ON clients USING gin (allergies)');
        DB::statement('CREATE INDEX clients_preferences_gin ON clients USING gin (preferences)');

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('name');
            $table->integer('base_price')->default(0);
            $table->integer('cost')->default(0);
            $table->integer('duration_min');
            $table->jsonb('upsell_suggestions')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX services_upsell_suggestions_gin ON services USING gin (upsell_suggestions)');

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status', 20);
            $table->integer('deposit_amount')->nullable();
            $table->float('risk_no_show')->nullable();
            $table->float('fit_score')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('starts_at');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX appointments_meta_gin ON appointments USING gin (meta)');

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->jsonb('preferred_slots')->nullable();
            $table->integer('priority')->default(0);
            $table->string('status', 20);
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX waitlist_entries_preferred_slots_gin ON waitlist_entries USING gin (preferred_slots)');

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('number');
            $table->integer('total');
            $table->integer('discount')->default(0);
            $table->integer('payable');
            $table->string('status', 20);
            $table->string('currency', 3)->default('RUB');
            $table->timestampsTz();
            $table->unique(['tenant_id', 'number']);
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->integer('unit_price');
            $table->integer('total');
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX invoice_items_meta_gin ON invoice_items USING gin (meta)');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id');
            $table->integer('amount');
            $table->string('status', 20);
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX payments_metadata_gin ON payments USING gin (metadata)');

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel', 20);
            $table->jsonb('content');
            $table->string('locale', 5)->default('en');
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX message_templates_content_gin ON message_templates USING gin (content)');

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('channel', 20);
            $table->jsonb('payload')->nullable();
            $table->string('status', 20);
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX messages_payload_gin ON messages USING gin (payload)');

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type', 50);
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('created_at');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->morphs('model');
            $table->string('collection')->nullable();
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->unsignedBigInteger('size')->default(0);
            $table->jsonb('custom_properties')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX media_custom_properties_gin ON media USING gin (custom_properties)');

        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->jsonb('metrics')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('date');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX analytics_daily_metrics_gin ON analytics_daily USING gin (metrics)');

        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->jsonb('spec');
            $table->string('status', 20)->default('draft');
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX flows_spec_gin ON flows USING gin (spec)');

        Schema::create('flow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->string('status', 20);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('flow_id');
            $table->index('status');
            $table->index('started_at');
            $table->index('created_at');
        });

        Schema::create('flow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_run_id')->constrained('flow_runs')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('channel', 20)->nullable();
            $table->string('status', 20);
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('flow_run_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX flow_events_meta_gin ON flow_events USING gin (meta)');

        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->jsonb('payload');
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX ai_recommendations_payload_gin ON ai_recommendations USING gin (payload)');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->jsonb('value')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'key']);
            $table->index('tenant_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX settings_value_gin ON settings USING gin (value)');

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret');
            $table->boolean('active')->default(true);
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('active');
            $table->index('created_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->jsonb('payload')->nullable();
            $table->timestampsTz();
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('created_at');
        });
        DB::statement('CREATE INDEX audit_logs_payload_gin ON audit_logs USING gin (payload)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('flow_events');
        Schema::dropIfExists('flow_runs');
        Schema::dropIfExists('flows');
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('media');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('tenants');
    }
};
