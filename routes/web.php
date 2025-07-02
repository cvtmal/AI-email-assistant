<?php

declare(strict_types=1);

use App\Http\Controllers\EmailActivityController;
use App\Http\Controllers\ImapEngineInboxController;
use App\Http\Controllers\QuickReplyTemplateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('imapengine-inbox', [ImapEngineInboxController::class, 'index'])->name('imapengine.inbox.index');
    Route::get('imapengine-inbox/{id}', [ImapEngineInboxController::class, 'show'])->name('imapengine.inbox.show');
    Route::post('imapengine-inbox/{id}/generate-reply', [ImapEngineInboxController::class, 'generateReply'])->name('imapengine.inbox.generate-reply');
    Route::post('imapengine-inbox/{id}/send-reply', [ImapEngineInboxController::class, 'sendReply'])->name('imapengine.inbox.send-reply');

    Route::get('email-activity', [EmailActivityController::class, 'index'])->name('email-activity.index');
    Route::get('api/email-activity', [EmailActivityController::class, 'apiActivity'])->name('email-activity.api');

    Route::get('settings/quick-reply-templates', [QuickReplyTemplateController::class, 'index'])->name('quick-reply-templates.index');
    Route::post('settings/quick-reply-templates', [QuickReplyTemplateController::class, 'store'])->name('quick-reply-templates.store');
    Route::put('settings/quick-reply-templates/{id}', [QuickReplyTemplateController::class, 'update'])->name('quick-reply-templates.update');
    Route::delete('settings/quick-reply-templates/{id}', [QuickReplyTemplateController::class, 'destroy'])->name('quick-reply-templates.destroy');
    Route::post('settings/quick-reply-templates/create-defaults', [QuickReplyTemplateController::class, 'createDefaults'])->name('quick-reply-templates.create-defaults');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
