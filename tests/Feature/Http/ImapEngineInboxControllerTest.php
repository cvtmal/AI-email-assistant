<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('skips ImapEngine tests due to memory issues', function () {
    $this->markTestSkipped('ImapEngine tests skipped due to memory issues with IMAP package during testing');
});
