<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AIClientInterface;
use App\Contracts\ImapClientInterface;
use App\Contracts\MailerServiceInterface;
use App\Models\EmailReply;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InboxController
{
    public function __construct(private AIClientInterface $aiClient, private MailerServiceInterface $mailerService, private ImapClientInterface $imapClient) {}

    /**
     * Display a listing of the inbox emails.
     *
     * @param  Request  $request  The incoming request
     * @param  string|null  $account  The account identifier
     */
    public function index(Request $request, ?string $account = null): Response
    {
        try {
            // Get emails from IMAP client
            $this->imapClient->getInboxEmails();

            // Determine the account to use
            $accountId = $account ?? config('imap.default', 'default');

            // Log configuration status
            $imap_host = config("imap.accounts.{$accountId}.host");
            $imap_username = config("imap.accounts.{$accountId}.username");

            logger()->info('IMAP Config Check', [
                'host' => $imap_host,
                'username' => $imap_username,
                'account' => $accountId,
                'client_class' => $this->imapClient::class,
            ]);

            // Get emails with detailed logging
            logger()->info('Attempting to retrieve emails from IMAP server', ['account' => $accountId]);
            $emails = $this->imapClient->getInboxEmails($accountId);

            // Log the emails that were retrieved
            logger()->info('Retrieved '.$emails->count().' emails', [
                'sample_emails' => $emails->take(3)->map(fn ($email): array => [
                    'subject' => $email['subject'],
                    'from' => $email['from'],
                    'has_date' => 'yes',
                    'date_type' => gettype($email['date']),
                ])->toArray(),
            ]);

            // Check if we have any emails
            if ($emails->isEmpty()) {
                logger()->warning('No emails found in IMAP inbox');
            }

            // Ensure emails is a proper array for JSON serialization
            $emailsArray = $emails->toArray();

            // Add detailed debugging to check the structure of each email
            foreach ($emailsArray as $index => $email) {
                $id = $email['id'] ?? null;
                $subject = $email['subject'] ?? null;
                $from = $email['from'] ?? null;
                $date = $email['date'] ?? null;
                $messageId = $email['message_id'] ?? null;

                logger()->info("Email data structure for email #{$index}", [
                    'id_type' => gettype($id),
                    'subject_type' => gettype($subject),
                    'from_type' => gettype($from),
                    'date_type' => gettype($date),
                    'message_id_type' => gettype($messageId),
                    'id' => $id,
                    'date' => $date,
                ]);

                // Ensure all values are strings or primitive types
                foreach ($email as $key => $value) {
                    if (is_object($value) || (is_array($value) && $value !== [])) {
                        logger()->error('Non-primitive value found in email data', [
                            'key' => $key,
                            'value_type' => gettype($value),
                            'value' => get_debug_type($value),
                        ]);

                        // Convert to string to prevent React errors
                        $emailsArray[$index][$key] = is_object($value) ? '[Object]' : json_encode($value);
                    }
                }
            }

            logger()->info('Preparing to render Inbox/Index with '.count($emailsArray).' emails');

            return Inertia::render('Inbox/Index', [
                'emails' => $emailsArray,
            ]);
        } catch (Exception $e) {
            logger()->error('Error retrieving emails from IMAP server', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Inbox/Index', [
                'emails' => [],
                'error' => 'Failed to connect to the email server: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified email.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $id  The ID of the email to show
     * @param  string|null  $account  The account identifier
     */
    public function show(Request $request, string $id, ?string $account = null): Response
    {
        $accountId = $account ?? config('imap.default', 'default');
        logger()->info('Show email requested for ID: '.$id, ['account' => $accountId]);

        try {
            $email = $this->imapClient->getEmail($id, $accountId);

            if ($email === null) {
                logger()->warning('Email not found with ID: '.$id);

                return Inertia::render('Inbox/NotFound');
            }

            logger()->info('Email found and retrieved', ['subject' => $email['subject'] ?? 'No Subject']);

            // Apply the same type safety check as in the index method
            foreach ($email as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    logger()->error('Non-primitive value found in email detail data', [
                        'key' => $key,
                        'type' => get_debug_type($value),
                    ]);
                }
            }

            // Get any existing reply for this email and account
            $reply = EmailReply::query()
                ->where('email_id', $id)
                ->where(function ($query) use ($accountId): void {
                    $query->where('account', $accountId)
                        ->orWhereNull('account'); // For backward compatibility with existing replies
                })
                ->first();
            $chatHistory = $reply->chat_history ?? [];

            return Inertia::render('Inbox/Show', [
                'email' => $email,
                'latestReply' => $reply?->latest_ai_reply,
                'signature' => config('signatures.'.$accountId) ?? config('signatures.default'),
                'chatHistory' => $chatHistory,
            ]);
        } catch (Exception $e) {
            logger()->error('Error retrieving email details', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Inbox/NotFound', [
                'error' => 'Failed to retrieve email: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Generate an AI reply for an email.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $id  The ID of the email
     * @param  string|null  $account  The account identifier
     */
    public function generateReply(Request $request, string $id, ?string $account = null): Response
    {
        $validated = $request->validate([
            'instruction' => ['nullable', 'string'],
            'refinementOptions' => ['nullable', 'array'],
            'refinementOptions.tone' => ['nullable', 'string', 'in:professional,friendly,casual,formal,warm,direct'],
            'refinementOptions.length' => ['nullable', 'string', 'in:concise,medium,detailed'],
            'refinementOptions.formality' => ['nullable', 'integer', 'min:1', 'max:5'],
            'refinementOptions.urgency' => ['nullable', 'string', 'in:low,normal,high'],
            'refinementOptions.customInstruction' => ['nullable', 'string'],
        ]);

        $accountId = $account ?? config('imap.default', 'default');
        $email = $this->imapClient->getEmail($id, $accountId);

        if ($email === null) {
            return Inertia::render('Inbox/NotFound');
        }

        // Get any existing chat history for this email and account
        $reply = EmailReply::query()
            ->where('email_id', $id)
            ->where(function ($query) use ($accountId): void {
                $query->where('account', $accountId)
                    ->orWhereNull('account'); // For backward compatibility
            })
            ->first();
        $chatHistory = $reply->chat_history ?? [];

        // Generate reply using AI - support both methods
        if (! empty($validated['refinementOptions'])) {
            $result = $this->aiClient->generateReplyWithOptions($email, $validated['refinementOptions'], $chatHistory);
        } else {
            $instruction = $validated['instruction'] ?? 'Generate a reply to this email.';
            $result = $this->aiClient->generateReply($email, $instruction, $chatHistory);
        }

        // Save the reply and chat history with account information
        $this->mailerService->saveDraftReply($id, $result['reply'], $result['chat_history'], $accountId);

        return Inertia::render('Inbox/Show', [
            'email' => $email,
            'latestReply' => $result['reply'],
            'chatHistory' => $result['chat_history'],
            'signature' => config('signatures.'.$accountId) ?? config('signatures.default'),
            'message' => 'Reply generated successfully.',
        ]);
    }

    /**
     * Send an email reply.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $id  The ID of the email
     * @param  string|null  $account  The account identifier
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    public function sendReply(Request $request, string $id, ?string $account = null)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $accountId = $account ?? config('imap.default', 'default');
        $email = $this->imapClient->getEmail($id, $accountId);

        if ($email === null) {
            return Inertia::render('Inbox/NotFound');
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

        $success = $this->mailerService->sendReply($email, $combined, $accountId);

        if ($success) {
            return to_route('inbox.index')
                ->with('message', 'Reply sent successfully')
                ->with('success', true);
        }

        return Inertia::render('Inbox/Show', [
            'email' => $email,
            'latestReply' => $validated['reply'],
            'message' => 'Failed to send reply. Please try again.',
            'success' => false,
        ]);

    }
}
