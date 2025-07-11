<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME', 'smtps'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', env('SMTP_HOST', '127.0.0.1')),
            'port' => (int) env('MAIL_PORT', env('SMTP_PORT', 2525)),
            'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
            'username' => env('MAIL_USERNAME', env('SMTP_USERNAME')),
            'password' => env('MAIL_PASSWORD', env('SMTP_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'smtp1' => [
            'transport' => 'smtp',
            'scheme' => env('SMTP1_MAIL_SCHEME', env('SMTP1_MAIL_ENCRYPTION', 'smtps')),
            'host' => env('SMTP1_MAIL_HOST', '127.0.0.1'),
            'port' => (int) env('SMTP1_MAIL_PORT', 587),
            'encryption' => env('SMTP1_MAIL_ENCRYPTION', 'tls'),
            'username' => env('SMTP1_MAIL_USERNAME'),
            'password' => env('SMTP1_MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('SMTP1_MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'from' => [
                'address' => env('SMTP1_MAIL_FROM_ADDRESS', 'account1@example.com'),
                'name' => env('SMTP1_MAIL_FROM_NAME', 'Account 1'),
            ],
        ],

        'smtp2' => [
            'transport' => 'smtp',
            'scheme' => env('SMTP2_MAIL_SCHEME', env('SMTP2_MAIL_ENCRYPTION', 'smtps')),
            'host' => env('SMTP2_MAIL_HOST', '127.0.0.1'),
            'port' => (int) env('SMTP2_MAIL_PORT', 587),
            'encryption' => env('SMTP2_MAIL_ENCRYPTION', 'tls'),
            'username' => env('SMTP2_MAIL_USERNAME'),
            'password' => env('SMTP2_MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('SMTP2_MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'from' => [
                'address' => env('SMTP2_MAIL_FROM_ADDRESS', 'account2@example.com'),
                'name' => env('SMTP2_MAIL_FROM_NAME', 'Account 2'),
            ],
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
