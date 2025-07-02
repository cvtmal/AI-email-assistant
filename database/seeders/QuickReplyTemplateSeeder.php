<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\QuickReplyTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

final class QuickReplyTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if ($user === null) {
            return;
        }

        $templates = [
            [
                'name' => 'Account Activation (German)',
                'template_text' => 'Dein Account auf www.myitjob.ch ist jetzt aktiv! Ich freue mich, dir mitteilen zu können, dass schon einige spannende Jobvorschläge auf dich warten. Es wäre grossartig, wenn du die Gelegenheit findest, dich bald einzuloggen und sie dir anzuschauen.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Thank You',
                'template_text' => 'Thank you for your email. I appreciate you reaching out and will get back to you soon.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Follow Up',
                'template_text' => 'I wanted to follow up on our previous conversation. Please let me know if you have any questions.',
                'sort_order' => 3,
            ],
        ];

        foreach ($templates as $template) {
            QuickReplyTemplate::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $template['name'],
                ],
                [
                    'template_text' => $template['template_text'],
                    'sort_order' => $template['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
