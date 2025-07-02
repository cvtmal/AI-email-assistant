<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface ImapClientInterface
{
    /**
     * Get emails from the inbox.
     *
     * @param  string|null  $account  The account identifier, default is used if null
     * @return Collection<int, array{id: string, subject: string, from: string, date: string, message_id: string}>
     */
    public function getInboxEmails(?string $account = null): Collection;

    /**
     * Get a specific email by ID.
     *
     * @param  string  $id  The email ID
     * @param  string|null  $account  The account identifier, default is used if null
     * @return array{id: string, subject: string, from: string, to: string, date: string, body: string, html: string|null, message_id: string}|null
     */
    public function getEmail(string $id, ?string $account = null): ?array;
}
