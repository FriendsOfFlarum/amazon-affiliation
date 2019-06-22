<?php

namespace Flagrow\AmazonAffiliation;

use Flarum\Extend;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    function (Dispatcher $events, Application $app) {
        $events->subscribe(Listeners\AlterAmazonLinks::class);
        $app->register(Providers\LinkManipulatorProvider::class);
    }
];
