<?php

/*
 * This file is part of fof/amazon-affiliation.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\AmazonAffiliation\Formatter;

use s9e\TextFormatter\Configurator;

/**
 * Registers the Amazon Product "rich embed", reworked for the current Amazon
 * Associates program.
 *
 * Background:
 *  - s9e/text-formatter shipped a built-in `amazon` MediaEmbed site until
 *    upstream commit b1d809a0c ("MediaEmbed: removed Amazon", 2024-01-05).
 *  - That site rendered an `<iframe>` to //ws-*.assoc-amazon.com/widgets/cm —
 *    Amazon's affiliate *widget* endpoint, which Amazon itself retired on
 *    2024-04-01 (iframe widgets now render blank). So the original embed is
 *    doubly dead: removed upstream, and pointing at a discontinued endpoint.
 *  - There is no keyless replacement that returns live product data: the
 *    Product Advertising API needs credentials plus a rolling sales quota and
 *    was retired 2025-01-31 in favour of the Creators API.
 *
 * What still works today is a plain product link carrying `?tag=<associate>`
 * (the SiteStripe "text link" mechanism). So the reworked embed renders a
 * self-contained, styled product-link card built entirely from the ASIN and
 * TLD captured from the URL — no external iframe, no API call.
 *
 * Backwards compatibility:
 *  - We register through MediaEmbed::add('amazon', ...), exactly as the old
 *    built-in site did. The parser stores embedded Amazon URLs as an `AMAZON`
 *    tag carrying `id` (ASIN) and `tld` attributes (see MediaEmbed\Parser).
 *  - Posts authored under fof/amazon-affiliation 1.x therefore contain `AMAZON`
 *    tags in their stored XML. Because we re-register the same tag with the same
 *    attributes, that existing content resolves to this tag and renders with the
 *    new card template — old data is not orphaned.
 *  - MediaEmbed::add() always builds an <iframe> template, so after registering
 *    the tag (for its URL matching + attribute extraction) we overwrite the
 *    tag's template with the card. This is the same "register then rewrite the
 *    template" pattern fof/formatting uses for YouTube's no-cookie host.
 *
 * The site is registered unconditionally. Only Amazon product URLs (/dp/ and
 * /gp/product/ on the supported TLDs) are turned into cards; every other Amazon
 * link is left untouched here and handled by AlterAmazonLinks instead.
 */
class AmazonProductCard
{
    /**
     * Register the Amazon product card, loading the MediaEmbed plugin first if
     * it is not already present.
     */
    public static function register(Configurator $configurator): void
    {
        if (!isset($configurator->MediaEmbed)) {
            $configurator->plugins->load('MediaEmbed');
        }

        // If the site is already registered (e.g. by a future fof/formatting),
        // still rewrite its template so the card — not a dead iframe — is used.
        if (!isset($configurator->MediaEmbed->getSites()['amazon'])) {
            $configurator->MediaEmbed->add('amazon', static::siteConfig());
        }

        // MediaEmbed::add() forces an <iframe> template; replace it with the
        // product-link card. Attributes (id, tld) are unchanged, so previously
        // stored AMAZON tags keep resolving and now render as a card.
        $configurator->tags['AMAZON']->template = static::template();
    }

    /**
     * Site definition: hosts, ASIN/TLD extraction and the associate-tag
     * parameters. Mirrors the upstream amazon.xml minus the dead iframe.
     *
     * @return array<string, mixed>
     */
    protected static function siteConfig(): array
    {
        return [
            'name'       => 'Amazon Product',
            'homepage'   => 'https://affiliate-program.amazon.com/',
            'attributes' => [
                'id' => ['required' => true],
            ],
            'host' => [
                'amazon.ca',
                'amazon.cn',
                'amazon.co.uk',
                'amazon.co.jp',
                'amazon.com',
                'amazon.com.au',
                'amazon.com.br',
                'amazon.com.mx',
                'amazon.de',
                'amazon.es',
                'amazon.fr',
                'amazon.in',
                'amazon.it',
            ],
            'extract' => [
                "#/(?:dp|gp/product)/(?'id'[A-Z0-9]+)#",
                // Capture the full marketplace suffix after "amazon.". Multi-part
                // suffixes (com.au, co.uk, …) are listed before the bare ones so
                // e.g. amazon.com.au matches `com.au`, not `com`.
                "#amazon\\.(?'tld'com\\.au|com\\.br|com\\.mx|co\\.uk|co\\.jp|ca|cn|com|de|es|fr|in|it)#",
            ],
            // A minimal iframe config so MediaEmbed::add() succeeds; the template
            // it produces is immediately overwritten by template() below.
            'iframe' => [
                'width'  => 120,
                'height' => 240,
                'src'    => '',
            ],
        ];
    }

