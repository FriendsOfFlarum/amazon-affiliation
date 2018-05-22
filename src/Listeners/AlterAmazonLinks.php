<?php

namespace Flagrow\AmazonAffiliation\Listeners;

use Flagrow\AmazonAffiliation\AmazonLinkManipulator;
use Flarum\Event\ConfigureFormatterRenderer;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;
use Zend\Diactoros\Uri;

class AlterAmazonLinks
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureFormatterRenderer::class, [$this, 'configure']);
    }

    public function configure(ConfigureFormatterRenderer $event)
    {
        $event->xml = Utils::replaceAttributes($event->xml, 'URL', function ($attributes) {
            if (array_has($attributes, 'url')) {
                /**
                 * @var $manipulator AmazonLinkManipulator
                 */
                $manipulator = app(AmazonLinkManipulator::class);

                $uri = $manipulator->process(new Uri(array_get($attributes, 'url')));

                if ($uri) {
                    $attributes['url'] = (string)$uri;
                }
            }

            return $attributes;
        });
    }
}
