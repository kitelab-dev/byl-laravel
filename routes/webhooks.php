<?php

use Byl\Laravel\Http\Controllers\HandleWebhookController;
use Byl\Laravel\Http\Middleware\VerifyBylSignature;
use Illuminate\Support\Facades\Route;

/*
| Byl-ийн webhook хүлээн авах route. config/byl.php дотор
| `webhook.route.enabled` = false болговол бүртгэгдэхгүй —
| тэр үед өөрийн route дээр `byl-signature` middleware хэрэглээрэй.
*/

if (! config('byl.webhook.route.enabled', true)) {
    return;
}

Route::post(config('byl.webhook.route.path', 'byl/webhook'), HandleWebhookController::class)
    ->middleware([VerifyBylSignature::class, ...(array) config('byl.webhook.route.middleware', [])])
    ->name(config('byl.webhook.route.name', 'byl.webhook'));
