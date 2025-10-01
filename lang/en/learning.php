<?php

return [
    'title' => 'Learning',
    'subtitle' => 'Grow faster with personalised coaching, micro-lessons and ready-to-use tools.',
    'tabs' => [
        'plan' => 'Your plan',
        'lessons' => 'Micro-lessons',
        'knowledge' => 'Knowledge base',
    ],
    'alerts' => [
        'load_error' => 'We could not load the learning data. Please refresh and try again.',
    ],
    'notifications' => [
        'task_updated' => 'Task progress updated.',
        'task_error' => 'We could not update the task. Please try again.',
    ],
    'plan' => [
        'header' => [
            'title' => 'Personal growth plan',
            'subtitle' => 'We analyse your performance and refresh the focus areas every week.',
        ],
        'ai_summary' => [
            'title' => 'AI insights',
            'subtitle' => 'The assistant highlights the weak spots so you know where to focus today.',
            'tips_title' => 'Tips for this week',
            'fallback' => [
                'headline' => 'Key growth points for the week',
                'description' => 'Focus on the tasks that drive revenue and client loyalty the most.',
                'tip_default' => 'Stay in touch with at-risk clients to protect repeat visits.',
            ],
        ],
        'insights' => [
            'title' => 'Personal recommendations',
            'empty' => 'No recommendations yet — the system is analysing your data.',
            'impact_label' => 'Impact',
            'action_label' => 'Next action',
            'confidence' => 'AI confidence: :value%',
        ],
        'tasks' => [
            'title' => 'Plan for the week',
            'subtitle' => 'Three to four focused tasks to reinforce what you have learned.',
            'progress_label' => 'Progress',
            'progress_summary' => ':done of :total completed',
            'progress_summary_empty' => 'Tasks will appear after the first analysis.',
            'counter' => ':current of :target :unit',
            'counter_simple' => ':current completed',
            'due_prefix' => 'Due:',
            'empty' => 'No tasks yet. As soon as we have enough data you will see a personalised plan here.',
            'complete' => 'Mark as done',
            'undo' => 'Return to work',
        ],
    ],
    'lessons' => [
        'title' => 'Micro-lessons',
        'subtitle' => 'Bite-sized lessons under five minutes that you can apply immediately.',
        'filters' => [
            'all' => 'All categories',
        ],
        'duration' => ':minutes min',
        'cta' => 'Open summary',
        'empty' => 'No lessons found for the selected category.',
    ],
    'knowledge' => [
        'title' => 'Knowledge base',
        'subtitle' => 'Articles, scripts and checklists ready for use.',
        'search_placeholder' => 'Search articles and templates…',
        'articles_title' => 'Articles',
        'articles_empty' => 'No materials matched your search. Try a different phrase.',
        'templates_title' => 'Template library',
        'template_empty' => 'No templates available.',
        'open_template' => 'View',
        'modal' => [
            'title' => 'Template',
            'copy' => 'Copy',
            'close' => 'Close',
        ],
        'groups' => [
            'text' => 'Text messages',
            'voice' => 'Voice scripts',
            'story' => 'Stories templates',
            'checklist' => 'Checklists',
        ],
    ],
];
