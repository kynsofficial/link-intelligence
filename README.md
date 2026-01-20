# Link Health

**Version:** 1.0.0  
**Requires:** WordPress 5.6+, PHP 7.4+  
**License:** GPL v2 or later

Production-grade link analysis and intelligence engine for WordPress. Monitor internal and external link health, detect broken links and redirects, and gain editorial link intelligence without automatic content modifications.

## Overview

Link Health is a comprehensive link monitoring and analysis tool built for editorial teams, SEO professionals, and site maintainers who need complete visibility into their WordPress site's link structure. Unlike automated link fixers, this plugin provides detection, analysis, and manual control over all link modifications.

The plugin scans WordPress content to identify three categories of information:

1. **Internal Link Health**: Redirects, broken links, and server errors on internal URLs
2. **External Link Errors**: Broken and unreachable external destinations
3. **Link Intelligence**: Linking patterns, anchor text usage, and domain relationships

All fixes require explicit administrator approval. No content is modified automatically.

## Why Link Health Matters

Links degrade over time. Internal restructuring creates redirect chains. External sites move or shut down. Content evolves but link references remain static. These issues compound into measurable SEO problems:

* Redirect chains increase page load time and dilute link equity
* Broken internal links waste crawl budget and harm user experience
* Broken external links signal poor content maintenance to search engines
* Inefficient internal linking leaves valuable content orphaned
* Over-linking to specific domains may trigger spam filters

Link Health addresses these problems through scheduled auditing and manual correction workflows, not automated replacements that may introduce new problems.

## Internal Link Health

The Internal Links scanner examines all links within your site and reports:

**Redirect Detection**

* HTTP 301 (Moved Permanently)
* HTTP 302 (Found / Temporary Redirect)
* HTTP 307 (Temporary Redirect)
* HTTP 308 (Permanent Redirect)

For each redirect, the plugin shows the current link, the final destination, and the redirect chain status code. This allows editorial teams to update links to point directly to final destinations, eliminating redirect latency.

**Error Detection**

* HTTP 404 (Not Found)
* HTTP 410 (Gone)
* HTTP 500-level server errors

These errors indicate broken internal references that harm user experience and SEO. Each error shows the source post, anchor text, and broken URL for easy correction.

**Issue Details**

For each detected issue, Link Health displays:

* Source post title and type
* Anchor text used in the link
* Current URL being linked
* Destination URL (for redirects)
* HTTP status code
* Issue type classification
* Fix status (Pending, Fixed, Ignored)

Administrators can edit the source post directly, apply a URL fix to update the link in-place, or ignore the issue if it represents a valid editorial decision.

## External Link Errors

The External Errors scanner validates outbound links to external domains:

**Error Types**

* HTTP 404 (Not Found)
* HTTP 410 (Gone)
* HTTP 5xx (Server Errors)
* Unreachable domains (DNS failures, timeouts)

**Detection Strategy**

External URL validation uses WordPress HTTP API with configurable timeouts and transient caching. URLs are checked once per scan and results are cached to reduce external request volume.

**Editorial Response**

For broken external links, editorial teams can:

* Update to a current working URL
* Find an archived version via Wayback Machine
* Remove the link if no replacement exists
* Ignore if the link represents historical reference

The plugin does not automatically replace external URLs because many broken links require editorial judgment about appropriate replacements.

## Link Intelligence Analysis

Beyond error detection, Link Health provides content strategy insights through link pattern analysis:

### Most Linked Internal Pages

Identifies pages receiving the most inbound internal links. This metric reveals:

* Which content your site considers most important
* Potential pillar pages for topic clusters
* Pages that may benefit from strategic de-linking if over-optimized
* Content that receives unexpectedly low internal link attention

Results show inbound link counts and the ability to drill down into source posts and anchor texts used.

### External Domains

Tracks which external domains receive the most outbound links from your content. Use cases include:

* Identifying citation and reference patterns
* Finding over-reliance on specific sources
* Spotting domains you link to frequently (partnership opportunities)
* Ensuring external link diversity for natural link profiles

Results include outbound link counts and expandable details showing which posts link to each domain and what anchor texts are used.

### Anchor Text Patterns

Analyzes anchor text usage across your site to identify:

* Over-optimized anchor text (repeated exact-match phrases)
* Branded vs. generic anchor distribution
* Opportunities for natural anchor text variation
* Potential keyword stuffing patterns

This intelligence supports editorial decisions about natural, user-friendly anchor text selection.

## Scan Engine Design

Link Health uses a single-URL-per-AJAX-request architecture to prevent server timeouts and resource exhaustion:

**Scan Process**

1. Administrator initiates scan from plugin interface
2. Plugin queries WordPress database for posts matching selected content types
3. For each post, content is parsed to extract all link elements
4. URLs are queued for validation
5. JavaScript makes sequential AJAX requests, processing one URL per request
6. Each URL is validated using native WordPress functions or HTTP requests as needed
7. Results are stored in custom database tables
8. Scan continues until all URLs are processed or administrator cancels

**Performance Characteristics**

