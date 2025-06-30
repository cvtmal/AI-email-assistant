<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for AI Client services
 */
interface AIClientInterface
{
    /**
     * Generate an AI reply based on an email and user instruction
     *
     * @param  array<string, mixed>  $email  The email data
     * @param  string  $userInstruction  The user's instruction
     * @param  array<int, array{role: string, content: string}>  $chatHistory  Previous chat history
     * @return array{reply: string, chat_history: array<int, array{role: string, content: string}>}
     */
    public function generateReply(array $email, string $userInstruction, array $chatHistory = []): array;

    /**
     * Generate an AI reply using structured refinement options
     *
     * @param  array<string, mixed>  $email  The email data
     * @param  array<string, mixed>  $options  Structured refinement options
     * @param  array<int, array{role: string, content: string}>  $chatHistory  Previous chat history
     * @return array{reply: string, chat_history: array<int, array{role: string, content: string}>}
     */
    public function generateReplyWithOptions(array $email, array $options, array $chatHistory = []): array;

    /**
     * Add a conversation exchange to the chat history
     *
     * @param  string  $emailId  The email ID
     * @param  string  $userInstruction  The user's instruction
     * @param  string  $aiReply  The AI's reply
     * @return bool Whether the operation succeeded
     */
    public function addToChatHistory(string $emailId, string $userInstruction, string $aiReply): bool;
}
