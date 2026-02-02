=== Link Diagnostics – Broken Links, Redirects, and Link Insights ===
Contributors: swiftspeed
Tags: broken links, link checker, internal links, redirect detection, SEO, 404 errors, link management
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete link health monitoring for WordPress. Find broken links, fix redirect chains, optimize internal linking, and improve SEO performance.

== Description ==

Link Diagnostics is a comprehensive link analysis tool that scans your WordPress site for link issues and provides detailed intelligence about your internal linking structure. If you're serious about SEO and site maintenance, this plugin gives you the visibility you need to keep your links healthy.

= Why Link Health Matters for SEO =

Over time, your site accumulates link problems. You delete old posts, change slugs, restructure content. Automated or manual redirect plugins create chains. External sites you have linked to in the past go offline. The result:

* Redirect chains that waste crawl budget and slow page speed
* Broken internal links that hurt user experience and rankings
* Orphaned content that search engines can't discover
* Dead external links that signal poor maintenance
* Inefficient internal linking that fails to pass authority to important pages

These issues directly impact your SEO health score in tools like Ahrefs, Semrush, and Screaming Frog. Link Diagnostics helps you fix them systematically.

= Internal Link Health - The Big Win =

This is where the plugin delivers maximum value. When you change a URL or delete a post, redirect plugins create a 301 redirect. Fine for visitors, but now you have anchor texts across your site linking to URLs that redirect elsewhere. Every redirect hop wastes crawl budget and dilutes link equity, seo tools detect it and tell you to fix, but then, your site is large, how many do you want to start fixing one after the other? well, that's why this plugin exists.

**What Link Diagnostics does:**

Scans every post and finds anchor texts linking to redirected URLs. You see:

* The post containing the link
* The anchor text used
* The current URL being linked to
* Where that URL redirects to
* The redirect type (301, 302, 307, 308)

Click "Fix" and the plugin updates the anchor's href to point directly to the final destination. No more redirect chains. Your internal links point exactly where they should.

**Why this matters:**

* Preserves crawl budget by eliminating unnecessary redirects
* Passes full link equity directly to target pages
* Improves page speed (fewer redirect hops)
* Boosts SEO health scores dramatically
* Strengthens your internal linking structure

This one feature alone can move your site health score from 60% to 90%+ in SEO tools.

= Other Internal Link Issues Detected =

Beyond redirects, the plugin finds:

* **404 Errors**: Broken internal links where the target page doesn't exist
* **410 Gone**: Links to permanently deleted content
* **500 Errors**: Server errors on internal URLs
* **Redirect Chains**: Multiple redirect hops before reaching destination

Each issue shows the source post, anchor text, problematic URL, and HTTP status code. Fix them directly from the admin panel.

= External Link Error Detection =

Your outbound links matter too. Link Diagnostics scans external links and reports:

* 404 and 410 errors (broken external links)
* 5xx server errors (unreliable external sites)
* DNS failures and timeouts (unreachable domains)
* Redirect chains on external URLs

You see which posts contain broken external links and can update or remove them to maintain content quality.

= Link Intelligence - Understand Your Link Structure =

This is the strategic part. Beyond finding problems, Link Diagnostics analyzes your entire link structure to reveal:

**Most Linked Internal Pages**

See which pages receive the most internal links. These are your content hubs and authority pages. You can:

* Verify your most important pages are well-linked
* Find pages that are over-linked or under-linked
* Identify pillar content opportunities
* Balance internal link distribution

Each page shows total inbound links and expandable details listing every source post with the exact anchor texts used.

**Common Anchor Texts**

Discover which anchor texts you use most frequently. This helps you:

* Spot over-optimization (too many exact-match anchors)
* Find opportunities for natural language variation
* Maintain consistency across your content
* Avoid anchor text patterns that look spammy

**External Domains Analysis**

See which external sites you link to most often. This reveals:

* Your most frequently cited sources
* Over-reliance on specific domains
* Opportunities to diversify external links
* External relationships worth maintaining

For each domain, see which posts link to it and what anchor texts are used.

