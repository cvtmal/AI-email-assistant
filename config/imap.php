<?php

declare(strict_types=1);
/*
* File:     imap.php
* Category: config
* Author:   M. Goldenbaum
* Created:  24.09.16 22:36
* Updated:  -
*
* Description:
*  -
*/

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP default account
    |--------------------------------------------------------------------------
    |
    | The default account identifier. It will be used as default for any missing account parameters.
    | If however the default account is missing a parameter the package default will be used.
    | Set to 'false' [boolean] to disable this functionality.
    |
    */
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Available accounts
    |--------------------------------------------------------------------------
    |
    | Please list all IMAP accounts which you are planning to use within the
    | array below.
    |
    */
    'accounts' => [

        'default' => [
            'host' => env('IMAP_HOST', 'localhost'),
            'port' => env('IMAP_PORT', 993),
            'protocol' => env('IMAP_PROTOCOL', 'imap'),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_USERNAME', 'user@example.com'),
            'password' => env('IMAP_PASSWORD', ''),
            'authentication' => env('IMAP_AUTHENTICATION', null),
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
        ],

        'info' => [
            'host' => env('IMAP_INFO_HOST'),
            'port' => env('IMAP_INFO_PORT', 993),
            'encryption' => env('IMAP_INFO_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_INFO_VALIDATE_CERT', true),
            'username' => env('IMAP_INFO_USERNAME'),
            'password' => env('IMAP_INFO_PASSWORD'),
        ],

        'damian' => [
            'host' => env('IMAP_DAMIAN_HOST'),
            'port' => env('IMAP_DAMIAN_PORT', 993),
            'encryption' => env('IMAP_DAMIAN_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_DAMIAN_VALIDATE_CERT', true),
            'username' => env('IMAP_DAMIAN_USERNAME'),
            'password' => env('IMAP_DAMIAN_PASSWORD'),
            // Memory optimization options
            'options' => [
                'fetch_order' => 'desc', // Newest messages first
                'limit' => env('IMAP_DAMIAN_MESSAGE_LIMIT', 50), // Limit to recent messages
                'fetch_body' => false, // Don't fetch body in initial listing
                'soft_fail' => true, // Continue on errors
            ],
        ],

        /*
        'gmail' => [ // account identifier
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => 'example@gmail.com',
            'password' => 'PASSWORD',
            'authentication' => 'oauth',
        ],

        'another' => [ // account identifier
            'host' => '',
            'port' => 993,
            'encryption' => false,
            'validate_cert' => true,
            'username' => '',
            'password' => '',
            'authentication' => null,
        ]
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Available IMAP options
    |--------------------------------------------------------------------------
    |
    | Available php imap config parameters are listed below
    |   -Delimiter (optional):
    |       This option is only used when calling $oClient->
    |       You can use any supported char such as ".", "/", (...)
    |   -Fetch option:
    |       IMAP::FT_UID  - Message marked as read by fetching the body message
    |       IMAP::FT_PEEK - Fetch the message without setting the "seen" flag
    |   -Fetch sequence id:
    |       IMAP::ST_UID  - Fetch message components using the message uid
    |       IMAP::ST_MSGN - Fetch message components using the message number
    |   -Fetch sequence type:
    |       IMAP::SE_UID  - Fetch message components using the message uid
    |       IMAP::SE_ID   - Fetch message components using the message number
    |   -Open IMAP options:
    |       IMAP::OP_READONLY   - Open mailbox read-only
    |       IMAP::OP_ANONYMOUS  - Don't use or update a .newsrc for news
    |       IMAP::OP_HALFOPEN   - For IMAP and NNTP names, open a connection but don't open a mailbox.
    |       IMAP::OP_EXPUNGE    - Silently expunge recycle stream
    |       IMAP::OP_DEBUG      - Debug protocol negotiations
    |       IMAP::OP_SHORTCACHE - Short (elt-only) caching
    |       IMAP::OP_SILENT     - Don't pass up events (internal use)
    |       IMAP::OP_PROTOTYPE  - Return driver prototype
    |       IMAP::OP_SECURE     - Don't do non-secure authentication
    |
    |   More information under: http://php.net/manual/en/function.imap-open.php
    |
    */
    'options' => [
        'delimiter' => env('IMAP_OPTIONS_DELIMITER', '/'),
        'fetch' => env('IMAP_OPTIONS_FETCH', Webklex\PHPIMAP\IMAP::FT_PEEK),
        'sequence' => env('IMAP_OPTIONS_SEQUENCE', Webklex\PHPIMAP\IMAP::ST_UID),
        'fetch_order' => env('IMAP_OPTIONS_FETCH_ORDER', 'asc'),
        'fetch_limit' => env('IMAP_OPTIONS_FETCH_LIMIT', 0),
        'open' => [
            // IMAP::OP_READONLY, IMAP::OP_ANONYMOUS, IMAP::OP_HALFOPEN, IMAP::OP_EXPUNGE, IMAP::OP_DEBUG, IMAP::OP_SHORTCACHE, IMAP::OP_SILENT, IMAP::OP_PROTOTYPE, IMAP::OP_SECURE
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available events
    |--------------------------------------------------------------------------
    |
    | Available events
    |
    */
    'events' => [
        'message' => [
            'new' => Webklex\PHPIMAP\Events\MessageNewEvent::class,
            'moved' => Webklex\PHPIMAP\Events\MessageMovedEvent::class,
            'copied' => Webklex\PHPIMAP\Events\MessageCopiedEvent::class,
            'deleted' => Webklex\PHPIMAP\Events\MessageDeletedEvent::class,
            'restored' => Webklex\PHPIMAP\Events\MessageRestoredEvent::class,
        ],
        'folder' => [
            'new' => Webklex\PHPIMAP\Events\FolderNewEvent::class,
            'moved' => Webklex\PHPIMAP\Events\FolderMovedEvent::class,
            'deleted' => Webklex\PHPIMAP\Events\FolderDeletedEvent::class,
        ],
        'flag' => [
            'new' => Webklex\PHPIMAP\Events\FlagNewEvent::class,
            'deleted' => Webklex\PHPIMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available flags
    |--------------------------------------------------------------------------
    |
    | List all available / supported flags. Set to null to accept all given flags.
     */
    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    /*
    |--------------------------------------------------------------------------
    | Available search criteria
    |--------------------------------------------------------------------------
    |
    | Available search criteria. You can also use the \Webklex\PHPIMAP\Criteria\Where class as a shortcut.
    |
    | More information under: http://php.net/manual/en/function.imap-search.php
    |
    */
    'search' => [
        'all' => 'ALL', // return all messages matching the rest of the criteria
        'answered' => 'ANSWERED', // match messages with the \\ANSWERED flag set
        'bcc' => 'BCC', // match messages with "string" in the Bcc: field
        'before' => 'BEFORE', // match messages with Date: before "date"
        'body' => 'BODY', // match messages with "string" in the body of the message
        'cc' => 'CC', // match messages with "string" in the Cc: field
        'deleted' => 'DELETED', // match deleted messages
        'flagged' => 'FLAGGED', // match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
        'from' => 'FROM', // match messages with "string" in the From: field
        'keyword' => 'KEYWORD', // match messages with "string" as a keyword
        'new' => 'NEW', // match new messages
        'old' => 'OLD', // match old messages
        'on' => 'ON', // match messages with Date: matching "date"
        'recent' => 'RECENT', // match messages with the \\RECENT flag set
        'seen' => 'SEEN', // match messages that have been read (the \\SEEN flag is set)
        'since' => 'SINCE', // match messages with Date: after "date"
        'subject' => 'SUBJECT', // match messages with "string" in the Subject:
        'text' => 'TEXT', // match messages with text "string"
        'to' => 'TO', // match messages with "string" in the To:
        'unanswered' => 'UNANSWERED', // match messages that have not been answered
        'undeleted' => 'UNDELETED', // match messages that are not deleted
        'unflagged' => 'UNFLAGGED', // match messages that are not flagged
        'unkeyword' => 'UNKEYWORD', // match messages that do not have the keyword "string"
        'unseen' => 'UNSEEN', // match messages which have not been read yet
    ],

    /*
    |--------------------------------------------------------------------------
    | Available masks
    |--------------------------------------------------------------------------
    |
    | By using your own custom masks you can implement your own methods for
    | a quick start.
    |
    | The provided masks below are used as the default masks.
    */
    'masks' => [
        'message' => Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => Webklex\PHPIMAP\Support\Masks\AttachmentMask::class,
    ],
];
