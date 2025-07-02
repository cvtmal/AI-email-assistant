<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MailerServiceInterface;
use App\Mail\EmailReplyMailable;
use App\Models\EmailReply;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class MailerService implements MailerServiceInterface
{
    /**
     * Send a reply to an email
     *
     * @param array{
     *  id: string,
     *  subject: string,
     *  from: string,
     *  to: string,
     *  date: \Carbon\Carbon,
     *  body: string,
     *  html: ?string,
     *  message_id: string,
     * } $email The original email data
     * @param  string  $replyContent  The content of the reply
     * @param  string|null  $account  The account identifier to send from
     * @return bool Whether the email was sent successfully
     */
    public function sendReply(array $email, string $replyContent, ?string $account = null): bool
    {
        $subject = $this->formatReplySubject($email['subject']);
        $accountId = $account ?? 'default';
        $mailerKey = $this->resolveMailerKey($accountId);
        $userId = Auth::id();

        // Convert combined plain text (reply + optional signature) to safe HTML
        $replyHtml = nl2br(e(mb_rtrim($replyContent)));

        // Create or update the email reply record with sending status
        $emailReply = EmailReply::query()->updateOrCreate(
            ['email_id' => $email['id'], 'user_id' => $userId],
            [
                'latest_ai_reply' => $replyContent,
                'account' => $accountId,
                'status' => 'sending',
                'recipient_email' => $email['from'],
                'subject' => $subject,
                'error_message' => null,
                'failed_at' => null,
            ]
        );

        try {
            $mailer = Mail::mailer($mailerKey);

            $mailer->to($email['from'])->send(new EmailReplyMailable(
                replyContent: $replyHtml,
                recipientEmail: $email['from'],
                originalMessageId: $email['message_id'],
                account: $accountId,
                emailSubject: $subject
            ));

            // Update status to sent on success
            $emailReply->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('Email reply sent successfully', [
                'email_id' => $email['id'],
                'account' => $accountId,
                'recipient' => $email['from'],
                'user_id' => $userId,
            ]);

            return true;
        } catch (Exception $e) {
            // Update status to failed on error
            $emailReply->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Failed to send email reply: '.$e->getMessage(), [
                'email_id' => $email['id'],
                'account' => $accountId,
                'recipient' => $email['from'],
                'user_id' => $userId,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Save a draft reply without sending it
     *
     * @param  string  $emailId  The email ID
     * @param  string  $replyContent  The draft reply content
     * @param  array<int, array{role: string, content: string}>  $chatHistory  The chat history
     * @param  string|null  $account  The account identifier
     */
    public function saveDraftReply(string $emailId, string $replyContent, array $chatHistory, ?string $account = null): EmailReply
    {
        $accountId = $account ?? 'default';
        $userId = Auth::id();

        return EmailReply::query()->updateOrCreate(
            ['email_id' => $emailId, 'user_id' => $userId],
            [
                'latest_ai_reply' => $replyContent,
                'chat_history' => $chatHistory,
                'account' => $accountId,
                'status' => 'draft',
                // sent_at remains null for drafts
            ]
        );
    }

    /**
     * Resolve the Laravel mailer key for a given logical account identifier.
     *
     * This maps friendly account names used elsewhere in the code (e.g. "info",
     * "damian", or the default account id) to the mailer keys that are
     * configured in config/mail.php (e.g. "smtp1", "smtp2", etc.). If no
     * mapping exists we fall back to the primary "smtp" mailer.
     */
    private function resolveMailerKey(string $accountId): string
    {
        return match ($accountId) {
            'info' => 'smtp1',
            'damian' => 'smtp2',
            default => 'smtp',
        };
    }

    /**
     * Format the reply subject to include Re: if not already present
     *
     * @param  string  $originalSubject  The original email subject
     * @return string Formatted subject
     */
    private function formatReplySubject(string $originalSubject): string
    {
        if (str_starts_with(mb_strtolower($originalSubject), 're:')) {
            return $originalSubject;
        }

        return "Re: $originalSubject";
    }
}
