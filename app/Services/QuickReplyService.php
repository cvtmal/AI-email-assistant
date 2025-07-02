<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QuickReplyTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final readonly class QuickReplyService
{
    /**
     * Get all active quick reply templates (available to all users)
     *
     * @return Collection<int, QuickReplyTemplate>
     */
    public function getUserTemplates(): Collection
    {
        return QuickReplyTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new quick reply template
     */
    public function createTemplate(string $name, string $templateText, int $sortOrder = 0): QuickReplyTemplate
    {
        return QuickReplyTemplate::create([
            'user_id' => Auth::id(),
            'name' => $name,
            'template_text' => $templateText,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    /**
     * Update an existing quick reply template
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        $template = QuickReplyTemplate::query()
            ->where('id', $templateId)
            ->where('user_id', Auth::id())
            ->first();

        if ($template === null) {
            return false;
        }

        return $template->update($data);
    }

    /**
     * Delete a quick reply template
     */
    public function deleteTemplate(int $templateId): bool
    {
        return QuickReplyTemplate::query()
            ->where('id', $templateId)
            ->where('user_id', Auth::id())
            ->delete() > 0;
    }

    /**
     * Get a specific template by ID (globally accessible)
     */
    public function getTemplate(int $templateId): ?QuickReplyTemplate
    {
        return QuickReplyTemplate::query()
            ->where('id', $templateId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create default templates for a user
     */
    public function createDefaultTemplates(): void
    {
        $defaultTemplates = [
            [
                'name' => 'Account Activation (German)',
                'template_text' => 'Dein Account auf www.myitjob.ch ist jetzt aktiv! Ich freue mich, dir mitteilen zu können, dass schon einige spannende Jobvorschläge auf dich warten. Es wäre grossartig, wenn du die Gelegenheit findest, dich bald einzuloggen und sie dir anzuschauen.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Thank You',
                'template_text' => 'Thank you for your email. I appreciate you reaching out.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Follow Up',
                'template_text' => 'I wanted to follow up on our previous conversation.',
                'sort_order' => 3,
            ],
        ];

        foreach ($defaultTemplates as $template) {
            $this->createTemplate(
                $template['name'],
                $template['template_text'],
                $template['sort_order']
            );
        }
    }
}