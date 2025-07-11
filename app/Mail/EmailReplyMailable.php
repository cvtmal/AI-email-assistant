<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class EmailReplyMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance
     *
     * @param  string  $replyContent  The content of the email reply
     * @param  string  $emailSubject  The subject of the email
     * @param  string  $recipientEmail  The recipient's email address
     * @param  string  $originalMessageId  The original message ID being replied to
     * @param  string|null  $account  The account identifier to send from
     */
    public function __construct(
        private readonly string $replyContent,
        private readonly string $recipientEmail,
        private readonly string $originalMessageId,
        private readonly ?string $account = null,
        string $emailSubject = '',
    ) {
        $this->subject($emailSubject);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $mailerKey = $this->resolveMailerKey($this->account ?? 'default');

        $fromAddress = config("mail.mailers.{$mailerKey}.from.address", config('mail.from.address'));
        $fromName = config("mail.mailers.{$mailerKey}.from.name", config('mail.from.name'));

        return $this
            ->to($this->recipientEmail)
            ->replyTo($fromAddress, $fromName)
            ->view('emails.reply-html', ['content' => $this->replyContent])
            ->withSymfonyMessage(function ($message): void {
                $message->getHeaders()
                    ->addTextHeader('References', $this->originalMessageId)
                    ->addTextHeader('In-Reply-To', $this->originalMessageId);
            });
    }

    /**
     * Resolve the Laravel mailer key for a given logical account identifier.
     */
    private function resolveMailerKey(string $accountId): string
    {
        return match ($accountId) {
            'info' => 'smtp1',
            'damian' => 'smtp2',
            default => 'smtp',
        };
    }
}
