<?php

namespace FoF\AmazonAffiliation\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\AmazonAffiliation\AmazonLinkManipulator;

class LinkManipulatorProvider extends AbstractServiceProvider
{
    const SETTINGS_PREFIX = 'flagrow-amazon-affiliation.affiliate-tag.';

    public function register()
    {
        $this->app->singleton(AmazonLinkManipulator::class, function () {
            /**
             * @var SettingsRepositoryInterface
             */
            $settings = $this->app->make(SettingsRepositoryInterface::class);

            $tags = [];

            foreach ($settings->all() as $key => $value) {
                if (empty($value) || strpos($key, self::SETTINGS_PREFIX) !== 0) {
                    continue;
                }

                $domain = substr($key, strlen(self::SETTINGS_PREFIX));

                $tags[$domain] = $value;
            }

            $manipulator = new AmazonLinkManipulator();

            $manipulator->affiliateTags = $tags;
            $manipulator->keepExistingTag = (bool) $settings->get('fof-amazon-affiliation.keep-existing-tag', false);
            $manipulator->removeTagIfUnhandled = (bool) $settings->get('fof-amazon-affiliation.remove-tag-if-unhandled', false);

            return $manipulator;
        });
    }
}
