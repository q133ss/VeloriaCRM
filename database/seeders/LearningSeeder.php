<?php

namespace Database\Seeders;

use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\LearningLesson;
use App\Models\LearningRecommendation;
use App\Models\LearningTask;
use App\Models\LearningTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class LearningSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            [
                'slug' => 'business',
                'icon' => 'ri-line-chart-line',
                'position' => 1,
                'title' => [
                    'ru' => 'Бизнес',
                    'en' => 'Business',
                ],
                'description' => [
                    'ru' => 'Финансовая грамотность и устойчивый рост студии.',
                    'en' => 'Financial literacy and sustainable salon growth.',
                ],
            ],
            [
                'slug' => 'sales-service',
                'icon' => 'ri-hand-coin-line',
                'position' => 2,
                'title' => [
                    'ru' => 'Продажи и сервис',
                    'en' => 'Sales & service',
                ],
                'description' => [
                    'ru' => 'Скрипты и приёмы для роста среднего чека.',
                    'en' => 'Scripts and techniques to boost the average ticket.',
                ],
            ],
            [
                'slug' => 'time-management',
                'icon' => 'ri-time-line',
                'position' => 3,
                'title' => [
                    'ru' => 'Тайм-менеджмент',
                    'en' => 'Time management',
                ],
                'description' => [
                    'ru' => 'Планирование расписания и борьба с выгоранием.',
                    'en' => 'Schedule planning and burnout prevention.',
                ],
            ],
            [
                'slug' => 'profession-trends',
                'icon' => 'ri-sparkling-2-line',
                'position' => 4,
                'title' => [
                    'ru' => 'Профессия и тренды',
                    'en' => 'Profession & trends',
                ],
                'description' => [
                    'ru' => 'Новые техники, материалы и конкурентные преимущества.',
                    'en' => 'New techniques, materials and ways to stand out.',
                ],
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $categoryData) {
            $categories[$categoryData['slug']] = LearningCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                Arr::except($categoryData, ['slug'])
            );
        }

        $lessonsData = [
            [
                'slug' => 'service-cost-basics',
                'category' => 'business',
                'position' => 1,
                'duration' => 4,
                'title' => [
                    'ru' => 'Как считать себестоимость услуги',
                    'en' => 'Calculating your service cost',
                ],
                'summary' => [
                    'ru' => 'Разберите формулу и поймите, сколько вы реально зарабатываете с каждой услуги.',
                    'en' => 'Understand the formula and see how much you really earn per service.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Выпишите все расходы: материалы, аренда, налоги, амортизация.',
                            'Разделите их на количество процедур и добавьте желаемую маржу.',
                            'Сравните себестоимость с текущей ценой и скорректируйте прайс-лист.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'List every expense: materials, rent, taxes and depreciation.',
                            'Divide by the number of procedures and add your target margin.',
                            'Compare the cost with your current price list and adjust if needed.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'pricing-formula',
                'category' => 'business',
                'position' => 2,
                'duration' => 5,
                'title' => [
                    'ru' => 'Формула идеального ценообразования',
                    'en' => 'The ideal pricing formula',
                ],
                'summary' => [
                    'ru' => 'Определите минимальную и премиальную цену в зависимости от спроса.',
                    'en' => 'Set your baseline and premium price based on demand.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Используйте себестоимость + маржа + ценность для клиента.',
                            'Добавьте коэффициенты сезонности и занятости.',
                            'Проверьте, как цена влияет на загрузку ближайших недель.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Use cost + margin + perceived client value.',
                            'Apply seasonality and occupancy coefficients.',
                            'Check how the price influences bookings for the next weeks.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'seasonality-plan',
                'category' => 'business',
                'position' => 3,
                'duration' => 3,
                'title' => [
                    'ru' => 'Сезонность: как заранее подготовиться',
                    'en' => 'Seasonality: prepare in advance',
                ],
                'summary' => [
                    'ru' => 'Предскажите загрузку и подготовьте акции к высоким и низким периодам.',
                    'en' => 'Forecast your workload and prepare offers for peak and slow periods.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Отметьте ключевые месяцы: отпускной сезон, праздники, зарплатные даты.',
                            'Составьте список быстрых акций и пакетов услуг.',
                            'Заранее закажите материалы и подготовьте контент.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Highlight peak months: holidays, salary weeks and vacation season.',
                            'Prepare flash offers and bundled services.',
                            'Order supplies in advance and prepare social content.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'finance-tools',
                'category' => 'business',
                'position' => 4,
                'duration' => 4,
                'title' => [
                    'ru' => 'Простые инструменты финансового учета',
                    'en' => 'Simple finance tracking tools',
                ],
                'summary' => [
                    'ru' => 'Ведите контроль доходов и расходов без сложных таблиц.',
                    'en' => 'Track income and expenses without complex spreadsheets.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Заведите еженедельный отчёт с тремя колонками: доходы, расходы, выводы.',
                            'Используйте приложение или шаблон Veloria, чтобы фиксировать показатели каждый день.',
                            'Раз в месяц подводите итоги и отмечайте аномалии.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Create a weekly report with three columns: income, expenses, insights.',
                            'Use an app or Veloria template to log metrics daily.',
                            'Summarise monthly and highlight anomalies.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'upsell-scripts',
                'category' => 'sales-service',
                'position' => 1,
                'duration' => 5,
                'title' => [
                    'ru' => 'Скрипты для апсейла',
                    'en' => 'Upsell scripts',
                ],
                'summary' => [
                    'ru' => 'Подберите точные фразы под услуги и говорите уверенно.',
                    'en' => 'Use confident phrases tailored to each service.',
                ],
                'content' => [
                    'ru' => [
                        'phrases' => [
                            '"Если добавить парафинотерапию, кожа будет мягкой ещё неделю. Давайте включим?"',
                            '"После окрашивания советую уход, который сохраняет блеск до следующего визита."',
                        ],
                    ],
                    'en' => [
                        'phrases' => [
                            '"If we add a paraffin treatment your skin will stay soft for another week."',
                            '"After colouring I recommend a care set that keeps the shine until the next visit."',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'handling-objections',
                'category' => 'sales-service',
                'position' => 2,
                'duration' => 4,
                'title' => [
                    'ru' => 'Алгоритм работы с возражениями',
                    'en' => 'Objection handling playbook',
                ],
                'summary' => [
                    'ru' => 'Отработайте фразы на случаи «Дорого» и «Я подумаю».',
                    'en' => 'Handle "It’s expensive" or "I’ll think about it" like a pro.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Согласитесь с клиентом и уточните причину.',
                            'Предложите решение: рассрочка, услуга попроще или бонус.',
                            'Закрепите результат конкретным предложением.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Empathise and clarify the real reason.',
                            'Offer a solution: instalments, lighter service or bonus.',
                            'Close with a specific proposal.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'turn-complaint-into-loyalty',
                'category' => 'sales-service',
                'position' => 3,
                'duration' => 3,
                'title' => [
                    'ru' => 'Как превратить жалобу в лояльность',
                    'en' => 'Turn complaints into loyalty',
                ],
                'summary' => [
                    'ru' => 'Разберите шаги: от извинений до бонуса и повторного визита.',
                    'en' => 'Step-by-step: apology, bonus and follow-up visit.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Выслушайте и повторите проблему своими словами.',
                            'Назначьте бесплатную доработку или подарочный сервис.',
                            'Через неделю уточните, как клиент оценивает решение.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Listen and restate the issue.',
                            'Offer a free fix or complimentary add-on.',
                            'Follow up within a week for feedback.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'perfect-consultation',
                'category' => 'sales-service',
                'position' => 4,
                'duration' => 5,
                'title' => [
                    'ru' => 'Идеальная консультация',
                    'en' => 'Perfect consultation flow',
                ],
                'summary' => [
                    'ru' => 'От приветствия до записи на следующий визит — что сказать на каждом шаге.',
                    'en' => 'From greeting to rebooking — what to say at each step.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Подтвердите запрос клиента и проговорите желаемый результат.',
                            'Согласуйте этапы процедуры и ключевые точки контроля.',
                            'Завершите рекомендацией домашнего ухода и предложением повторной записи.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Confirm the client’s request and desired outcome.',
                            'Align on the procedure steps and check points.',
                            'Finish with home-care advice and a rebooking offer.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'perfect-schedule',
                'category' => 'time-management',
                'position' => 1,
                'duration' => 4,
                'title' => [
                    'ru' => 'Как собрать идеальное расписание',
                    'en' => 'Building the perfect schedule',
                ],
                'summary' => [
                    'ru' => 'Распределите услуги по типу и длительности, чтобы не терять время.',
                    'en' => 'Distribute services by type and duration to avoid downtime.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Чередуйте короткие и длинные процедуры.',
                            'Закладывайте буфер 10 минут на подготовку рабочего места.',
                            'Блокируйте время для звонков и апдейта контента.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Alternate short and long services.',
                            'Add a 10 minute buffer for prep and cleaning.',
                            'Block time for follow-ups and content updates.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'burnout-protection',
                'category' => 'time-management',
                'position' => 2,
                'duration' => 3,
                'title' => [
                    'ru' => 'Методы борьбы с выгоранием',
                    'en' => 'Burnout prevention toolkit',
                ],
                'summary' => [
                    'ru' => 'Создайте ритуалы восстановления энергии и разгрузки.',
                    'en' => 'Create rituals to restore energy and offload stress.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Планируйте два коротких перерыва с растяжкой в середине дня.',
                            'В конце недели фиксируйте победы и благодарности клиентов.',
                            'Настройте автоматические напоминания о выходных.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Schedule two short stretch breaks mid-day.',
                            'Record weekly wins and client feedback.',
                            'Set automatic reminders for days off.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'workspace-prep-tools',
                'category' => 'time-management',
                'position' => 3,
                'duration' => 3,
                'title' => [
                    'ru' => 'Инструменты быстрой подготовки рабочего места',
                    'en' => 'Quick workstation setup',
                ],
                'summary' => [
                    'ru' => 'Оптимизируйте набор материалов и порядок действий перед клиентом.',
                    'en' => 'Optimise your supplies and steps before the client arrives.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Соберите «старт-бокс» с топовыми материалами и одноразовыми расходниками.',
                            'Используйте таймер на 5 минут для проверки стерилизации и инвентаря.',
                            'Подготовьте приветствие и ключевые вопросы заранее.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Create a “start box” with top materials and disposables.',
                            'Use a 5 minute timer to check sterilisation and stock.',
                            'Prepare your greeting and key questions in advance.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ideal-workload-formula',
                'category' => 'time-management',
                'position' => 4,
                'duration' => 4,
                'title' => [
                    'ru' => 'Формула идеальной загрузки',
                    'en' => 'Ideal workload formula',
                ],
                'summary' => [
                    'ru' => 'Расчитайте баланс между доходом и здоровьем.',
                    'en' => 'Find the balance between income and wellbeing.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Определите максимальное количество клиентов в день без потери качества.',
                            'Установите недельный лимит по часам и придерживайтесь его.',
                            'Добавьте буфер на внеплановые запросы.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Define the maximum clients per day without quality loss.',
                            'Set a weekly hour limit and stick to it.',
                            'Add a buffer for urgent appointments.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'material-reviews',
                'category' => 'profession-trends',
                'position' => 1,
                'duration' => 5,
                'title' => [
                    'ru' => 'Обзоры новых материалов',
                    'en' => 'New material reviews',
                ],
                'summary' => [
                    'ru' => 'Два лучших материала месяца и их особенности.',
                    'en' => 'Two standout products this month and their benefits.',
                ],
                'content' => [
                    'ru' => [
                        'items' => [
                            'Биогель нового поколения — держится до 5 недель, подходит для клиентов с аллегриями.',
                            'Пигмент с холодным подтоном для стойких блондов.',
                        ],
                    ],
                    'en' => [
                        'items' => [
                            'Next-gen biogel — lasts up to 5 weeks and fits sensitive clients.',
                            'Cool-tone pigment designed for long-lasting blondes.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'social-trend-breakdown',
                'category' => 'profession-trends',
                'position' => 2,
                'duration' => 4,
                'title' => [
                    'ru' => 'Разбор трендов из соцсетей',
                    'en' => 'Social trend breakdown',
                ],
                'summary' => [
                    'ru' => 'Что сейчас в запросах клиентов и как повторить в студии.',
                    'en' => 'What clients ask from social media and how to recreate it.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Выделите 3 запроса из ленты: форма, оттенок, дизайн.',
                            'Подготовьте примеры своих работ и материалы для них.',
                            'Снимите сторис с разбором тренда и призывом к записи.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Pick three requests: shape, colour, design.',
                            'Prepare examples from your portfolio and matching materials.',
                            'Record a story explaining the trend and invite to book.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'safety-techniques',
                'category' => 'profession-trends',
                'position' => 3,
                'duration' => 4,
                'title' => [
                    'ru' => 'Техники безопасности',
                    'en' => 'Safety techniques refresh',
                ],
                'summary' => [
                    'ru' => 'Обновите протоколы: аллергенность, обработка инструментов, стерилизация.',
                    'en' => 'Refresh protocols: allergies, tool processing, sterilisation.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Проверьте сроки годности средств и отметьте их в календаре.',
                            'Обновите инструкции по обработке инструментов для ассистента.',
                            'Проведите экспресс-тест на аллергию для новых клиентов.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Check product shelf life and log it in the calendar.',
                            'Update tool processing instructions for assistants.',
                            'Run a quick allergy test for new clients.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'competitive-advantages-checklist',
                'category' => 'profession-trends',
                'position' => 4,
                'duration' => 3,
                'title' => [
                    'ru' => 'Чек-лист «Мои конкурентные преимущества»',
                    'en' => 'My competitive advantages checklist',
                ],
                'summary' => [
                    'ru' => 'Соберите аргументы, которые выделяют вас среди конкурентов.',
                    'en' => 'Collect the arguments that make you stand out.',
                ],
                'content' => [
                    'ru' => [
                        'items' => [
                            'Опыт и сертификации.',
                            'Фирменные техники и материалы.',
                            'Отзывы клиентов и результаты до/после.',
                        ],
                    ],
                    'en' => [
                        'items' => [
                            'Experience and certifications.',
                            'Signature techniques and materials.',
                            'Client testimonials and before/after results.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($lessonsData as $lessonData) {
            $category = $categories[$lessonData['category']] ?? null;

            if (!$category) {
                continue;
            }

            LearningLesson::updateOrCreate(
                ['slug' => $lessonData['slug']],
                [
                    'learning_category_id' => $category->id,
                    'title' => $lessonData['title'],
                    'summary' => $lessonData['summary'],
                    'duration_minutes' => $lessonData['duration'],
                    'format' => 'micro',
                    'content' => $lessonData['content'],
                    'position' => $lessonData['position'],
                ]
            );
        }

        $user = User::where('email', 'natalia.zueva@veloria.test')->first();

        if ($user) {
            $recommendationsData = [
                [
                    'priority' => 300,
                    'type' => 'upsell',
                    'title' => [
                        'ru' => 'Вы редко предлагаете апсейлы к маникюру',
                        'en' => 'Upsells are offered too rarely',
                    ],
                    'description' => [
                        'ru' => 'Только 12% визитов заканчиваются дополнительной покупкой. Клиенты готовы брать уход, если показать ценность.',
                        'en' => 'Only 12% of appointments end with an add-on. Clients are ready to buy care when the value is clear.',
                    ],
                    'impact_text' => [
                        'ru' => '+500 ₽ к среднему чеку',
                        'en' => '+₽500 to the average ticket',
                    ],
                    'action' => [
                        'ru' => 'Подготовьте две фразы для предложения парафинотерапии и защитного ухода.',
                        'en' => 'Prepare two phrases to offer paraffin care and a protective kit.',
                    ],
                    'confidence' => 78,
                    'meta' => [
                        'icon' => 'ri-arrow-up-circle-line',
                        'metric' => 'average_check',
                    ],
                ],
                [
                    'priority' => 260,
                    'type' => 'retention',
                    'title' => [
                        'ru' => 'Три клиента в группе риска',
                        'en' => 'Three clients are at risk of churn',
                    ],
                    'description' => [
                        'ru' => 'Они не бронировали визит более 6 недель. Напомните о себе с персональным предложением.',
                        'en' => 'They have been inactive for over six weeks. Reach out with a personalised offer.',
                    ],
                    'impact_text' => [
                        'ru' => 'Верните 3 клиента в расписание',
                        'en' => 'Win back three clients this week',
                    ],
                    'action' => [
                        'ru' => 'Отправьте голосовое с индивидуальным тоном и ссылкой на быстрый выбор времени.',
                        'en' => 'Send a voice note with a friendly tone and a quick booking link.',
                    ],
                    'confidence' => 72,
                    'meta' => [
                        'icon' => 'ri-customer-service-2-line',
                        'metric' => 'retention',
                    ],
                ],
                [
                    'priority' => 220,
                    'type' => 'experience',
                    'title' => [
                        'ru' => 'NPS просел после последних визитов',
                        'en' => 'NPS decreased after recent visits',
                    ],
                    'description' => [
                        'ru' => 'Средняя оценка 7,6 — клиенты отмечают недостаток общения до процедуры.',
                        'en' => 'Average score is 7.6 — clients mention limited consultation before the service.',
                    ],
                    'impact_text' => [
                        'ru' => '+10% к NPS за счёт усиленной консультации',
                        'en' => '+10% NPS by strengthening the consultation',
                    ],
                    'action' => [
                        'ru' => 'Используйте чек-лист вопросов перед началом и фиксируйте ожидания клиента.',
                        'en' => 'Use the pre-service question checklist and document expectations.',
                    ],
                    'confidence' => 69,
                    'meta' => [
                        'icon' => 'ri-chat-smile-2-line',
                        'metric' => 'nps',
                    ],
                ],
            ];

            foreach ($recommendationsData as $data) {
                LearningRecommendation::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'priority' => $data['priority'],
                    ],
                    Arr::except($data, ['priority']) + ['user_id' => $user->id, 'priority' => $data['priority']]
                );
            }

            $weekEnd = Carbon::now($user->timezone ?? config('app.timezone'))->endOfWeek(Carbon::SUNDAY);

            $tasksData = [
                [
                    'position' => 1,
                    'title' => [
                        'ru' => 'Предложить уходовое средство 5 клиентам',
                        'en' => 'Offer a care product to five clients',
                    ],
                    'description' => [
                        'ru' => 'Используйте подсказки из урока по апсейлу и зафиксируйте результат в CRM.',
                        'en' => 'Use the upsell script lesson and log results in the CRM.',
                    ],
                    'status' => LearningTask::STATUS_IN_PROGRESS,
                    'progress_current' => 2,
                    'progress_target' => 5,
                    'progress_unit' => [
                        'ru' => 'клиентов',
                        'en' => 'clients',
                    ],
                    'due_on' => $weekEnd->toDateString(),
                    'meta' => [
                        'related_lesson' => 'upsell-scripts',
                    ],
                ],
                [
                    'position' => 2,
                    'title' => [
                        'ru' => 'Связаться с клиентами из группы риска',
                        'en' => 'Reach out to at-risk clients',
                    ],
                    'description' => [
                        'ru' => 'Подготовьте голосовой сценарий и предложите быстрый слот со скидкой 10%.',
                        'en' => 'Record a voice script and offer a quick slot with a 10% incentive.',
                    ],
                    'status' => LearningTask::STATUS_PENDING,
                    'progress_current' => 0,
                    'progress_target' => 3,
                    'progress_unit' => [
                        'ru' => 'клиента',
                        'en' => 'clients',
                    ],
                    'due_on' => $weekEnd->copy()->subDay()->toDateString(),
                    'meta' => [
                        'related_template_group' => 'voice',
                    ],
                ],
                [
                    'position' => 3,
                    'title' => [
                        'ru' => 'Обновить чек-лист консультации',
                        'en' => 'Refresh consultation checklist',
                    ],
                    'description' => [
                        'ru' => 'Добавьте вопросы про чувствительность и желаемый результат в начале приёма.',
                        'en' => 'Add questions about sensitivity and desired outcome at the start.',
                    ],
                    'status' => LearningTask::STATUS_COMPLETED,
                    'progress_current' => 1,
                    'progress_target' => 1,
                    'progress_unit' => [
                        'ru' => 'чек-лист',
                        'en' => 'checklist',
                    ],
                    'completed_at' => Carbon::now()->subDay(),
                    'due_on' => $weekEnd->copy()->subDays(2)->toDateString(),
                    'meta' => [
                        'related_lesson' => 'perfect-consultation',
                    ],
                ],
            ];

            foreach ($tasksData as $data) {
                $attributes = [
                    'user_id' => $user->id,
                    'position' => $data['position'],
                ];

                $values = Arr::except($data, ['position']);

                if (isset($values['completed_at']) && $values['completed_at'] instanceof Carbon) {
                    $values['completed_at'] = $values['completed_at'];
                }

                LearningTask::updateOrCreate($attributes, $values + ['user_id' => $user->id, 'position' => $data['position']]);
            }
        }

        $articlesData = [
            [
                'slug' => 'first-post-guide',
                'title' => [
                    'ru' => 'Как сделать первый пост о себе',
                    'en' => 'How to write your first post about yourself',
                ],
                'summary' => [
                    'ru' => 'Пошаговый сценарий публикации, чтобы познакомить клиентов с мастером.',
                    'en' => 'Step-by-step script to introduce yourself to clients.',
                ],
                'reading_time_minutes' => 4,
                'topic' => 'marketing',
                'content' => [
                    'ru' => [
                        'structure' => [
                            'Заголовок с ключевой специализацией.',
                            'История о том, почему вы выбрали профессию.',
                            'Факты: опыт, обучение, любимые услуги.',
                            'Призыв записаться и удобный способ связи.',
                        ],
                    ],
                    'en' => [
                        'structure' => [
                            'Headline with your key specialisation.',
                            'Your story: why you chose the profession.',
                            'Facts: experience, education, favourite services.',
                            'Call to action with an easy booking link.',
                        ],
                    ],
                ],
                'action' => [
                    'ru' => [
                        'label' => 'Использовать шаблон текста',
                        'template_group' => 'text',
                    ],
                    'en' => [
                        'label' => 'Use a text template',
                        'template_group' => 'text',
                    ],
                ],
            ],
            [
                'slug' => 'word-of-mouth-launch',
                'title' => [
                    'ru' => 'Инструкция: запуск сарафанного радио',
                    'en' => 'Guide: start your word of mouth',
                ],
                'summary' => [
                    'ru' => 'Настройте реферальную систему и активируйте рекомендаций клиентов.',
                    'en' => 'Set up referrals and activate client recommendations.',
                ],
                'reading_time_minutes' => 5,
                'topic' => 'retention',
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Определите бонус для клиента и его друга.',
                            'Настройте шаблон сообщения и ссылку на быструю запись.',
                            'Напомните об акции после визита и через 7 дней.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Define a reward for both the client and the friend.',
                            'Prepare a message template with a quick booking link.',
                            'Remind about the offer right after the visit and seven days later.',
                        ],
                    ],
                ],
                'action' => [
                    'ru' => [
                        'label' => 'Скопировать голосовой скрипт',
                        'template_group' => 'voice',
                    ],
                    'en' => [
                        'label' => 'Copy the voice script',
                        'template_group' => 'voice',
                    ],
                ],
            ],
            [
                'slug' => 'client-loyalty-ideas',
                'title' => [
                    'ru' => '10 способов повысить лояльность',
                    'en' => '10 ways to increase loyalty',
                ],
                'summary' => [
                    'ru' => 'Готовый список идей, чтобы клиенты возвращались чаще.',
                    'en' => 'A list of ideas to keep clients coming back.',
                ],
                'reading_time_minutes' => 6,
                'topic' => 'loyalty',
                'content' => [
                    'ru' => [
                        'ideas' => [
                            'Отправляйте фото «до/после» через 24 часа.',
                            'Создайте мини-клуб постоянных клиентов с бонусами.',
                            'Планируйте «тихие часы» для клиентов, которым нужен комфорт.',
                        ],
                    ],
                    'en' => [
                        'ideas' => [
                            'Send before/after photos within 24 hours.',
                            'Create a mini loyalty club with perks.',
                            'Plan “quiet hours” for clients who need calm.',
                        ],
                    ],
                ],
                'action' => [
                    'ru' => [
                        'label' => 'Открыть чек-лист лояльности',
                        'template_group' => 'checklist',
                    ],
                    'en' => [
                        'label' => 'Open loyalty checklist',
                        'template_group' => 'checklist',
                    ],
                ],
            ],
            [
                'slug' => 'self-employed-legal',
                'title' => [
                    'ru' => 'Юридические нюансы для самозанятых',
                    'en' => 'Legal essentials for self-employed',
                ],
                'summary' => [
                    'ru' => 'Какие документы вести, как принимать оплату и работать официально.',
                    'en' => 'What documents to keep and how to accept payments legally.',
                ],
                'reading_time_minutes' => 7,
                'topic' => 'legal',
                'content' => [
                    'ru' => [
                        'sections' => [
                            'Регистрация и налоги.',
                            'Договор-оферта и политика конфиденциальности.',
                            'Онлайн-касса и безопасные платежи.',
                        ],
                    ],
                    'en' => [
                        'sections' => [
                            'Registration and taxes.',
                            'Offer agreement and privacy policy.',
                            'Online payments and e-receipts.',
                        ],
                    ],
                ],
                'action' => [
                    'ru' => [
                        'label' => 'Скачать шаблон оферты',
                        'template_group' => 'text',
                    ],
                    'en' => [
                        'label' => 'Download offer template',
                        'template_group' => 'text',
                    ],
                ],
            ],
        ];

        foreach ($articlesData as $data) {
            LearningArticle::updateOrCreate(
                ['slug' => $data['slug']],
                Arr::except($data, ['slug'])
            );
        }

        $templatesData = [
            [
                'slug' => 'text-visit-reminder',
                'type' => LearningTemplate::TYPE_TEXT,
                'position' => 1,
                'title' => [
                    'ru' => 'Напоминание о визите',
                    'en' => 'Appointment reminder',
                ],
                'description' => [
                    'ru' => 'Сообщение за день до визита с мягким call-to-action.',
                    'en' => 'Message one day before the visit with a gentle CTA.',
                ],
                'content' => [
                    'ru' => [
                        'body' => "{{Имя}}, завтра ждём вас на маникюр в {{Время}}. Подготовили новый нюдовый оттенок — уверены, понравится! Если нужно перенести, просто ответьте на это сообщение.",
                    ],
                    'en' => [
                        'body' => "{{Name}}, we are waiting for you tomorrow at {{Time}}. A new nude shade is ready for you! Reply here if you need to reschedule.",
                    ],
                ],
            ],
            [
                'slug' => 'text-upsell-care',
                'type' => LearningTemplate::TYPE_TEXT,
                'position' => 2,
                'title' => [
                    'ru' => 'Апсейл после окрашивания',
                    'en' => 'Post-colour upsell',
                ],
                'description' => [
                    'ru' => 'Сообщение с рекомендацией ухода, который продлевает результат.',
                    'en' => 'Message recommending home care to extend the result.',
                ],
                'content' => [
                    'ru' => [
                        'body' => "{{Имя}}, чтобы цвет держался до 6 недель, рекомендую набор с кератином. Держу для вас до {{Дата}} — напишите, если отложить?",
                    ],
                    'en' => [
                        'body' => "{{Name}}, to keep the colour for up to 6 weeks I recommend our keratin care set. I can reserve it for you until {{Date}} — send me a note if you want it!",
                    ],
                ],
            ],
            [
                'slug' => 'voice-referral-script',
                'type' => LearningTemplate::TYPE_VOICE,
                'position' => 1,
                'title' => [
                    'ru' => 'Голосовой для рекомендации подруги',
                    'en' => 'Voice script for referrals',
                ],
                'description' => [
                    'ru' => 'Приветственное сообщение с предложением бонуса за рекомендацию.',
                    'en' => 'Friendly message with a referral bonus.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Поздоровайтесь по имени и поблагодарите за последний визит.',
                            'Расскажите о бонусе: -10% для подруги и spa-уход для клиента.',
                            'Дайте ссылку на быстрый выбор времени и попросите переслать сообщение.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Greet the client by name and thank for the last visit.',
                            'Explain the bonus: 10% for the friend and a spa add-on for the client.',
                            'Share a quick booking link and ask to forward the message.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'voice-returning-client',
                'type' => LearningTemplate::TYPE_VOICE,
                'position' => 2,
                'title' => [
                    'ru' => 'Возврат клиента после паузы',
                    'en' => 'Win-back voice note',
                ],
                'description' => [
                    'ru' => 'Сценарий мягкого напоминания и приглашения на удобное время.',
                    'en' => 'Gentle reminder inviting the client to return.',
                ],
                'content' => [
                    'ru' => [
                        'steps' => [
                            'Отметьте, что соскучились и помните о предпочтениях клиента.',
                            'Предложите быстрый слот или онлайн-консультацию.',
                            'Уточните, удобно ли записать сразу через ссылку.',
                        ],
                    ],
                    'en' => [
                        'steps' => [
                            'Say you miss them and remember their preferences.',
                            'Offer a quick slot or an online consultation.',
                            'Ask if it is convenient to book via the link right now.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'story-template-refresh',
                'type' => LearningTemplate::TYPE_STORY,
                'position' => 1,
                'title' => [
                    'ru' => 'Сторис «до/после»',
                    'en' => 'Before/after story set',
                ],
                'description' => [
                    'ru' => 'Три слайда: результат, отзыв клиента и призыв записаться.',
                    'en' => 'Three slides: result, testimonial and CTA.',
                ],
                'content' => [
                    'ru' => [
                        'slides' => [
                            ['title' => 'Слайд 1', 'text' => 'Фото до и после + подпись с продуктами.'],
                            ['title' => 'Слайд 2', 'text' => 'Цитата клиента или аудио-отзыв.'],
                            ['title' => 'Слайд 3', 'text' => 'Призыв записаться с кнопкой «Записаться» или ссылкой.'],
                        ],
                    ],
                    'en' => [
                        'slides' => [
                            ['title' => 'Slide 1', 'text' => 'Before/after photo with products used.'],
                            ['title' => 'Slide 2', 'text' => 'Client quote or short audio review.'],
                            ['title' => 'Slide 3', 'text' => 'Call to action with “Book now”.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'story-material-launch',
                'type' => LearningTemplate::TYPE_STORY,
                'position' => 2,
                'title' => [
                    'ru' => 'Сторис о новом материале',
                    'en' => 'New material story',
                ],
                'description' => [
                    'ru' => 'Объявление о новой услуге или материале в формате 3 карточек.',
                    'en' => 'Announce a new material or service in three cards.',
                ],
                'content' => [
                    'ru' => [
                        'slides' => [
                            ['title' => 'Слайд 1', 'text' => 'Тизер: «Новый оттенок уже в студии!»'],
                            ['title' => 'Слайд 2', 'text' => 'Коротко о преимуществах и кому подходит.'],
                            ['title' => 'Слайд 3', 'text' => 'Призыв записаться с ограничением по времени.'],
                        ],
                    ],
                    'en' => [
                        'slides' => [
                            ['title' => 'Slide 1', 'text' => 'Teaser: “New shade just arrived!”'],
                            ['title' => 'Slide 2', 'text' => 'Benefits and ideal client profile.'],
                            ['title' => 'Slide 3', 'text' => 'Book now, limited time offer.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'checklist-day-prep',
                'type' => LearningTemplate::TYPE_CHECKLIST,
                'position' => 1,
                'title' => [
                    'ru' => 'Чек-лист подготовки к рабочему дню',
                    'en' => 'Workday preparation checklist',
                ],
                'description' => [
                    'ru' => 'Быстрый контроль материалов, рабочего места и коммуникации.',
                    'en' => 'Quick control of supplies, workstation and communications.',
                ],
                'content' => [
                    'ru' => [
                        'items' => [
                            'Проверить стерилизацию инструментов и расходников.',
                            'Обновить расписание и подтвердить первые визиты.',
                            'Подготовить приветственное сообщение для сторис.',
                        ],
                    ],
                    'en' => [
                        'items' => [
                            'Check sterilised tools and consumables.',
                            'Review the schedule and confirm the first appointments.',
                            'Prepare a welcome story for social media.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'checklist-supply-order',
                'type' => LearningTemplate::TYPE_CHECKLIST,
                'position' => 2,
                'title' => [
                    'ru' => 'Чек-лист заказа материалов',
                    'en' => 'Supply order checklist',
                ],
                'description' => [
                    'ru' => 'Список, чтобы не забыть базовые позиции и новинки.',
                    'en' => 'List to cover essentials and new arrivals.',
                ],
                'content' => [
                    'ru' => [
                        'items' => [
                            'Проверить остатки топовых материалов (гель, базы, пигменты).',
                            'Зафиксировать новые запросы клиентов и добавить тестовые позиции.',
                            'Запланировать дату следующей закупки и бюджет.',
                        ],
                    ],
                    'en' => [
                        'items' => [
                            'Check stock of top materials (gel, bases, pigments).',
                            'Log new client requests and add test items.',
                            'Schedule the next purchase date and budget.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($templatesData as $data) {
            LearningTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                Arr::except($data, ['slug'])
            );
        }
    }
}