    /**
     * The card template: a styled anchor to the tagged product URL.
     *
     * Builds `https://www.amazon.<host>/dp/<ASIN>?tag=<associate>`. A single
     * <xsl:choose> maps `@tld` to both the host suffix and the region's associate
     * tag. It accepts both the full suffixes captured by the current extract
     * regex (`com`, `co.uk`, `com.au`, …) AND the short values stored by 1.x
     * (`uk`, `jp`, and empty for .com), so previously stored AMAZON tags still
     * render correctly. The associate tags come from the $AMAZON_ASSOCIATE_TAG*
     * rendering parameters populated in extend.php; we only append `?tag=` when a
     * tag is configured (s9e's parser has no <xsl:variable>, so the host choice,
     * tag choice and emptiness guard are folded into the one choose).
     */
    protected static function template(): string
    {
        return <<<'XSL'
<a data-s9e-mediaembed="amazon" class="AmazonProductCard">
	<!--
		Set rel/target via xsl:attribute rather than as literal attributes: the
		MediaEmbed template normaliser (and other render passes) can blank literal
		target/rel on media-tag anchors, but computed attributes survive. This is
		UGC pointing off-site, so apply SEO best practice: nofollow + ugc so search
		engines don't follow or assign weight, sponsored for the affiliate
		relationship, and noopener/noreferrer for new-tab safety.
	-->
	<xsl:attribute name="rel">nofollow ugc sponsored noopener noreferrer</xsl:attribute>
	<xsl:attribute name="target">_blank</xsl:attribute>
	<xsl:attribute name="href">
		<!-- Host: normalise the few 1.x short aliases (uk, jp) and empty (.com); -->
		<!-- every other @tld value already equals its host suffix.             -->
		<xsl:text>https://www.amazon.</xsl:text>
		<xsl:choose>
			<xsl:when test="@tld='uk'">co.uk</xsl:when>
			<xsl:when test="@tld='jp'">co.jp</xsl:when>
			<xsl:when test="@tld!=''"><xsl:value-of select="@tld"/></xsl:when>
			<xsl:otherwise>com</xsl:otherwise>
		</xsl:choose>
		<xsl:value-of select="concat('/dp/', @id)"/>
		<!-- Append ?tag=<associate> for the region, only when one is configured. -->
		<!-- Branches accept both 1.x short values and current full suffixes.     -->
		<xsl:choose>
			<xsl:when test="@tld='ca'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_CA != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_CA)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='cn'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_CN != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_CN)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='com.au'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_AU != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_AU)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='com.br'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_BR != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_BR)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='com.mx'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_MX != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_MX)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='de'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_DE != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_DE)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='es'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_ES != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_ES)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='fr'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_FR != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_FR)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='in'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_IN != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_IN)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='it'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_IT != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_IT)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='jp' or @tld='co.jp'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_JP != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_JP)"/></xsl:if></xsl:when>
			<xsl:when test="@tld='uk' or @tld='co.uk'"><xsl:if test="$AMAZON_ASSOCIATE_TAG_UK != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG_UK)"/></xsl:if></xsl:when>
			<xsl:otherwise><xsl:if test="$AMAZON_ASSOCIATE_TAG != ''"><xsl:value-of select="concat('?tag=', $AMAZON_ASSOCIATE_TAG)"/></xsl:if></xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
	<span class="AmazonProductCard-icon" aria-hidden="true"/>
	<span class="AmazonProductCard-body">
		<span class="AmazonProductCard-title">
			<xsl:text>View on Amazon</xsl:text>
		</span>
		<span class="AmazonProductCard-asin">
			<xsl:value-of select="@id"/>
		</span>
	</span>
</a>
XSL;
    }
}
