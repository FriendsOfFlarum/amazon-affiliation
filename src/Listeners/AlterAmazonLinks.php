<?php

namespace FoF\AmazonAffiliation\Listeners;

use Flarum\Formatter\Event\Rendering;
use FoF\AmazonAffiliation\AmazonLinkManipulator;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;
use Zend\Diactoros\Uri;

class AlterAmazonLinks
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Rendering::class, [$this, 'configure']);
    }

    public function configure(Rendering $event)
    {
        $event->xml = Utils::replaceAttributes($event->xml, 'URL', function ($attributes) {
            if (array_has($attributes, 'url')) {
                /**
                 * @var AmazonLinkManipulator
                 */
                $manipulator = app(AmazonLinkManipulator::class);

                $uri = $manipulator->process(new Uri(array_get($attributes, 'url')));

                if ($uri) {
                    $attributes['url'] = (string) $uri;
                }
            }

            return $attributes;
        });
    }
}
