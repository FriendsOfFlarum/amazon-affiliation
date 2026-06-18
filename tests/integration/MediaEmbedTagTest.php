<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation\Tests\integration;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Flarum\User\User;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;

/**
 * End-to-end test for the Amazon "rich embed" path.
 *
 * Separate from the AlterAmazonLinks render callback (which rewrites the href of
 * plain Amazon links), an Amazon *product* URL is turned into a self-contained
 * product-link card by AmazonProductCard. The card's href is built from the
 * `AMAZON_ASSOCIATE_TAG*` rendering parameters this extension populates in
 * extend.php's Formatter->configure() block.
 *
 * Historically this was an s9e MediaEmbed iframe widget; s9e removed the Amazon
 * site and Amazon retired the widget endpoint, so the embed is now a tagged
 * product-link card (see AmazonProductCard). The card registers unconditionally,
 * so fof/formatting's MediaEmbed plugin does not need to be enabled for it.
 */
class MediaEmbedTagTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-amazon-affiliation');

        // Bypass the post-creation rate limit.
        $this->extend(
            (new Extend\ThrottleApi())->remove('postTimeout')
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test', 'slug' => 'test', 'user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Opener.</p></t>', 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function amazon_url_is_rendered_as_a_product_card()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        // The product URL becomes a card, not a plain anchor or an iframe.
        $this->assertStringContainsString('class="AmazonProductCard"', $html, "Amazon URL was not turned into a card:\n".$html);
        $this->assertStringNotContainsString('<iframe', $html);
        // The product ASIN is shown in the card and used to build the href.
        $this->assertStringContainsString('B00004TZY8', $html);
    }

    #[Test]
    public function card_links_to_the_com_tagged_url()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        // The card href is the canonical product URL carrying the .com tag,
        // sourced from the AMAZON_ASSOCIATE_TAG rendering parameter.
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html, "Affiliate tag missing from card:\n".$html);
    }

    #[Test]
    public function card_links_to_the_uk_tagged_url()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.co.uk', 'uktag');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.co.uk/dp/B00004TZY8');

        // .co.uk maps to the AMAZON_ASSOCIATE_TAG_UK parameter and the co.uk host.
        $this->assertStringContainsString('href="https://www.amazon.co.uk/dp/B00004TZY8?tag=uktag"', $html, "UK affiliate tag missing from card:\n".$html);
    }

    #[Test]
    public function card_links_to_the_au_tagged_url()
    {
        // .com.au was never an embed in the old MediaEmbed site; the card builds
        // a plain tagged link, so newer marketplaces work too.
        $this->setting('fof-amazon-affiliation.affiliate-tag.com.au', 'autag');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com.au/dp/B00004TZY8');

        $this->assertStringContainsString('href="https://www.amazon.com.au/dp/B00004TZY8?tag=autag"', $html, "AU affiliate tag missing from card:\n".$html);
    }

    #[Test]
    public function card_without_configured_tag_omits_the_tag_param()
    {
        // No affiliate tags configured at all. By design the card still renders
        // (the rich-card setting defaults on), but the href has no dangling
        // `?tag=` — the tag is only appended when one is configured.
        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        $this->assertStringContainsString('class="AmazonProductCard"', $html, "Amazon URL was not turned into a card:\n".$html);
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8"', $html);
        $this->assertStringNotContainsString('?tag=', $html);
    }

    #[Test]
    public function legacy_stored_amazon_tag_renders_as_a_card()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        // Content authored under 1.x was stored as a parsed `AMAZON` MediaEmbed
        // tag (id = ASIN, tld attribute), NOT as the URL text. Feed that stored
        // XML straight to the renderer to prove old data is not orphaned: the
        // re-registered `amazon` tag renders it with the new card template.
        $xml = '<r><AMAZON id="B00004TZY8">https://www.amazon.com/dp/B00004TZY8</AMAZON></r>';

        $html = $this->app()->getContainer()->make('flarum.formatter')->render($xml);

        $this->assertStringContainsString('class="AmazonProductCard"', $html, "Legacy AMAZON tag did not render as a card:\n".$html);
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    // NOTE: the `rich-card = false` branch (product URLs staying plain links) is
    // gated by Extend\Conditional()->whenSetting() in extend.php, which the core
    // evaluates once per boot. The integration harness injects test settings via
    // a local-site extender that runs *after* extension extenders, so a setting
    // cannot be made false early enough for the extension's Conditional to see it
    // within a single boot. The registration logic itself is covered directly by
    // the AmazonProductCard unit test instead.

    #[Test]
    public function legacy_short_tld_attribute_renders_as_a_card()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.co.uk', 'uktag');

        // 1.x's extract regex stored the .co.uk marketplace as tld="uk" (not
        // "co.uk"). The card template must still map that legacy short value to
        // the co.uk host and the UK tag.
        $xml = '<r><AMAZON id="B00EO4NN5C" tld="uk">https://www.amazon.co.uk/dp/B00EO4NN5C</AMAZON></r>';

        $html = $this->app()->getContainer()->make('flarum.formatter')->render($xml);

        $this->assertStringContainsString('class="AmazonProductCard"', $html);
        $this->assertStringContainsString('href="https://www.amazon.co.uk/dp/B00EO4NN5C?tag=uktag"', $html, "Legacy tld=\"uk\" did not map to the co.uk card:\n".$html);
    }

    /**
     * POST a reply to discussion 1 and return its rendered `contentHtml`.
     */
    private function postReplyAndGetContentHtml(string $content): string
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'attributes'    => ['content' => $content],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), 'Creating the reply failed: '.$response->getBody());

        $body = json_decode((string) $response->getBody(), true);

        $html = $body['data']['attributes']['contentHtml'] ?? null;
        $this->assertIsString($html, 'Response is missing contentHtml.');

        return $html;
    }
}
