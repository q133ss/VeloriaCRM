<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('useful_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::table('learning_articles', function (Blueprint $table) {
            $table->foreignId('useful_category_id')
                ->nullable()
                ->constrained('useful_categories')
                ->nullOnDelete();
        });

        $now = Carbon::now();
        $categories = [
            [
                'slug' => 'general',
                'name' => ['ru' => 'Полезное', 'en' => 'Useful'],
                'description' => ['ru' => 'Общие полезные материалы для работы.', 'en' => 'General useful posts for day-to-day work.'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'marketing',
                'name' => ['ru' => 'Маркетинг', 'en' => 'Marketing'],
                'description' => ['ru' => 'Идеи для продвижения и контента.', 'en' => 'Ideas for promotion and content.'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'retention',
                'name' => ['ru' => 'Возврат клиентов', 'en' => 'Client retention'],
                'description' => ['ru' => 'Материалы по возврату и реактивации клиентов.', 'en' => 'Guides for bringing clients back.'],
                'sort_order' => 30,
            ],
            [
                'slug' => 'loyalty',
                'name' => ['ru' => 'Лояльность', 'en' => 'Loyalty'],
                'description' => ['ru' => 'Как удерживать клиентов и повышать доверие.', 'en' => 'How to grow loyalty and trust.'],
                'sort_order' => 40,
            ],
            [
                'slug' => 'legal',
                'name' => ['ru' => 'Налоги и право', 'en' => 'Taxes & legal'],
                'description' => ['ru' => 'Правила, налоги и важные юридические моменты.', 'en' => 'Rules, taxes and important legal details.'],
                'sort_order' => 50,
            ],
            [
                'slug' => 'business',
                'name' => ['ru' => 'Бизнес', 'en' => 'Business'],
                'description' => ['ru' => 'Финансы, рост и организация работы.', 'en' => 'Finance, growth and business operations.'],
                'sort_order' => 60,
            ],
            [
                'slug' => 'clients',
                'name' => ['ru' => 'Клиенты', 'en' => 'Clients'],
                'description' => ['ru' => 'Коммуникация и забота о клиентах.', 'en' => 'Communication and client care.'],
                'sort_order' => 70,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('useful_categories')->insert([
                'slug' => $category['slug'],
                'name' => json_encode($category['name'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'description' => json_encode($category['description'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $categoryIds = DB::table('useful_categories')
            ->pluck('id', 'slug');

        $legacyTopicMap = [
            'marketing' => 'marketing',
            'content' => 'marketing',
            'promotion' => 'marketing',
            'retention' => 'retention',
            'loyalty' => 'loyalty',
            'legal' => 'legal',
            'tax' => 'legal',
            'taxes' => 'legal',
            'compliance' => 'legal',
            'business' => 'business',
            'finance' => 'business',
            'clients' => 'clients',
            'client-care' => 'clients',
        ];

        foreach ($legacyTopicMap as $legacyTopic => $slug) {
            $categoryId = $categoryIds[$slug] ?? null;
            if (! $categoryId) {
                continue;
            }

            DB::table('learning_articles')
                ->where('topic', $legacyTopic)
                ->update(['useful_category_id' => $categoryId]);
        }

        $generalCategoryId = $categoryIds['general'] ?? null;
        if ($generalCategoryId) {
            DB::table('learning_articles')
                ->whereNull('useful_category_id')
                ->update([
                    'useful_category_id' => $generalCategoryId,
                    'topic' => DB::raw("COALESCE(topic, 'general')"),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('useful_category_id');
        });

        Schema::dropIfExists('useful_categories');
    }
};
