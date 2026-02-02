# Link Diagnostics – Broken Links, Redirects, and Link Insights

**Version:** 1.0.0  
**Requires:** WordPress 5.6+, PHP 7.4+  
**License:** GPL v2 or later

Complete link health monitoring for WordPress. Find broken links, fix redirect chains, optimize internal linking, and improve SEO performance.

## What This Plugin Does

Link Diagnostics scans your WordPress site for link issues and provides detailed intelligence about your internal linking structure. If you manage a content site and care about SEO, this plugin gives you the visibility you need to maintain healthy links.

## Why Link Health Matters for SEO

Over time, your site accumulates link problems. You delete old posts, change slugs, restructure content. Automated or manual redirect plugins create chains. External sites you have linked to in the past go offline. The result:

* Redirect chains that waste crawl budget and slow page speed
* Broken internal links that hurt rankings and user experience
* Orphaned content search engines can't discover
* Dead external links signaling poor maintenance
* Inefficient internal linking failing to pass authority to important pages

These issues directly impact your SEO health score in tools like Ahrefs, Semrush, and Screaming Frog. Link Diagnostics helps you fix them systematically.

## Internal Link Health - The Core Feature

This is where the plugin delivers maximum value. When you change a URL or delete a post, redirect plugins create a 301 redirect. Fine for visitors, but now you have anchor texts across your site linking to URLs that redirect elsewhere. Every redirect hop wastes crawl budget and dilutes link equity, seo tools detect it and tell you to fix, but then, your site is large, how many do you want to start fixing one after the other? well, that's why this plugin exists.

### What the Plugin Does

Scans every post, pages, custom pages, or all of these depending on your selection and finds anchor texts linking to redirected URLs. For each one, you see:

* The post containing the link
* The anchor text used
* The current URL being linked to
* Where that URL redirects to
* The redirect type (301, 302, 307, 308)

Click "Fix" and the plugin updates the anchor's href to point directly to the final destination. No more redirect chains. Your internal links point exactly where they should.

### Why This Matters

* **Preserves crawl budget** by eliminating unnecessary redirects
* **Passes full link equity** directly to target pages
* **Improves page speed** (fewer redirect hops)
* **Boosts SEO health scores** dramatically
* **Strengthens internal linking structure**

This one feature alone can move your site internal health score from 50% to 90%+ in SEO audit tools.

### Other Internal Link Issues Detected

Beyond redirects:

* **404 Errors**: Broken internal links where the target page doesn't exist
* **410 Gone**: Links to permanently deleted content
* **500 Errors**: Server errors on internal URLs
* **Redirect Chains**: Multiple redirect hops before reaching destination

Each issue shows the source post, anchor text, problematic URL, and HTTP status code. Fix them directly from the admin panel.

## External Link Error Detection

Your outbound links matter too. The plugin scans external links and reports:

* 404 and 410 errors (broken external links)
* 5xx server errors (unreliable external sites)
* DNS failures and timeouts (unreachable domains)
* Redirect chains on external URLs

See which posts contain broken external links and update or remove them to maintain content quality.

## Link Intelligence - Strategic Analysis

Beyond finding problems, Link Diagnostics analyzes your entire link structure to reveal patterns and SEO opportunities.

### Most Linked Internal Pages

Discover which pages receive the most internal links. These are your content hubs and authority pages. You can:

* Verify your most important pages are well-linked
* Find pages that are over-linked or under-linked
* Identify pillar content opportunities
* Balance internal link distribution

Each page shows total inbound links and expandable details listing every source post with the exact anchor texts used.

### Common Anchor Texts

See which anchor texts you use most frequently across your site. This helps you:

* Spot over-optimization (too many exact-match anchors)
* Find opportunities for natural language variation
* Maintain consistency across content
* Avoid anchor text patterns that look spammy

### External Domains Analysis

Discover which external sites you link to most often. This reveals:

* Your most frequently cited sources
* Over-reliance on specific domains
* Opportunities to diversify external links
* External relationships worth maintaining

For each domain, see which posts link to it and what anchor texts are used.

### Orphaned Pages

Find content with zero internal links. These pages are invisible to crawlers and users. The plugin lists every orphaned page so you can:

* Add internal links from relevant content
* Improve site-wide content discovery
* Boost the authority of isolated pages
* Ensure all content is accessible

This is crucial for SEO - pages without internal links rarely rank well.

## URL Redirect Management

Built-in redirect manager for site restructuring and migrations:

* Create 301, 302, 307, and 308 redirects
* Add multiple source URLs to one destination in bulk
* Organize redirects with categories
* Toggle redirects active/inactive without deletion
* Bulk delete operations
* Source URL uniqueness validation prevents conflicts

Perfect for handling old URLs after site migrations or content reorganization.

## How Scanning Works

Click "Start Scan" on any tab. The plugin:

1. Queries your published content (posts, pages, custom post types)
2. Extracts all links from post content
3. Checks each URL via WordPress HTTP API
4. Stores results in custom database tables
5. Shows progress in real-time