* No background processing or WP-Cron usage
* All scanning happens in admin-initiated foreground sessions
* Processing rate depends on site URL count and server response time
* Large sites may take 10-30 minutes to complete full scans
* Scans can be cancelled and resumed without data loss

**Resource Management**

* WordPress transient caching reduces redundant HTTP requests
* Database queries use indexed columns for performance
* Results pagination prevents memory exhaustion on large result sets
* One-URL-at-a-time processing prevents PHP timeout issues

## Manual Fixing Philosophy

Link Health intentionally requires manual approval for all link modifications. Automated fixing presents several risks:

**Editorial Risks**

* Context matters: "Click here" may need to become "View pricing" when the destination changes
* Some redirects are intentional (campaign tracking, A/B tests)
* Broken external links may need archival versions, not just removal

**Technical Risks**

* Bulk URL replacement may match unintended occurrences
* Post revisions can be corrupted by automated find-replace operations
* Some URLs appear in shortcodes, meta fields, or structured data where direct replacement fails

**SEO Risks**

* Changing anchor text affects on-page optimization
* Redirect elimination may remove intentional canonicalization
* External link removal affects topical relevance signals

By requiring manual approval, Link Health ensures that site maintainers make informed decisions about each link change.

## Performance and Safety

Link Health is designed for production WordPress environments:

**Frontend Performance**

* Zero frontend JavaScript or CSS loading
* No public-facing functionality or route handlers
* Admin-only codebase with capability checks

**Database Design**

* Custom tables for scan data (not post meta or options table)
* Indexed columns for efficient querying
* Optional cleanup on uninstall to prevent database bloat

**Security**

* All AJAX handlers verify WordPress nonces
* Capability checks on all admin functions
* Prepared statements for all database queries
* No user input rendered without escaping

**Compatibility**

* Works with all public post types (posts, pages, custom post types)
* Compatible with common page builders (Gutenberg, Classic Editor)
* Extracts links from post_content field without requiring specific editor formats
* No conflicts with caching plugins (scans happen in admin, not frontend)

## Screenshots

**Screenshot 1: Internal Link Issues**

The Internal Links view displays detected redirects and errors in a sortable table. Each row shows the source post, anchor text, current URL, destination (for redirects), HTTP status code, and action buttons. The interface provides quick access to edit the source post, apply a fix to update the URL, or ignore the issue.

**Screenshot 2: External Link Errors**

External Errors view lists broken outbound links with source post information, anchor text, broken URL, and HTTP status code. Administrators can review each broken link and decide whether to update, remove, or ignore it based on editorial judgment.

**Screenshot 3: Link Intelligence Analysis**

The Intelligence view shows most-linked internal pages ranked by inbound link count. Each entry can be expanded to show which posts link to it and what anchor texts are used. This provides strategic insights into content interconnectedness and helps identify pillar pages or orphaned content.

**Screenshot 4: External Domains**

External Domains intelligence reveals which external websites receive the most outbound links from your content. Expandable details show source posts and anchor text distribution for each domain, enabling analysis of citation patterns and external link diversity.

**Screenshot 5: Scan History**

Scan History tracks all completed scans with details about scan type, configuration (which post types were scanned), total items processed, issues found, and timestamps. This audit trail helps teams track maintenance activities and measure link health improvements over time.

## Data Handling and Privacy

**Data Storage**

All scan results, link issues, and intelligence data are stored in custom WordPress database tables prefixed with `wp_li_`. The plugin creates the following tables:

* `wp_li_settings`: Plugin configuration
* `wp_li_scans`: Scan history records
* `wp_li_issues`: Detected link problems
* `wp_li_ignored`: Manually ignored issues
* `wp_li_fixes`: Applied fix history
* `wp_li_intelligence`: Link pattern analysis
* `wp_li_scan_state`: Active scan state tracking

**Data Retention**

By default, all plugin data persists after uninstall. If you want automatic cleanup, enable the "Delete on Uninstall" option in Settings before uninstalling the plugin.

**External Requests**

Link Health makes HTTP requests only to URLs found in your own content during scans. No data is sent to external services or third-party APIs. All scanning happens server-side within your WordPress installation.

**Privacy Compliance**

The plugin stores only:

* URLs found in your content
* HTTP response codes from URL validation
* Anchor text from your posts
* WordPress post IDs and titles

No personally identifiable information is collected, stored, or transmitted. No cookies are set. No frontend tracking occurs.

## Installation and Configuration

1. Upload the `link-health` directory to `/wp-content/plugins/`
2. Activate through the WordPress Plugins menu
3. Navigate to Link Health in the admin sidebar
4. Configure Settings:
   * Select which post types to include in scans
   * Set data retention preferences
5. Start your first scan from any tab

## Technical Requirements

* WordPress 5.6 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher / MariaDB 10.1 or higher
* Administrator account for plugin access

## Credits

**Developed by:** [Àgbà Akin](https://akinolaakeem.com)  
**Managed by:** [Ssu-Technology Limited](https://swiftspeed.org)  
**Supported by:** [Swiftspeed](https://swiftspeed.app)

## License

Link Health is free software released under the GNU General Public License v2.0 or later. See LICENSE file for details.
