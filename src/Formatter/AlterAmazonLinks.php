<?php

namespace FoF\AmazonAffiliation\Formatter;

use FoF\AmazonAffiliation\AmazonLinkManipulator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Uri;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class AlterAmazonLinks
{
    public function __invoke(Renderer $renderer, $context, $xml)
    {
        return Utils::replaceAttributes($xml, 'URL', function ($attributes) {
            if (Arr::has($attributes, 'url')) {
                /**
                 * @var AmazonLinkManipulator
                 */
                $manipulator = resolve(AmazonLinkManipulator::class);

                $uri = $manipulator->process(new Uri(Arr::get($attributes, 'url')));

                if ($uri) {
                    $attributes['url'] = (string) $uri;
                }
            }

            return $attributes;
        });
    }
}