Scans are on-demand only. No background processing. You control when to scan and what to fix.

### Performance

The plugin processes one URL per AJAX request to prevent server timeouts. A 100-post site scans in minutes. A 1,000-post site might take 20-30 minutes. Designed to work reliably on sites of any size.

## Technical Details

### Database Structure

Custom tables for optimal performance:

* `wp_lhcfwp_settings` - Plugin configuration
* `wp_lhcfwp_scans` - Scan history
* `wp_lhcfwp_issues` - Detected problems
* `wp_lhcfwp_ignored` - Ignored issues
* `wp_lhcfwp_fixes` - Fix history
* `wp_lhcfwp_intelligence` - Link analysis data
* `wp_lhcfwp_redirects` - URL redirects

### Security

* Nonce verification on all AJAX requests
* Capability checks on admin actions
* Prepared statements for database queries
* Escaped output for rendered data
* WordPress coding standards compliant

### Data Handling

**Storage:** All data stays in your WordPress database  
**Privacy:** No external services, no tracking, no cookies  
**Retention:** Configurable - keep or delete on uninstall  
**Requests:** Only validates URLs found in your content

### Frontend Impact

Zero. The plugin runs entirely in the WordPress admin area. No JavaScript or CSS loaded on the frontend.

## Installation

1. Upload `link-diagnostic-and-insights` folder to `/wp-content/plugins/`
2. Activate through WordPress Plugins menu
3. Navigate to 'Link Diagnostics' in admin menu
4. Configure content types to scan in Settings
5. Click 'Start Scan' on any tab

## Configuration

### First Scan

1. Go to 'Internal Links' tab
2. Select content type (posts, pages, or custom post types)
3. Click 'Start Scan'
4. Monitor progress in real-time
5. Review results when complete

### Settings

* **Content Types**: Select which post types to scan
* **Data Retention**: Choose to preserve or delete data on uninstall

## Use Cases

### Pre-Migration Audit

Run comprehensive scans before site redesigns to identify and fix all link issues upfront. Prevents broken links from carrying over to the new site.

### Regular Maintenance

Schedule monthly scans to catch broken links and maintain site quality. Many sites run scans after bulk content updates or deletions.

### SEO Optimization

Use intelligence insights to strengthen internal linking and improve topical authority. Find orphaned pages, balance link distribution, optimize anchor text usage.

### Content Strategy

Identify content hubs (most-linked pages), find gaps in internal linking, discover which external sources you reference most.

### Redirect Management

Handle site restructuring professionally with bulk redirect tools. Update internal links to bypass redirects and improve performance.

## Screenshots

### Internal Link Issues
![Internal Link Issues](assets/screenshot-1.png)

The Internal Links view displays detected redirects and errors in a sortable table. Each row shows the source post, anchor text, current URL, destination (for redirects), HTTP status code, and action buttons.

### External Link Errors
![External Link Errors](assets/screenshot-2.png)

External Errors view lists broken outbound links with source post information, anchor text, broken URL, and HTTP status code.

### Link Intelligence Analysis
![Link Intelligence Analysis](assets/screenshot-3.png)

The Intelligence view shows most-linked internal pages ranked by inbound link count, with expandable details showing source posts and anchor texts used.

### External Domains
![External Domains](assets/screenshot-4.png)

External Domains intelligence reveals which external websites receive the most outbound links from your content, with details on source posts and anchor texts.

### Scan History
![Scan History](assets/screenshot-5.png)

Scan History tracks completed scans, configurations, and results over time.

## Frequently Asked Questions

### How long does a scan take?

Depends on site size. A 100-post site typically scans in a few minutes. A 1,000-post site might take 20-30 minutes. The plugin uses efficient AJAX processing to handle sites of any size safely.

### Can I scan specific content types?

Yes. Configure which post types to include in Settings. Scan only posts and pages or include custom post types as needed.

### How do redirects work?

Create redirects by adding source URL(s) and a destination. The plugin processes redirects at the template_redirect hook before WordPress loads pages with proper HTTP status codes. Each source URL can only exist once to prevent conflicts.

### What about data retention?

You control it. By default, scan data is preserved. Enable 'Delete on Uninstall' in Settings for automatic cleanup.

### Does it work on large sites?

Yes. Designed for production environments of any size with efficient one-URL-per-request processing.

### What's the difference between Internal Links and Link Intelligence?

**Internal Links** finds problems: redirects, 404s, broken links, server errors.

**Link Intelligence** analyzes patterns: which pages get the most links, common anchor texts, external domain relationships, orphaned content.

Problems need fixing. Patterns inform strategy.

## Technical Requirements

* WordPress 5.6 or higher
* PHP 7.4 or higher
* MySQL 5.6+ / MariaDB 10.1+
* Administrator capability for access

## Credits

**Developed by:** [Àgbà Akin](https://akinolaakeem.com)  
**Managed by:** [Ssu-Technology Limited](https://swiftspeed.org)  
**Supported by:** [Swiftspeed](https://swiftspeed.app)

## License

GPL v2 or later. See LICENSE file for details.