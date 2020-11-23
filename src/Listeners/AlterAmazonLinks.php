<?php

namespace FoF\AmazonAffiliation\Listeners;

use Flarum\Formatter\Event\Rendering;
use FoF\AmazonAffiliation\AmazonLinkManipulator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Uri;
use s9e\TextFormatter\Utils;

class AlterAmazonLinks
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Rendering::class, [$this, 'configure']);
    }

    public function configure(Rendering $event)
    {
        $event->xml = Utils::replaceAttributes($event->xml, 'URL', function ($attributes) {
            if (Arr::has($attributes, 'url')) {
                /**
                 * @var AmazonLinkManipulator
                 */
                $manipulator = app(AmazonLinkManipulator::class);

                $uri = $manipulator->process(new Uri(Arr::get($attributes, 'url')));

                if ($uri) {
                    $attributes['url'] = (string) $uri;
                }
            }

            return $attributes;
        });
    }
}
