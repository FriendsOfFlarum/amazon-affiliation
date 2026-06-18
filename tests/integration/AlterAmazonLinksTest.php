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
 * End-to-end test for the AlterAmazonLinks render callback.
 *
 * Unlike the unit tests on AmazonLinkManipulator, this exercises the full
 * runtime wiring that only exists once the extension is booted: the
 * LinkManipulatorProvider building the manipulator from settings, and the
 * Extend\Formatter->render(AlterAmazonLinks::class) callback rewriting URLs
 * in real post content as it passes through TextFormatter.
 *
 * We render content by POSTing a reply through the API and reading the
 * rendered `contentHtml` from the JSON response — the same pipeline that
 * runs in production.
 */
class AlterAmazonLinksTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-amazon-affiliation');

        // Bypass the post-creation rate limit so several tests can each create
        // a reply within the same window.
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
    public function amazon_link_in_post_gets_affiliate_tag()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('Check out https://www.amazon.com/dp/B00004TZY8 for the game.');

        $this->assertStringContainsString('tag=abcdef', $html);
        $this->assertStringContainsString('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', $html);
    }

    #[Test]
    public function amazon_link_without_www_is_normalized_and_tagged()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        // http + no www in the source; the rendered URL should be normalized.
        $html = $this->postReplyAndGetContentHtml('Link: http://amazon.com/dp/B00004TZY8 here.');

        $this->assertStringContainsString('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', $html);
    }

    #[Test]
    public function existing_tag_is_replaced_by_default()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('See https://www.amazon.com/dp/B00004TZY8?tag=someoneelse now.');

        // Only the href is rewritten; the link text still shows the original URL.
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=abcdef"', $html);
        $this->assertStringNotContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=someoneelse"', $html);
    }

    #[Test]
    public function existing_tag_is_kept_when_setting_enabled()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');
        $this->setting('fof-amazon-affiliation.keep-existing-tag', true);

        $html = $this->postReplyAndGetContentHtml('See https://www.amazon.com/dp/B00004TZY8?tag=someoneelse now.');

        // keepExistingTag leaves the original tag on the href untouched.
        $this->assertStringContainsString('href="https://www.amazon.com/dp/B00004TZY8?tag=someoneelse"', $html);
        $this->assertStringNotContainsString('tag=abcdef', $html);
    }

    #[Test]
    public function unhandled_domain_tag_removed_when_setting_enabled()
    {
        // No tag configured for .fr, so it is "unhandled".
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');
        $this->setting('fof-amazon-affiliation.remove-tag-if-unhandled', true);

        $html = $this->postReplyAndGetContentHtml('See https://www.amazon.fr/dp/B00004TZY8?tag=someoneelse now.');

        // Only the href is rewritten; the link text still shows the original URL.
        $this->assertStringContainsString('href="https://www.amazon.fr/dp/B00004TZY8"', $html);
        $this->assertStringNotContainsString('href="https://www.amazon.fr/dp/B00004TZY8?tag=someoneelse"', $html);
    }

    #[Test]
    public function non_amazon_link_is_left_untouched()
    {
        $this->setting('fof-amazon-affiliation.affiliate-tag.com', 'abcdef');

        $html = $this->postReplyAndGetContentHtml('Unrelated: https://example.com/test here.');

        $this->assertStringContainsString('https://example.com/test', $html);
        $this->assertStringNotContainsString('tag=abcdef', $html);
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
