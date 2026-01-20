=== Link Health ===
Contributors: swiftspeed
Tags: broken links, link checker, internal links, redirect detection, SEO, 404 errors, link management
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitor internal and external link health, detect broken links and redirects, and gain editorial link intelligence without automatic content changes.

== Description ==

Link Health is a WordPress link analysis and monitoring tool designed for editorial teams and SEO professionals who need comprehensive visibility into their site's link structure without risking automated content modifications.

This plugin scans your WordPress content to identify link issues and provides detailed intelligence about your internal linking patterns and external domain relationships. All fixes require explicit editorial approval through the admin interface.

= What Link Health Does =

**Internal Link Health Monitoring**

* Detects HTTP redirects on internal links (301, 302, 307, 308)
* Identifies broken internal links (404 errors)
* Finds internal server errors (500-level responses)
* Shows exact location of each issue by post, anchor text, and URL
* Provides one-click access to edit problematic posts

**External Link Error Detection**

* Scans outbound links for 404 and 410 errors
* Identifies external server errors (5xx responses)
* Reports unreachable external domains
* Tracks which posts link to broken external resources

**Link Intelligence Analysis**

* Identifies most-linked internal pages (inbound link tracking)
* Maps external domain link frequency (outbound link analysis)
* Analyzes anchor text distribution across your content
* Reveals linking patterns for content strategy insights
* Helps identify pillar content and key external relationships

**Scan Management**

* On-demand scanning only (no background automation)
* Configurable content type selection (posts, pages, custom post types)
* Detailed scan history with timestamp tracking
* Option to ignore specific issues permanently
* Bulk operations for efficient link management

= Why Manual Fixes Only =

Link Health intentionally does not modify your content automatically. Reasons include:

* **Editorial Control**: Link updates often require contextual decisions
* **Content Integrity**: Prevents unintended changes to carefully crafted content
* **SEO Safety**: Gives you time to evaluate redirect chains and alternative destinations
* **Audit Trail**: All changes remain under version control and editorial review

= Performance and Safety =

* Processes one URL per AJAX request to prevent server timeout
* Respects WordPress transient caching for external URL checks
* Optional data cleanup on uninstall
* No frontend JavaScript or stylesheet loading
* Stores all data in custom database tables for performance

= Typical Use Cases =

* Pre-migration link audits before site redesigns
* Regular SEO maintenance for large content libraries
* Identifying redirect chains that harm page speed
* Finding orphaned content with no inbound links
* Monitoring external link quality over time
* Preparing editorial reports on content interconnectedness

== Installation ==

1. Upload the `link-health` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Link Health' in the WordPress admin menu
4. Configure your scan settings
5. Click 'Start Scan' on any tab to begin analysis

== Frequently Asked Questions ==

= Does this plugin automatically fix broken links? =

No. Link Health provides detection and analysis tools. All fixes must be applied manually through the WordPress admin interface. You can click 'Edit' to open the post editor, or use 'Fix' to update a specific link URL, but you must initiate each action.

= How does scanning work? =

When you click 'Start Scan', the plugin processes your content one URL at a time using AJAX requests. This prevents server timeouts on large sites. Scanning may take several minutes depending on the number of posts and links. You can cancel scans at any time.

= What happens to my data when I uninstall the plugin? =

By default, all scan data, link issues, and intelligence records are preserved when you uninstall Link Health. If you want automatic data deletion on uninstall, enable the 'Delete on Uninstall' option in Settings before uninstalling.

= Can I scan specific post types only? =

Yes. In the Settings tab, you can configure which post types to include in scans. This is useful if you only want to check published posts and pages while excluding other custom post types.

= Does this work on large websites? =

Yes. Link Health is designed for production environments and processes one URL per request to prevent server resource exhaustion. Larger sites will simply take longer to complete scans.

= What's the difference between Internal Links and Link Intelligence? =

Internal Links scans for problems (redirects, broken links, errors). Link Intelligence analyzes healthy linking patterns (most-linked pages, anchor text usage, external domain relationships) without checking HTTP status. Both provide different editorial insights.

= Can I export scan results? =

Version 1.0.0 does not include export functionality. All data is viewable in the admin interface and stored in your WordPress database.

== Screenshots ==

1. Internal Link Issues view showing 301 redirects with post details, current URLs, redirect destinations, and manual fix controls
2. External Link Errors displaying broken external links with HTTP status codes and source post information
3. Link Intelligence Analysis showing most-linked internal pages with inbound link counts and editorial insights
4. External Domains view revealing outbound link patterns with expandable details for each domain, including source posts and anchor texts used
5. Scan History table tracking completed scans with configuration details, timestamps, and issue counts

== Changelog ==

= 1.0.0 =
* Initial release
* Internal link health monitoring (redirects, 404s, errors)
* External link error detection (404, 410, 5xx)
* Link intelligence analysis (most linked pages, external domains, anchor text patterns)
* Manual fix controls with post editor integration
* Bulk fix operations
* Scan history tracking
* Configurable data retention on uninstall
* Support for posts, pages, and custom post types
* One-URL-per-request scanning for server stability

== Credits ==

Developed by [Àgbà Akin](https://akinolaakeem.com)  
Managed by [Ssu-Technology Limited](https://swiftspeed.org)  
Supported by [Swiftspeed](https://swiftspeed.app)
