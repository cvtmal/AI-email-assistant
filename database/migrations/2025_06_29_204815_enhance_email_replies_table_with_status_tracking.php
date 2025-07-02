<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_replies', function (Blueprint $table): void {
            // Add user relationship
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Add status tracking
            $table->enum('status', ['draft', 'sending', 'sent', 'failed'])->default('draft')->after('account');

            // Add timestamps for different states
            $table->timestamp('failed_at')->nullable()->after('sent_at');

            // Add error tracking
            $table->text('error_message')->nullable()->after('failed_at');

            // Add recipient tracking
            $table->string('recipient_email')->nullable()->after('error_message');
            $table->string('subject')->nullable()->after('recipient_email');

            // Add indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'account']);
            $table->index(['user_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_replies', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'status',
                'failed_at',
                'error_message',
                'recipient_email',
                'subject',
            ]);
        });
    }
};
