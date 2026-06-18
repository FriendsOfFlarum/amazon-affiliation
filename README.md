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
- **Works with rich embeds** — when used alongside [fof/formatting](https://github.com/FriendsOfFlarum/formatting) with MediaEmbed enabled, the affiliate tag is also injected into the Amazon product embed (see below).

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

## Rich embeds (fof/formatting + MediaEmbed)

If you also run [fof/formatting](https://github.com/FriendsOfFlarum/formatting) with its **MediaEmbed** plugin enabled, Amazon product URLs are turned into rich embeds rather than plain links. This extension injects your affiliate tag into those embeds as well, so embedded products are still attributed to your account.

A couple of things to be aware of, both upstream limitations of [s9e\TextFormatter](https://github.com/s9e/TextFormatter)'s MediaEmbed:

- **Embeds always strip any existing tag** and use yours — the "keep existing tags" setting only applies to plain links.
- **Some marketplaces are not supported as embeds:** CN, BR, MX and AU. Links to those always render as plain links and follow the settings above instead.

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/12389)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/amazon-affiliation)
- [Report an issue](https://github.com/FriendsOfFlarum/amazon-affiliation/issues)
- [Download via Packagist](https://packagist.org/packages/fof/amazon-affiliation)
