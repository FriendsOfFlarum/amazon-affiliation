<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation\Tests\unit;

use Flarum\Testing\unit\TestCase;
use FoF\AmazonAffiliation\Formatter\AmazonProductCard;
use PHPUnit\Framework\Attributes\DataProvider;
use s9e\TextFormatter\Configurator;

/**
 * Unit coverage for the Amazon product-card site registration.
 *
 * In production the gating between "card" and "plain link" is done by
 * Extend\Conditional()->whenSetting('fof-amazon-affiliation.rich-card', true, …)
 * in extend.php — when the setting is on, register() is called; when off, it is
 * not. The conditional itself is core behaviour, so here we test the two states
 * of the configurator directly: registered vs not.
 */
class AmazonProductCardTest extends TestCase
{
    protected function configurator(): Configurator
    {
        $configurator = new Configurator();
        // Parameters the card template reads; mirror extend.php's defaults.
        foreach (['', '_UK', '_DE', '_FR', '_JP', '_CA', '_IT', '_ES', '_IN', '_CN', '_BR', '_MX', '_AU'] as $suffix) {
            $configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG'.$suffix] = '';
        }

        return $configurator;
    }

    public function test_register_adds_the_amazon_site()
    {
        $configurator = $this->configurator();

        $this->assertArrayNotHasKey('amazon', $configurator->MediaEmbed->getSites() ?? []);

        AmazonProductCard::register($configurator);

        $this->assertArrayHasKey('amazon', $configurator->MediaEmbed->getSites());
        $this->assertTrue(isset($configurator->tags['AMAZON']));
    }

    public function test_card_template_replaces_the_iframe()
    {
        $configurator = $this->configurator();

        AmazonProductCard::register($configurator);

        $template = (string) $configurator->tags['AMAZON']->template;

        // The dead iframe widget is gone; the tag renders an anchor card.
        $this->assertStringNotContainsString('<iframe', $template);
        $this->assertStringContainsString('AmazonProductCard', $template);
        $this->assertStringContainsString('amazon.', $template);

        // New tab + UGC/affiliate rel, set via xsl:attribute so render passes
        // that blank literal target/rel on media anchors can't strip them.
        $this->assertStringContainsString('<xsl:attribute name="target">_blank</xsl:attribute>', $template);
        $this->assertStringContainsString('<xsl:attribute name="rel">nofollow ugc sponsored noopener noreferrer</xsl:attribute>', $template);
    }

    public function test_a_fresh_configurator_has_no_amazon_site()
    {
        // Without register() — i.e. the rich-card setting being off — there is no
        // amazon media site, so product URLs are left to the link path.
        $configurator = $this->configurator();
        $configurator->plugins->load('MediaEmbed');

        $this->assertArrayNotHasKey('amazon', $configurator->MediaEmbed->getSites());
    }

    public function test_register_is_idempotent()
    {
        $configurator = $this->configurator();

        AmazonProductCard::register($configurator);
        AmazonProductCard::register($configurator);

        $this->assertArrayHasKey('amazon', $configurator->MediaEmbed->getSites());
    }

    /**
     * The card on/off gating in extend.php uses
     * Extend\Conditional()->whenSetting('fof-amazon-affiliation.rich-card', true, …).
     * The integration harness can't set this setting early enough for an
     * extension-level Conditional to observe (its local-site setting injection
     * runs after extension boot), so the two states are verified here against the
     * real Conditional extender: the gated extenders factory must run only when
     * the setting is enabled.
     */
    #[DataProvider('richCardStates')]
    public function test_conditional_applies_card_extenders_only_when_setting_enabled(bool $richCard, bool $expectApplied)
    {
        $applied = false;

        // A sentinel extender standing in for the gated Formatter; if the
        // condition passes, Conditional resolves the factory and calls extend().
        $sentinel = new class($applied) implements \Flarum\Extend\ExtenderInterface {
            public function __construct(public bool &$applied)
            {
            }

            public function extend(\Illuminate\Contracts\Container\Container $container, ?\Flarum\Extension\Extension $extension = null): void
            {
                $this->applied = true;
            }
        };

        $extender = (new \Flarum\Extend\Conditional())
            ->whenSetting('fof-amazon-affiliation.rich-card', true, fn () => [$sentinel]);

        $settings = new class($richCard) implements \Flarum\Settings\SettingsRepositoryInterface {
            public function __construct(private bool $richCard)
            {
            }

            public function all(): array
            {
                return ['fof-amazon-affiliation.rich-card' => $this->richCard];
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'fof-amazon-affiliation.rich-card' ? $this->richCard : $default;
            }

            public function set(string $key, mixed $value): void
            {
            }

            public function delete(string $keyLike): void
            {
            }
        };

        $container = new \Illuminate\Container\Container();
        $container->instance(\Flarum\Settings\SettingsRepositoryInterface::class, $settings);

        $extender->extend($container);

        $this->assertSame($expectApplied, $applied);
    }

    public static function richCardStates(): array
    {
        return [
            'enabled'  => [true, true],
            'disabled' => [false, false],
        ];
    }
}