**Orphaned Pages**

Find content with zero internal links. These pages are invisible to crawlers and users. Link Diagnostics lists every orphaned page so you can:

* Add internal links from relevant content
* Improve site-wide content discovery
* Boost the authority of isolated pages
* Ensure all content is accessible

= URL Redirect Management =

Built-in redirect manager handles site restructuring and migrations:

* Create 301, 302, 307, and 308 redirects
* Add multiple source URLs to one destination in bulk
* Organize redirects with categories
* Toggle redirects active/inactive without deletion
* Bulk delete operations
* Source URL uniqueness validation prevents conflicts

Perfect for handling old URLs after site migrations or content reorganization.

= How Scanning Works =

Click "Start Scan" on any tab. The plugin:

1. Queries your published content (posts, pages, custom post types)
2. Extracts all links from post content
3. Checks each URL via WordPress HTTP API
4. Stores results in custom database tables
5. Shows progress in real-time

Scans are on-demand only. No background processing, no automated changes. You control when to scan and what to fix.

== Installation ==

1. Upload the `link-diagnostic-and-insights` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Link Diagnostics' in the WordPress admin menu
4. Configure content types to scan in Settings
5. Click 'Start Scan' on any tab to begin

== Frequently Asked Questions ==

= How does fixing work? =

For each issue, you have options:

* Click "Edit" to open the post editor and manually update the link
* Click "Fix" to automatically update that specific link's URL to the correct destination
* Click "Ignore" to permanently hide the issue

All fixes require you to initiate them. Nothing happens automatically.

= How long does a scan take? =

Depends on your site size. A 100-post site typically scans in a few minutes. A 1,000-post site might take 20-30 minutes. The plugin processes efficiently to avoid server timeouts.

= Can I scan only certain post types? =

Yes. In Settings, select which post types to include. Scan only posts and pages, or include custom post types as needed.

= What happens to scan data when I uninstall? =

By default, all data is preserved. If you want automatic cleanup, enable "Delete on Uninstall" in Settings before removing the plugin.

= Does this work on large sites? =

Yes. The plugin is designed for production sites of any size. It processes one URL per request to stay within server limits.

= What's the difference between Internal Links and Link Intelligence? =

**Internal Links** finds problems: redirects, 404s, broken links, server errors.

**Link Intelligence** analyzes patterns: which pages get the most links, common anchor texts, external domain relationships, orphaned content.

Both provide different insights. Problems need fixing. Patterns inform strategy.

= How do redirects work? =

The plugin includes a redirect manager. Add source URL(s) and a destination, choose the redirect type (301, 302, 307, 308), and save. The plugin handles redirects at the template_redirect hook before WordPress loads pages.

Each source URL can only exist once across all redirects to prevent conflicts.

= Will this slow down my site? =

No frontend impact. The plugin only runs in the WordPress admin area. No JavaScript or CSS loaded on the frontend. Scans happen on-demand, not automatically in the background.

== Screenshots ==

1. Internal Link Issues view showing redirects with post details and fix controls
2. External Link Errors displaying broken links with HTTP status codes
3. Link Intelligence Analysis showing most-linked pages with SEO insights
4. External Domains view revealing outbound link patterns
5. Scan History tracking all completed scans with details

== Changelog ==

= 1.0.0 =
* Initial release
* Internal link health monitoring (redirects, 404s, errors)
* External link error detection (404, 410, 5xx)
* Link intelligence analysis (most linked pages, anchor text patterns, external domains)
* Orphan page detection (pages with zero inbound links)
* URL redirect management (301, 302, 307, 308)
* Multiple source URLs per redirect
* Redirect editing and bulk operations
* Fix controls with post editor integration
* Bulk fix operations
* Scan history tracking
* Configurable data retention
* Support for posts, pages, and custom post types

== Credits ==

Developed by [Àgbà Akin](https://akinolaakeem.com)
Managed by [Ssu-Technology Limited](https://swiftspeed.org)
Supported by [Swiftspeed](https://swiftspeed.app)