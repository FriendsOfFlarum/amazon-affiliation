<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation;

use Flarum\Extend;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),
    new Extend\Locales(__DIR__.'/resources/locale'),

    function (Dispatcher $events, Application $app) {
        $events->subscribe(Listeners\AlterAmazonLinks::class);
        $app->register(Providers\LinkManipulatorProvider::class);
    },
];
