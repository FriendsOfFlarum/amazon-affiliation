<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation;

use Flarum\Extend;
use Flarum\Settings\Event\Saving as SettingsSaving;
use Flarum\Settings\SettingsRepositoryInterface;
use s9e\TextFormatter\Configurator;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/resources/less/forum.less'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Settings())
        ->default('fof-amazon-affiliation.keep-existing-tag', false)
        ->default('fof-amazon-affiliation.remove-tag-if-unhandled', false)
        ->default('fof-amazon-affiliation.rich-card', true),

    (new Extend\Formatter())
        ->render(Formatter\AlterAmazonLinks::class),

    (new Extend\ServiceProvider())
        ->register(Providers\LinkManipulatorProvider::class),

    (new Extend\Event())
        ->listen(SettingsSaving::class, function (SettingsSaving $event) {
            foreach ($event->settings as $key => $setting) {
                // The affiliate tags feed the formatter's rendering parameters,
                // and `rich-card` controls whether the card site is registered at
                // all — both change the compiled formatter, so flush its cache.
                if (strpos($key, 'fof-amazon-affiliation.affiliate-tag.') === 0
                    || $key === 'fof-amazon-affiliation.rich-card') {
                    resolve('flarum.formatter')->flush();

                    return;
                }
            }
        }),

    // The rich product card is opt-out via the `rich-card` setting. When it is
    // disabled, the card site is never registered, so Amazon product URLs stay
    // as plain links and are tagged by AlterAmazonLinks (above) like any other
    // Amazon link. When enabled, product URLs become cards.
    (new Extend\Conditional())
        ->whenSetting('fof-amazon-affiliation.rich-card', true, fn () => [
            (new Extend\Formatter())
                ->configure(function (Configurator $configurator): void {
                    /** @var SettingsRepositoryInterface */
                    $settings = resolve(SettingsRepositoryInterface::class);
                    $prefix = 'fof-amazon-affiliation.affiliate-tag.';

                    // s9e/text-formatter removed its built-in Amazon MediaEmbed
                    // site (upstream commit b1d809a0c, 2024-01-05) and Amazon
                    // retired the iframe widget it used, so we re-register the
                    // `amazon` site as a product-link card before populating the
                    // associate-tag parameters its template reads. Re-registering
                    // the same tag/attributes also keeps Amazon embeds stored by
                    // 1.x renderable.
                    //
                    // Only Amazon *product* URLs (/dp/ and /gp/product/ on the
                    // supported TLDs) become cards. Any other Amazon link —
                    // search/category pages, or TLDs the card doesn't cover
                    // (.com.br, .com.mx, .com.au, .cn) — stays a plain link for
                    // AlterAmazonLinks to tag.
                    Formatter\AmazonProductCard::register($configurator);

                    $params = $configurator->rendering->parameters;
                    $params['AMAZON_ASSOCIATE_TAG'] = $settings->get($prefix.'com', '');
                    $params['AMAZON_ASSOCIATE_TAG_UK'] = $settings->get($prefix.'co.uk', '');
                    $params['AMAZON_ASSOCIATE_TAG_DE'] = $settings->get($prefix.'de', '');
                    $params['AMAZON_ASSOCIATE_TAG_FR'] = $settings->get($prefix.'fr', '');
                    $params['AMAZON_ASSOCIATE_TAG_JP'] = $settings->get($prefix.'co.jp', '');
                    $params['AMAZON_ASSOCIATE_TAG_CA'] = $settings->get($prefix.'ca', '');
                    $params['AMAZON_ASSOCIATE_TAG_IT'] = $settings->get($prefix.'it', '');
                    $params['AMAZON_ASSOCIATE_TAG_ES'] = $settings->get($prefix.'es', '');
                    $params['AMAZON_ASSOCIATE_TAG_IN'] = $settings->get($prefix.'in', '');
                    // The card builds plain tagged links rather than the old
                    // region-specific iframe widget, so these marketplaces — which
                    // the upstream MediaEmbed site never supported — work too.
                    $params['AMAZON_ASSOCIATE_TAG_CN'] = $settings->get($prefix.'cn', '');
                    $params['AMAZON_ASSOCIATE_TAG_BR'] = $settings->get($prefix.'com.br', '');
                    $params['AMAZON_ASSOCIATE_TAG_MX'] = $settings->get($prefix.'com.mx', '');
                    $params['AMAZON_ASSOCIATE_TAG_AU'] = $settings->get($prefix.'com.au', '');
                }),
        ]),
];
