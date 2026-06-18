<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation\Tests;

use Flarum\Testing\unit\TestCase;
use FoF\AmazonAffiliation\AmazonLinkManipulator;
use Laminas\Diactoros\Uri;

class AmazonLinkManipulatorTest extends TestCase
{
    protected function createManipulator()
    {
        $manipulator = new AmazonLinkManipulator();

        $manipulator->affiliateTags = [
            'com' => 'abcdef',
        ];

        return $manipulator;
    }

    protected function createMultiLocaleManipulator()
    {
        $manipulator = new AmazonLinkManipulator();

        $manipulator->affiliateTags = [
            'com'    => 'abcdef',
            'co.uk'  => 'uktag',
            'de'     => 'detag',
            'co.jp'  => 'jptag',
            'com.br' => 'brtag',
            'com.au' => 'autag',
        ];

        return $manipulator;
    }

    public function test_add_tag()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', (string) $uri);
    }

    public function test_add_tag_with_other_domains_and_proto()
    {
        $manipulator = $this->createManipulator();

        $expected = 'https://www.amazon.com/dp/B00004TZY8?tag=abcdef';

        $uri = $manipulator->process(new Uri('http://www.amazon.com/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals($expected, (string) $uri);

        $uri = $manipulator->process(new Uri('http://amazon.com/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals($expected, (string) $uri);

        $uri = $manipulator->process(new Uri('https://amazon.com/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals($expected, (string) $uri);

        $uri = $manipulator->process(new Uri('https://www.amazon.co.uk/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.co.uk/dp/B00004TZY8', (string) $uri);
    }

    public function test_add_tag_with_other_query_params()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com/Mattel-Games-UNO-Card-Game/dp/B00004TZY8?pd_rd_wg=WJpCt&pd_rd_r=670b61ea-72f1-4d11-aa5e-1d7f2f580948&pd_rd_w=wf5ho&ref_=pd_gw_ri&pf_rd_r=TW615HC2HPGYTBD7T649&pf_rd_p=c116cecb-5676-58e0-b306-0894a1d0149e'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/Mattel-Games-UNO-Card-Game/dp/B00004TZY8?pd_rd_wg=WJpCt&pd_rd_r=670b61ea-72f1-4d11-aa5e-1d7f2f580948&pd_rd_w=wf5ho&ref_=pd_gw_ri&pf_rd_r=TW615HC2HPGYTBD7T649&pf_rd_p=c116cecb-5676-58e0-b306-0894a1d0149e&tag=abcdef', (string) $uri);
    }

    public function test_replace_existing_tag()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8?tag=other'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', (string) $uri);
    }

    public function test_keep_existing_tag()
    {
        $manipulator = $this->createManipulator();

        $manipulator->keepExistingTag = true;

        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8?tag=other'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=other', (string) $uri);
    }

    public function test_unhandled_doesnt_get_tag()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.fr/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.fr/dp/B00004TZY8', (string) $uri);
    }

    public function test_unhandled_tag_kept()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.fr/dp/B00004TZY8?tag=other'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.fr/dp/B00004TZY8?tag=other', (string) $uri);
    }

    public function test_unhandled_tag_removed()
    {
        $manipulator = $this->createManipulator();

        $manipulator->removeTagIfUnhandled = true;

        $uri = $manipulator->process(new Uri('https://www.amazon.fr/dp/B00004TZY8?tag=other'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.fr/dp/B00004TZY8', (string) $uri);
    }

    public function test_does_not_touch_non_amazon_urls()
    {
        $manipulator = $this->createManipulator();

        $this->assertNull($manipulator->process(new Uri('https://example.com/test')));
        $this->assertNull($manipulator->process(new Uri('https://www.example.fr/test')));
        $this->assertNull($manipulator->process(new Uri('https://amazon.example.com/test')));
    }

    public function test_keep_existing_tag_still_adds_when_none_present()
    {
        $manipulator = $this->createManipulator();

        $manipulator->keepExistingTag = true;

        // No tag present yet, so even with keepExistingTag the configured tag is added.
        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', (string) $uri);
    }

    public function test_remove_tag_if_unhandled_does_not_affect_handled_domain()
    {
        $manipulator = $this->createManipulator();

        $manipulator->removeTagIfUnhandled = true;

        // Domain IS handled, so the configured tag replaces the existing one rather than being removed.
        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8?tag=other'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', (string) $uri);
    }

    public function test_remove_tag_if_unhandled_with_no_existing_tag_only_normalizes()
    {
        $manipulator = $this->createManipulator();

        $manipulator->removeTagIfUnhandled = true;

        // Unhandled domain, no tag to remove: URL is just normalized (https + www).
        $uri = $manipulator->process(new Uri('http://amazon.fr/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.fr/dp/B00004TZY8', (string) $uri);
    }

    public function test_normalizes_unhandled_domain_without_tag()
    {
        $manipulator = $this->createManipulator();

        // Unhandled domain with no special flags: still normalized to https + www.
        $uri = $manipulator->process(new Uri('http://amazon.fr/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.fr/dp/B00004TZY8', (string) $uri);
    }

    public function test_adds_tag_for_two_letter_tld()
    {
        $manipulator = $this->createMultiLocaleManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.de/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.de/dp/B00004TZY8?tag=detag', (string) $uri);
    }

    public function test_adds_tag_for_co_uk_tld()
    {
        $manipulator = $this->createMultiLocaleManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.co.uk/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.co.uk/dp/B00004TZY8?tag=uktag', (string) $uri);
    }

    public function test_adds_tag_for_co_jp_tld()
    {
        $manipulator = $this->createMultiLocaleManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.co.jp/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.co.jp/dp/B00004TZY8?tag=jptag', (string) $uri);
    }

    public function test_adds_tag_for_com_br_tld()
    {
        $manipulator = $this->createMultiLocaleManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com.br/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com.br/dp/B00004TZY8?tag=brtag', (string) $uri);
    }

    public function test_adds_tag_for_com_au_tld()
    {
        $manipulator = $this->createMultiLocaleManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com.au/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com.au/dp/B00004TZY8?tag=autag', (string) $uri);
    }

    public function test_does_not_touch_amazon_subdomains()
    {
        $manipulator = $this->createManipulator();

        // Only a bare host or a leading "www." is matched; other subdomains are ignored.
        $this->assertNull($manipulator->process(new Uri('https://smile.amazon.com/dp/B00004TZY8')));
        $this->assertNull($manipulator->process(new Uri('https://music.amazon.co.uk/dp/B00004TZY8')));
    }

    public function test_does_not_touch_shortened_share_links()
    {
        $manipulator = $this->createManipulator();

        // amzn.to / a.co are redirect services, not marketplace domains. They
        // can't be rewritten without following the redirect, so they are left
        // untouched. See the limitation noted in the README.
        $this->assertNull($manipulator->process(new Uri('https://amzn.to/3abcDEF')));
        $this->assertNull($manipulator->process(new Uri('https://www.amzn.to/3abcDEF')));
        $this->assertNull($manipulator->process(new Uri('https://a.co/d/abcDEF')));
    }

    public function test_preserves_path_and_fragment()
    {
        $manipulator = $this->createManipulator();

        $uri = $manipulator->process(new Uri('https://www.amazon.com/Some-Product-Name/dp/B00004TZY8#reviews'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/Some-Product-Name/dp/B00004TZY8?tag=abcdef#reviews', (string) $uri);
    }

    public function test_url_encodes_query_values()
    {
        $manipulator = $this->createManipulator();

        // http_build_query rebuilds and url-encodes the query. A value with a space comes back encoded.
        $uri = $manipulator->process(new Uri('https://www.amazon.com/dp/B00004TZY8?keywords=board%20game'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?keywords=board+game&tag=abcdef', (string) $uri);
    }

    public function test_normalizes_uppercase_in_host()
    {
        $manipulator = $this->createManipulator();

        // Hosts are case-insensitive; Uri lowercases the host before it reaches the regex.
        $uri = $manipulator->process(new Uri('https://WWW.AMAZON.COM/dp/B00004TZY8'));

        $this->assertNotNull($uri);
        $this->assertEquals('https://www.amazon.com/dp/B00004TZY8?tag=abcdef', (string) $uri);
    }
}
