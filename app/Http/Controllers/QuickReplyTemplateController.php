<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QuickReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class QuickReplyTemplateController
{
    public function __construct(
        private QuickReplyService $quickReplyService,
    ) {}

    /**
     * Display the quick reply templates management page.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/QuickReplyTemplates', [
            'templates' => $this->quickReplyService->getUserTemplates(),
        ]);
    }

    /**
     * Store a new quick reply template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_text' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $template = $this->quickReplyService->createTemplate(
            $validated['name'],
            $validated['template_text'],
            $validated['sort_order'] ?? 0
        );

        return response()->json([
            'template' => $template,
            'message' => 'Template created successfully.',
        ]);
    }

    /**
     * Update an existing quick reply template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_text' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $success = $this->quickReplyService->updateTemplate($id, $validated);

        if (! $success) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        return response()->json(['message' => 'Template updated successfully.']);
    }

    /**
     * Delete a quick reply template.
     */
    public function destroy(int $id): JsonResponse
    {
        $success = $this->quickReplyService->deleteTemplate($id);

        if (! $success) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        return response()->json(['message' => 'Template deleted successfully.']);
    }

    /**
     * Create default templates for the current user.
     */
    public function createDefaults(): JsonResponse
    {
        $this->quickReplyService->createDefaultTemplates();

        return response()->json(['message' => 'Default templates created successfully.']);
    }
}
