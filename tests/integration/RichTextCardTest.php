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
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression guard for the fof/rich-text interaction.
 *
 * fof/rich-text (the TipTap editor) auto-links pasted URLs and its markdown
 * serializer emits them as CommonMark autolinks `<url>`. s9e/text-formatter's
 * Litedown wraps `<url>` in a URL tag, which prevents MediaEmbed (and therefore
 * our `amazon` card site) from claiming the URL — so links authored that way
 * render as plain links rather than cards. This is an upstream fof/rich-text bug
 * (FriendsOfFlarum/rich-text#5, "Rich Text Editor suppresses Formatting's
 * MediaEmbed"); it affects every MediaEmbed provider, not just Amazon, and must
 * be fixed in the editor's serializer.
 *
 * These tests pin the boundary so a future change here can't silently regress
 * the part that IS ours: with the full markdown + rich-text stack enabled, a
 * bare Amazon product URL must still render as a card. The autolink/labelled
 * cases are asserted as the documented (upstream) limitation, not desired
 * behaviour — if rich-text#5 is fixed, the autolink assertion can be revisited.
 */
class RichTextCardTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // The realistic production stack for the reported bug.
        $this->extension('flarum-markdown', 'fof-rich-text', 'fof-amazon-affiliation');

        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

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
    public function bare_amazon_url_still_renders_as_a_card_with_rich_text_enabled()
    {
        // A bare URL (the form a correct serializer produces) embeds normally —
        // this is the part that is ours to keep working, regardless of rich-text.
        $html = $this->postReplyAndGetContentHtml('https://www.amazon.com/dp/B00004TZY8');

        $this->assertStringContainsString('class="AmazonProductCard"', $html, "Bare Amazon URL did not render as a card with rich-text enabled:\n".$html);
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html);

        // New tab + UGC rel must survive the full render pipeline (other render
        // passes blank literal target/rel on media anchors; ours are computed).
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertMatchesRegularExpression('/rel="[^"]*\bnofollow\b[^"]*"/', $html, "Card link is missing nofollow:\n".$html);
        $this->assertMatchesRegularExpression('/rel="[^"]*\bugc\b[^"]*"/', $html, "Card link is missing ugc:\n".$html);
        $this->assertMatchesRegularExpression('/rel="[^"]*\bsponsored\b[^"]*"/', $html, "Card link is missing sponsored:\n".$html);
    }

    #[Test]
    public function autolinked_amazon_url_is_a_plain_tagged_link_upstream_limitation()
    {
        // `<url>` is what the TipTap serializer emits for a pasted URL. Litedown
        // wraps it in a URL tag so MediaEmbed can't claim it: no card. The href
        // is still tagged by AlterAmazonLinks, so the link remains monetised.
        // Documented limitation of FriendsOfFlarum/rich-text#5.
        $html = $this->postReplyAndGetContentHtml('<https://www.amazon.com/dp/B00004TZY8>');

        $this->assertStringNotContainsString('AmazonProductCard', $html);
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html);
    }

    #[Test]
    public function labelled_amazon_link_is_left_as_a_tagged_link()
    {
        // An intentional [text](url) link should stay a link (not become a card)
        // even once rich-text#5 is fixed — only the href is tagged.
        $html = $this->postReplyAndGetContentHtml('[the product](https://www.amazon.com/dp/B00004TZY8)');

        $this->assertStringNotContainsString('AmazonProductCard', $html);
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html);
        $this->assertStringContainsString('the product', $html);
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
