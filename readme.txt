=== Link Diagnostics – Broken Links, Redirects, and Link Insights ===
Contributors: swiftspeed
Tags: broken links, link checker, internal links, redirect detection, SEO, 404 errors, link management
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive link health monitoring, redirect management, and SEO intelligence for WordPress sites. Find broken links, optimize internal linking, and improve site performance.

== Description ==

Link Diagnostics is a powerful SEO and link management tool that gives you complete visibility into your site's link structure. Whether you're managing a small blog or a large content library, Link Diagnostics helps you maintain healthy links, optimize internal linking patterns, and improve your site's overall SEO performance.

= Core Features =

**🔍 Internal Link Health Monitoring**

Keep your internal links in perfect shape with comprehensive scanning and detection:

* Detects HTTP redirects on internal links (301, 302, 307, 308)
* Identifies broken internal links (404 errors)
* Finds internal server errors (500-level responses)
* Shows exact location of each issue by post, anchor text, and URL
* Provides quick access to fix issues directly in post editor

**🌐 External Link Error Detection**

Monitor all your outbound links to ensure they're working correctly:

* Scans external links for 404 and 410 errors
* Identifies external server errors (5xx responses)
* Reports unreachable external domains
* Tracks which posts contain broken external links
* Helps maintain site credibility and user experience

**📊 Link Intelligence & SEO Analysis**

Gain powerful insights into your internal linking strategy:

* **Most Linked Pages**: Discover your power pages and content hubs
* **Anchor Text Analysis**: See how you're linking internally across your site
* **External Domain Tracking**: Monitor your outbound link relationships
* **Orphan Page Detection**: Find content with zero internal links
* **Link Distribution Insights**: Identify opportunities to strengthen internal linking
* **Content Authority Mapping**: Understand which pages are central to your site structure

**⚡ Smart URL Redirect Management**

Create and manage redirects with ease:

* Support for 301, 302, 307, and 308 redirect types
* Add multiple source URLs to one destination in bulk
* Category-based organization for better management
* Active/Inactive status toggling
* Bulk operations for efficient redirect management
* Professional redirect handling with proper HTTP headers
* Source URL uniqueness validation prevents conflicts

**🎯 Powerful Scanning System**

* On-demand scanning with configurable content type selection
* Detailed scan history with timestamp tracking
* Process posts, pages, and custom post types
* Bulk operations for efficient link management
* Issue filtering by status, content type, and severity

**⚙️ Editorial Control & Workflow**

* Review each issue before taking action
* One-click access to post editor for fixes
* Ignore specific issues permanently if needed
* Full audit trail of all changes
* Maintains content integrity and SEO safety

= Real-World Benefits =

**For SEO Professionals:**
* Eliminate broken links that hurt rankings
* Optimize internal linking structure
* Identify redirect chains slowing page speed
* Monitor external link quality
* Find orphaned content needing promotion

**For Content Teams:**
* Pre-migration link audits before redesigns
* Regular link health maintenance
* Identify content interconnection opportunities
* Maintain high-quality user experience
* Prepare editorial reports on site structure

**For Site Owners:**
* Improve site crawlability and indexation
* Reduce bounce rates from broken links
* Strengthen topical authority through smart internal linking
* Monitor external partner link health
* Make data-driven content strategy decisions

= Performance & Reliability =

* Optimized AJAX-based scanning prevents server timeouts
* Respects WordPress transient caching
* No frontend performance impact
* Efficient custom database tables
* Works on sites of any size

== Installation ==

1. Upload the `link-diagnostic-and-insights` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Link Diagnostics' in the WordPress admin menu
4. Select your content types in Settings
5. Click 'Start Scan' to begin analyzing your links

== Frequently Asked Questions ==

= How does the plugin fix broken links? =

Link Diagnostics provides detailed reports of all link issues with one-click access to fix them. Click 'Edit' to open the post editor and update the link, or use 'Fix' to update a specific link URL directly. You maintain full control over all changes.

= How long does scanning take? =

Scan time depends on your site size. The plugin processes links efficiently using AJAX requests to prevent server timeouts. A site with 100 posts typically completes in a few minutes. Large sites take longer but remain stable throughout the process.

= What happens to my data when I uninstall? =

You control data retention. By default, scan data is preserved. Enable 'Delete on Uninstall' in Settings if you want automatic cleanup upon plugin removal.

= Can I scan specific post types only? =

Yes! Configure which post types to include in Settings. Scan only published posts and pages, or include custom post types as needed.

= Does this work on large websites? =

Absolutely. Link Diagnostics is designed for production environments of any size. The efficient scanning system processes one URL per request to prevent resource exhaustion.

= What's the difference between Internal Links and Link Intelligence? =

**Internal Links** scans for problems: redirects, broken links, and errors that need fixing.

**Link Intelligence** analyzes your linking patterns: which pages get the most links, how you use anchor text, and where external links point. This helps optimize your SEO strategy.

= How do the redirect management features work? =

Create redirects by adding source URL(s) and a destination. Support for 301 (permanent), 302 (temporary), 307 (temporary preserving method), and 308 (permanent preserving method). Each source URL can only exist once to prevent conflicts. Organize with categories and toggle active/inactive status.

= Can I export scan results? =

Version 1.0.0 focuses on the admin interface for analysis. All data is stored in your WordPress database for future reference and trend analysis.

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
* Manual fix controls with post editor integration
* Bulk fix operations
* Scan history tracking
* Configurable data retention
* Support for posts, pages, and custom post types

== Credits ==

Developed by [Àgbà Akin](https://akinolaakeem.com)  
Managed by [Ssu-Technology Limited](https://swiftspeed.org)  
Supported by [Swiftspeed](https://swiftspeed.app)
