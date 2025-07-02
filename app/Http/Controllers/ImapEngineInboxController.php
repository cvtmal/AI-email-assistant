<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AIClientInterface;
use App\Contracts\MailerServiceInterface;
use App\Models\EmailReply;
use App\Services\ImapEngineClient;
use App\Services\QuickReplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ImapEngineInboxController
{
    public function __construct(
        private ImapEngineClient $imapClient,
        private AIClientInterface $aiClient,
        private MailerServiceInterface $mailerService,
        private QuickReplyService $quickReplyService,
    ) {}

    /**
     * Display a listing of ImapEngine inbox emails.
     */
    public function index(Request $request): Response
    {
        $account = $request->query('account');
        $emails = $this->imapClient->getInboxEmails($account);

        return Inertia::render('ImapEngineInbox/Index', [
            'emails' => $emails,
            'account' => $account ?? 'default',
        ]);
    }

    /**
     * Display the specified email.
     */
    public function show(Request $request, string $id): Response
    {
        $account = $request->query('account');
        $accountId = $account ?? config('imapengine.default', 'default');
        $email = $this->imapClient->getEmail($id, $accountId);

        if ($email === null) {
            return Inertia::render('ImapEngineInbox/Show', [
                'email' => null,
                'error' => 'Email not found',
                'account' => $account ?? 'default',
            ]);
        }

        // Load existing draft / history for current user
        $reply = EmailReply::query()
            ->where('email_id', $id)
            ->where('user_id', Auth::id())
            ->where('account', $accountId)
            ->first();

        $templates = $this->quickReplyService->getUserTemplates();

        return Inertia::render('ImapEngineInbox/Show', [
            'email' => $email,
            'latestReply' => $reply?->latest_ai_reply,
            'chatHistory' => $reply->chat_history ?? [],
            'signature' => config('signatures.'.$accountId) ?? config('signatures.default'),
            'account' => $accountId,
            'quickReplyTemplates' => $templates,
        ]);
    }

    /**
     * Generate an AI reply for an email.
     */
    public function generateReply(Request $request, string $id): Response
    {
        $validated = $request->validate([
            'instruction' => ['nullable', 'string'],
            'templateId' => ['nullable', 'integer', 'exists:quick_reply_templates,id'],
            'refinementOptions' => ['nullable', 'array'],
            'refinementOptions.tone' => ['nullable', 'string', 'in:professional,friendly,casual,formal,warm,direct'],
            'refinementOptions.length' => ['nullable', 'string', 'in:concise,medium,detailed'],
            'refinementOptions.formality' => ['nullable', 'integer', 'min:1', 'max:5'],
            'refinementOptions.urgency' => ['nullable', 'string', 'in:low,normal,high'],
            'refinementOptions.customInstruction' => ['nullable', 'string'],
        ]);

        $account = $request->query('account');
        $accountId = $account ?? config('imapengine.default', 'default');

        $email = $this->imapClient->getEmail($id, $accountId);
        if ($email === null) {
            return Inertia::render('ImapEngineInbox/Show', [
                'email' => null,
                'account' => $accountId,
                'message' => 'Email not found',
                'success' => false,
            ]);
        }

        $reply = EmailReply::query()
            ->where('email_id', $id)
            ->where('user_id', Auth::id())
            ->where('account', $accountId)
            ->first();
        $history = $reply->chat_history ?? [];

        // Determine instruction source: template, custom instruction, or refinement options
        $instruction = '';
        Log::info('Template lookup debug', [
            'templateId' => $validated['templateId'] ?? null,
            'templateId_empty' => empty($validated['templateId']),
            'validated_data' => $validated,
        ]);
        
        if (! empty($validated['templateId'])) {
            $template = $this->quickReplyService->getTemplate($validated['templateId']);
            Log::info('Template retrieval result', [
                'template_found' => $template !== null,
                'template_id' => $validated['templateId'],
                'template_data' => $template ? $template->toArray() : null,
            ]);
            
            if ($template !== null) {
                $instruction = "Use this template as the basis for your reply, adapting it to respond to the specific email context: \"{$template->template_text}\"";
                Log::info('Generated template instruction', ['instruction' => $instruction]);
            }
        } elseif (! empty($validated['instruction'])) {
            $instruction = $validated['instruction'];
        }

        // Generate reply using AI - support both methods
        if (! empty($validated['refinementOptions'])) {
            $result = $this->aiClient->generateReplyWithOptions($email, $validated['refinementOptions'], $history);
        } else {
            $instruction = $instruction ?: 'Generate a reply to this email.';
            Log::info('Sending instruction to AI', [
                'instruction' => $instruction,
                'email_id' => $id,
                'template_id' => $validated['templateId'] ?? null,
            ]);
            $result = $this->aiClient->generateReply($email, $instruction, $history);
        }

        $this->mailerService->saveDraftReply($id, $result['reply'], $result['chat_history'], $accountId);

        return Inertia::render('ImapEngineInbox/Show', [
            'email' => $email,
            'latestReply' => $result['reply'],
            'chatHistory' => $result['chat_history'],
            'signature' => config('signatures.'.$accountId) ?? config('signatures.default'),
            'message' => 'Reply generated successfully.',
            'success' => true,
            'account' => $accountId,
            'quickReplyTemplates' => $this->quickReplyService->getUserTemplates(),
        ]);
    }

    /**
     * Send the AI reply.
     */
    public function sendReply(Request $request, string $id): Response|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'reply' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $account = $request->query('account');
        $accountId = $account ?? config('imapengine.default', 'default');

        $email = $this->imapClient->getEmail($id, $accountId);
        if ($email === null) {
            return Inertia::render('ImapEngineInbox/Show', [
                'email' => null,
                'account' => $accountId,
                'message' => 'Email not found',
                'success' => false,
            ]);
        }

        $signature = mb_trim($validated['signature'] ?? '');
        $combined = mb_trim($validated['reply']);
        if ($signature !== '') {
            $combined .= "\n\n".$signature;
        }

        EmailReply::updateOrCreate(
            ['email_id' => $id, 'account' => $accountId],
            ['latest_ai_reply' => $combined, 'sent_at' => now()]
        );

        $sent = $this->mailerService->sendReply($email, $combined, $accountId);

        if ($sent) {
            return to_route('imapengine.inbox.index', ['account' => $accountId])
                ->with('message', 'Reply sent successfully')
                ->with('success', true);
        }

        return Inertia::render('ImapEngineInbox/Show', [
            'email' => $email,
            'latestReply' => $validated['reply'],
            'signature' => config('signatures.'.$accountId) ?? config('signatures.default'),
            'message' => 'Failed to send reply. Please try again.',
            'success' => false,
            'account' => $accountId,
        ]);
    }
}
