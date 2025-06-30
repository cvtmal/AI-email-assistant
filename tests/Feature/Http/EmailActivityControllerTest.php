<?php

declare(strict_types=1);

use App\Models\EmailReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Email Activity Index', function () {
    it('can display email activity dashboard', function () {
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_email' => 'test@example.com',
            'subject' => 'Test Email 1',
        ]);

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'damian',
            'status' => 'failed',
            'failed_at' => now(),
            'recipient_email' => 'test2@example.com',
            'subject' => 'Test Email 2',
            'error_message' => 'SMTP connection failed',
        ]);

        $response = $this->get('/email-activity');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->has('recentActivity')
            ->has('stats')
            ->has('accountStats')
            ->where('currentAccount', null)
        );
    });

    it('can filter activity by account', function () {
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'damian',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email-activity?account=damian');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->where('currentAccount', 'damian')
            ->has('recentActivity')
            ->has('stats')
        );
    });

    it('calculates correct statistics', function () {
        // Create one email sent today
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => today(),
        ]);

        // Create another email sent last week (should not count in this week)
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now()->subWeek(),
        ]);

        // Create a failed email
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        // Create an email for another user (shouldn't count)
        EmailReply::factory()->create([
            'user_id' => User::factory()->create()->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email-activity');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->where('stats.total_sent', 2)
            ->where('stats.sent_today', 1)
            ->where('stats.sent_this_week', 1)
            ->where('stats.failed_count', 1)
        );
    });

    it('groups account statistics correctly', function () {
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'damian',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email-activity');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->has('accountStats.default')
            ->has('accountStats.damian')
            ->where('accountStats.default.sent', 1)
            ->where('accountStats.default.failed', 1)
            ->where('accountStats.damian.sent', 1)
            ->where('accountStats.damian.failed', 0)
        );
    });

    it('limits recent activity to 20 items', function () {
        EmailReply::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email-activity');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->has('recentActivity', 20)
        );
    });

    it('only shows activity for authenticated user', function () {
        $otherUser = User::factory()->create();

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailReply::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/email-activity');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EmailActivity/Index')
            ->has('recentActivity', 1)
            ->where('stats.total_sent', 1)
        );
    });
});

describe('Email Activity API', function () {
    it('can return activity data as JSON', function () {
        $emailReply = EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_email' => 'test@example.com',
            'subject' => 'Test Email',
        ]);

        $response = $this->get('/api/email-activity');

        $response->assertStatus(200);
        $response->assertJson([
            [
                'id' => $emailReply->id,
                'email_id' => $emailReply->email_id,
                'account' => 'default',
                'status' => 'sent',
                'recipient_email' => 'test@example.com',
                'subject' => 'Test Email',
            ],
        ]);
    });

    it('can filter API activity by account', function () {
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'default',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $damianReply = EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'account' => 'damian',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/api/email-activity?account=damian');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJson([
            [
                'id' => $damianReply->id,
                'account' => 'damian',
            ],
        ]);
    });

    it('includes sending status in API response', function () {
        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sending',
            'updated_at' => now(),
        ]);

        $response = $this->get('/api/email-activity');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJson([
            [
                'status' => 'sending',
            ],
        ]);
    });

    it('limits API activity to 10 items', function () {
        EmailReply::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/api/email-activity');

        $response->assertStatus(200);
        $response->assertJsonCount(10);
    });

    it('orders API activity by updated_at desc', function () {
        $oldReply = EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $newReply = EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now()->subHour(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/api/email-activity');

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $data = $response->json();
        expect($data[0]['id'])->toBe($newReply->id);
        expect($data[1]['id'])->toBe($oldReply->id);
    });

    it('only returns API activity for authenticated user', function () {
        $otherUser = User::factory()->create();

        EmailReply::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailReply::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->get('/api/email-activity');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    });
});
