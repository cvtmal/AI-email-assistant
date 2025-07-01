<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AIClientInterface;
use App\Models\EmailReply;
use Exception;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Log;

final class AIClient implements AIClientInterface
{
    private string $apiKey;

    private string $apiUrl;

    private Factory $http;

    public function __construct(?Factory $http = null)
    {
        $this->apiKey = Config::get('services.ai.key');
        $this->apiUrl = Config::get('services.ai.url');
        $this->http = $http ?? Http::getFacadeRoot();
    }

    /**
     * Generate a reply for an email using the AI
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
     * } $email The email data
     * @param  string  $instruction  User's instruction for the AI
     * @param  array<int, array{role: string, content: string}>  $chatHistory  Previous chat history
     * @return array{
     *  reply: string,
     *  chat_history: array<int, array{role: string, content: string}>
     * }
     *
     * @throws Exception
     */
    public function generateReply(array $email, string $instruction, array $chatHistory = []): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => <<<'TXT'
You are an email assistant that helps the user craft replies. The user will provide you with an email to respond to and specific instructions on how to craft the reply. Generate a professional and appropriate response according to the user's instructions. Do not include any email closing, signature or placeholder fields. Return ONLY the reply body text (including greeting and closing phrases) with NO "Subject:" line.
TXT,
        ];

        $emailContextMessage = [
            'role' => 'user',
            'content' => "I need to reply to this email:\n\nFrom: {$email['from']}\nSubject: {$email['subject']}\nDate: {$email['date']}\n\n{$email['body']}",
        ];

        $instructionMessage = [
            'role' => 'user',
            'content' => $instruction,
        ];

        // Construct the messages array for the AI
        $messages = [$systemMessage];

        // If we have chat history, add it after the system message
        if (! empty($chatHistory)) {
            array_push($messages, ...$chatHistory);
        } else {
            // If this is the first interaction, add the email context
            $messages[] = $emailContextMessage;
        }

        // Add the latest instruction
        $messages[] = $instructionMessage;

        // Call the AI API
        $response = $this->getClient()->post($this->apiUrl, [
            'model' => 'gpt-4o',
            'messages' => $messages,
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to generate reply: {$response->body()}");
        }

        $data = $response->json();
        $reply = $data['choices'][0]['message']['content'] ?? '';

        // Update the chat history with the new messages and AI response
        if (empty($chatHistory)) {
            $chatHistory[] = $emailContextMessage;
        }

        $chatHistory[] = $instructionMessage;
        $chatHistory[] = [
            'role' => 'assistant',
            'content' => $reply,
        ];

        return [
            'reply' => $reply,
            'chat_history' => $chatHistory,
        ];
    }

    /**
     * Generate a reply using structured refinement options
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
     * } $email The email data
     * @param  array<string, mixed>  $options  Structured refinement options
     * @param  array<int, array{role: string, content: string}>  $chatHistory  Previous chat history
     * @return array{
     *  reply: string,
     *  chat_history: array<int, array{role: string, content: string}>
     * }
     *
     * @throws Exception
     */
    public function generateReplyWithOptions(array $email, array $options, array $chatHistory = []): array
    {
        $instruction = $this->buildInstructionFromOptions($options);
        Log::info('Generated instruction from options:', ['instruction' => $instruction]);

        return $this->generateReply($email, $instruction, $chatHistory);
    }

    /**
     * Add a user instruction and AI reply to the chat history for an email
     *
     * @param  string  $emailId  The email ID to add chat history for
     * @param  string  $userInstruction  The user's instruction to the AI
     * @param  string  $aiReply  The AI's reply
     * @return bool Whether the chat history was successfully saved
     */
    public function addToChatHistory(string $emailId, string $userInstruction, string $aiReply): bool
    {
        try {
            $existingReply = EmailReply::firstOrNew(['email_id' => $emailId]);

            // Initialize chat history if it doesn't exist
            $chatHistory = $existingReply->chat_history ?? [];

            // If this is the first interaction, add the system message
            if (empty($chatHistory)) {
                $chatHistory[] = [
                    'role' => 'system',
                    'content' => 'You are a helpful email assistant.',
                ];
            }

            // Add user instruction
            $chatHistory[] = [
                'role' => 'user',
                'content' => $userInstruction,
            ];

            // Add AI reply
            $chatHistory[] = [
                'role' => 'assistant',
                'content' => $aiReply,
            ];

            // Update the model
            $existingReply->chat_history = $chatHistory;
            $existingReply->latest_ai_reply = $aiReply;
            $existingReply->save();

            return true;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to add chat history', [
                'email_id' => $emailId,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build a natural language instruction from structured options
     *
     * @param  array<string, mixed>  $options  The refinement options
     * @return string The generated instruction
     */
    private function buildInstructionFromOptions(array $options): string
    {
        if (! empty($options['customInstruction'])) {
            return $options['customInstruction'];
        }

        $parts = [];

        // Tone instruction
        if (! empty($options['tone'])) {
            $toneDescriptions = [
                'professional' => 'professional and business-appropriate',
                'friendly' => 'friendly and warm',
                'casual' => 'casual and relaxed',
                'formal' => 'formal and respectful',
                'warm' => 'warm and caring',
                'direct' => 'direct and to-the-point',
            ];

            $tone = $options['tone'];
            if (isset($toneDescriptions[$tone])) {
                $parts[] = "Write in a {$toneDescriptions[$tone]} tone";
            }
        }

        // Length instruction
        if (! empty($options['length'])) {
            $lengthDescriptions = [
                'concise' => 'make it concise and brief',
                'medium' => 'use a balanced length',
                'detailed' => 'make it detailed and comprehensive',
            ];

            $length = $options['length'];
            if (isset($lengthDescriptions[$length])) {
                $parts[] = $lengthDescriptions[$length];
            }
        }

        // Formality instruction
        if (isset($options['formality']) && $options['formality'] !== 3) {
            $formality = (int) $options['formality'];
            $formalityDescriptions = [
                1 => 'very casual language',
                2 => 'casual language',
                4 => 'formal language',
                5 => 'very formal language',
            ];

            if (isset($formalityDescriptions[$formality])) {
                $parts[] = "use {$formalityDescriptions[$formality]}";
            }
        }

        // Urgency instruction
        if (! empty($options['urgency']) && $options['urgency'] === 'high') {
            $parts[] = 'convey appropriate urgency';
        }

        if (empty($parts)) {
            return 'Refine this reply to make it better.';
        }

        return ucfirst(implode(', ', $parts)).'.';
    }

    /**
     * Get the HTTP client with proper authentication headers
     */
    private function getClient(): PendingRequest
    {
        return $this->http->withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ]);
    }
}
