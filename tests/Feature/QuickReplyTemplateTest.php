<?php

declare(strict_types=1);

use App\Models\QuickReplyTemplate;
use App\Models\User;
use App\Services\QuickReplyService;

test('can create quick reply template', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = new QuickReplyService();
    $template = $service->createTemplate('Test Template', 'This is a test template text.');

    expect($template)->toBeInstanceOf(QuickReplyTemplate::class);
    expect($template->name)->toBe('Test Template');
    expect($template->template_text)->toBe('This is a test template text.');
    expect($template->user_id)->toBe($user->id);
});

test('can retrieve all templates globally', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = new QuickReplyService();
    $service->createTemplate('Template 1', 'Text 1', 1);
    $service->createTemplate('Template 2', 'Text 2', 2);

    // Templates are now globally accessible
    $templates = $service->getUserTemplates();

    expect($templates->count())->toBeGreaterThanOrEqual(2);
    expect($templates->pluck('name'))->toContain('Template 1');
});

test('can create default templates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = new QuickReplyService();
    $service->createDefaultTemplates();

    $templates = $service->getUserTemplates();

    expect($templates)->toHaveCount(3);
    expect($templates->first()->name)->toBe('Account Activation (German)');
    expect($templates->first()->template_text)->toContain('www.myitjob.ch');
});

test('can update template', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = new QuickReplyService();
    $template = $service->createTemplate('Original', 'Original text');

    $success = $service->updateTemplate($template->id, [
        'name' => 'Updated',
        'template_text' => 'Updated text',
    ]);

    expect($success)->toBe(true);

    $updated = $service->getTemplate($template->id);
    expect($updated->name)->toBe('Updated');
    expect($updated->template_text)->toBe('Updated text');
});

test('can delete template', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = new QuickReplyService();
    $template = $service->createTemplate('To Delete', 'Delete me');

    $success = $service->deleteTemplate($template->id);

    expect($success)->toBe(true);
    expect($service->getTemplate($template->id))->toBe(null);
});
