<?php

namespace Flagrow\AmazonAffiliation;

use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events, Application $app) {
    $events->subscribe(Listeners\AlterAmazonLinks::class);
    $events->subscribe(Listeners\Assets::class);

    $app->register(Providers\LinkManipulatorProvider::class);
};
