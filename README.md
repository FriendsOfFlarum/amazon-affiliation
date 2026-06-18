# Amazon Affiliation by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/amazon-affiliation.svg)](https://packagist.org/packages/fof/amazon-affiliation) [![Total Downloads](https://img.shields.io/packagist/dt/fof/amazon-affiliation.svg)](https://packagist.org/packages/fof/amazon-affiliation) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

A [Flarum](https://flarum.org) extension that automatically adds your [Amazon Associates](https://affiliate-program.amazon.com/) affiliate tag to Amazon links posted on your forum, so eligible purchases earn you commission — no need to ask your users to format links a particular way.

## How it works

Whenever a post is rendered, every Amazon link in it is rewritten on the fly:

- The link is normalised to `https://` with the `www.` subdomain.
- Your configured affiliate tag for that marketplace is appended as the `tag` query parameter (or replaces an existing one, depending on your settings).
- Links to Amazon marketplaces you haven't configured are left alone (or have their tag stripped, if you enable that).

Rewriting happens at **render time**, so changing your tag updates every existing post automatically — nothing is baked into the stored content.

A separate tag is configured per marketplace (`.com`, `.co.uk`, `.de`, `.fr`, …), because Amazon Associates accounts are region-specific. Leave a marketplace blank to skip it.

> **Note:** Shortened Amazon share links (`amzn.to`, `a.co`) are **not** rewritten. These point to a redirect service rather than a marketplace domain, so the real product URL — and therefore the marketplace and tag — can't be determined without following the redirect. Post the full `amazon.<tld>` URL to have your tag applied.

## Features

- **Per-marketplace tags** — configure a distinct affiliate tag for each Amazon region.
- **Keep or replace existing tags** — by default an existing `tag` in a link is replaced with yours; optionally keep the original instead.
- **Strip tags on uncovered marketplaces** — optionally remove affiliate tags from links to marketplaces you haven't configured, so no one else's tag survives.
- **URL normalisation** — `http` → `https` and bare hosts gain the `www.` subdomain.
- **Product cards** — Amazon product URLs become a compact card linking to the tagged product page (see below).

## Installation

```sh
composer require fof/amazon-affiliation:"*"
```

Then enable **FoF Amazon Affiliation** in your admin panel and configure your affiliate tags.

## Updating

```sh
composer update fof/amazon-affiliation
php flarum cache:clear
```

## Configuration

All settings live on the extension's page in the admin panel.

| Setting | Description |
| --- | --- |
| **Affiliate Tags** | One field per Amazon marketplace (`amazon.com`, `amazon.co.uk`, …). Enter the affiliate tag for each region you want covered. Leave blank to ignore that marketplace. |
| **Keep existing tags** | When enabled, a link that already carries a `tag` keeps it; otherwise your configured tag replaces it. |
| **Remove tags on uncovered links** | When enabled, links to marketplaces you haven't configured have any existing `tag` stripped. By default such links are left untouched. |

## Product cards

Amazon **product** URLs (`/dp/…` and `/gp/product/…`) are turned into a compact, self-contained product-link card instead of a plain link. The card links straight to the canonical product URL carrying your affiliate tag, so the click is attributed to your account.

This replaces the old MediaEmbed iframe embed. [s9e\TextFormatter](https://github.com/s9e/TextFormatter) removed its built-in Amazon site, and the Amazon affiliate *iframe widget* it relied on was [retired by Amazon in 2022](https://affiliate-program.amazon.com/help/node/topic/GJV3KJAYNY5BQYPH) (it renders blank). A tagged product link is the mechanism Amazon still supports, so the card is built from that. Posts authored under older versions of this extension — whose embeds were stored as MediaEmbed tags — automatically render as cards too.

A couple of things to be aware of:

- **Cards always use your configured tag** for the marketplace — the "keep existing tags" setting only applies to plain (non-product) links.
- **All marketplaces are supported**, including CN, BR, MX and AU. (The old iframe embed couldn't handle those; the card builds a plain tagged link, so they work too.)
- **Prefer plain links?** Turn off **Display Amazon product links as a rich product card** in the settings and product URLs stay plain links, tagged like any other Amazon link.

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/12389)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/amazon-affiliation)
- [Report an issue](https://github.com/FriendsOfFlarum/amazon-affiliation/issues)
- [Download via Packagist](https://packagist.org/packages/fof/amazon-affiliation)
