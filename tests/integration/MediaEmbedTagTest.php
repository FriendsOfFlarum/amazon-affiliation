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
 * End-to-end test for the s9e MediaEmbed path.
 *
 * This is the *other* way Amazon links get their affiliate tag, separate from
 * the AlterAmazonLinks render callback: when fof/formatting's MediaEmbed plugin
 * is enabled, an Amazon product URL becomes an embedded iframe rather than a
 * plain link. That iframe's `src` is built from the `AMAZON_ASSOCIATE_TAG*`
 * rendering parameters, which this extension populates from its settings in
 * extend.php's Formatter->configure() block.
 *
 * This test confirms the two extensions cooperate: with MediaEmbed enabled and
 * an affiliate tag configured here, the rendered embed carries the tag.
 */
class MediaEmbedTagTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Both extensions must be enabled: fof/formatting provides the
        // MediaEmbed sites (incl. Amazon); this extension supplies the tags.
        $this->extension('fof-formatting', 'fof-amazon-affiliation');

        // Turn on the MediaEmbed plugin in fof/formatting.
        $this->setting('fof-formatting.plugin.mediaembed', true);

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
    public function amazon_url_is_embedded_as_iframe()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        // With MediaEmbed enabled the URL becomes an embed, not a plain anchor.
        $this->assertStringContainsString('<iframe', $html, "Amazon URL was not embedded:\n".$html);
        // The product ASIN ends up in the embed src.
        $this->assertStringContainsString('B00004TZY8', $html);
    }

    #[Test]
    public function embed_carries_the_com_affiliate_tag()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        // The Amazon MediaEmbed iframe src contains `&t=<tag>` for the .com TLD,
        // sourced from the AMAZON_ASSOCIATE_TAG rendering parameter.
        $this->assertStringContainsString('t=abcdef', $html, "Affiliate tag missing from embed:\n".$html);
    }

    #[Test]
    public function embed_carries_the_uk_affiliate_tag()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.co.uk', 'uktag');

        $html = $this->postReplyAndGetContentHtml('https://www.amazon.co.uk/dp/B00004TZY8');

        // .co.uk maps to the AMAZON_ASSOCIATE_TAG_UK parameter.
        $this->assertStringContainsString('t=uktag', $html, "UK affiliate tag missing from embed:\n".$html);
    }

    #[Test]
    public function embed_without_configured_tag_renders_empty_tag()
    {
        // No tag configured for .com. The embed still renders; the tag param is
        // just empty. This documents that MediaEmbed always embeds regardless.
        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        $this->assertStringContainsString('<iframe', $html, "Amazon URL was not embedded:\n".$html);
        $this->assertStringContainsString('B00004TZY8', $html);
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
