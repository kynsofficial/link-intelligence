# Link Diagnostics – Broken Links, Redirects, and Link Insights

**Version:** 1.0.0  
**Requires:** WordPress 5.6+, PHP 7.4+  
**License:** GPL v2 or later

Comprehensive link health monitoring, redirect management, and SEO intelligence for WordPress. Find broken links, optimize internal linking, and boost your site's SEO performance.

## Overview

Link Diagnostics is a powerful SEO and link management tool that gives you complete visibility into your site's link structure. Monitor link health, detect issues, detect redirects, optimize internal linking patterns, and fix redirects issues - all from a simple interface.

**Perfect for:**
- SEO professionals optimizing site performance
- Content teams maintaining quality standards
- Site owners preparing for migrations
- Anyone managing a content-rich WordPress site

## Key Features

### 🔍 Internal Link Health Monitoring

Keep your internal links in perfect condition:

- **Redirect Detection**: Find all 301, 302, 307, and 308 redirects and fix them. Clicking fix simply update the anchor text by replacing currently attached url with url being redirected to. BOOST SEO massively
- **Broken Link Detection**: Identify 404 errors immediately and point to the exact anchor url of this issue.
- **Server Error Tracking**: Catch 500-level responses
- **Detailed Reporting**: See exact post, anchor text, and URL for each issue
- **Quick Fixes**: One-click access to post editor or direct URL replacement

### 🌐 External Link Error Detection

Monitor all outbound links for issues:

- **404 & 410 Detection**: Find broken external links
- **Server Error Tracking**: Identify 5xx responses
- **Unreachable Domain Alerts**: Spot DNS failures and timeouts
- **Source Tracking**: Know exactly which posts contain broken links
- **User Experience Protection**: Maintain site credibility

### 📊 Link Intelligence & SEO Analysis

Gain powerful insights into your linking strategy:

**Most Linked Pages**
- Discover your content hubs and power pages
- See which pages get the most internal attention
- Identify opportunities to strengthen important pages

**Anchor Text Analysis**
- Understand how you're linking internally
- Find over-optimized or under-utilized anchor text
- Improve natural language patterns

**External Domain Tracking**
- Monitor outbound link relationships
- Identify frequently cited sources
- Track external domain patterns

**Orphan Page Detection**
- Find content with zero internal links
- Discover hidden or forgotten pages
- Improve site-wide connectivity

### ⚡ Smart URL Redirect Management

Professional redirect handling made easy:

- **Multiple Redirect Types**: 301, 302, 307, 308 support
- **Bulk Source URLs**: Add multiple sources to one destination
- **Category Organization**: Group redirects for easy management
- **Status Control**: Toggle active/inactive without deletion
- **Uniqueness Validation**: Prevents duplicate source URLs
- **Bulk Operations**: Manage redirects efficiently

### 🎯 Powerful Scanning System

- **On-Demand Scanning**: Run scans when you need them
- **Content Type Selection**: Choose posts, pages, custom post types
- **Scan History**: Track all scans with detailed logs
- **Bulk Operations**: Fix multiple issues efficiently
- **Advanced Filtering**: Filter by status, type, severity

## Real-World Benefits

### For SEO Professionals

**Eliminate Ranking Penalties** from broken links  
**Optimize Internal Linking** for better authority flow  
**Fix Redirect Chains** that slow page speed  
**Monitor Link Quality** systematically  
**Find Orphaned Content** needing promotion

### For Content Teams

**Pre-Migration Audits** before site redesigns  
**Regular Maintenance** schedules  
**Content Interconnection** opportunities  
**Quality Standards** enforcement  
**Editorial Reports** on site structure

### For Site Owners

**Improved Crawlability** and indexation  
**Reduced Bounce Rates** from dead links  
**Stronger Topical Authority**  
**External Partner Monitoring**  
**Data-Driven Decisions**

## How It Works

### Scanning Process

1. Select content types to scan (posts, pages, custom post types)
2. Click 'Start Scan' from any tab
3. Plugin processes links efficiently using AJAX
4. Review detailed reports of findings
5. Fix issues with one-click access to editor
6. Track progress in Scan History

### Link Intelligence

The intelligence engine analyzes your entire link structure to reveal:

- **Which pages are your content hubs** (most linked internally)
- **How you're using anchor text** across your site
- **Where external links point** (domain analysis)
- **Which pages need attention** (orphan detection)

### Redirect Management

Create and manage redirects professionally:

1. Add source URL(s) and destination
2. Choose redirect type (301, 302, 307, 308)
3. Organize with categories
4. Toggle active/inactive as needed
5. Bulk manage for efficiency

## Technical Details

### Performance Optimized

- AJAX-based scanning prevents timeouts
- One URL per request for stability
- Efficient database queries with proper indexing
- No frontend performance impact
- Works on sites of any size

### Security First

- Nonce verification on all AJAX requests
- Capability checks on admin actions
- Prepared statements for database queries
- Escaped output for rendered data
- WordPress coding standards compliant

### Database Structure

Custom tables for optimal performance:

- `wp_lhcfwp_settings` - Plugin configuration
- `wp_lhcfwp_scans` - Scan history
- `wp_lhcfwp_issues` - Detected problems
- `wp_lhcfwp_ignored` - Ignored issues
- `wp_lhcfwp_fixes` - Fix history
- `wp_lhcfwp_intelligence` - Link analysis
- `wp_lhcfwp_redirects` - URL redirects

### Data Handling

**Storage:** All data stays in your WordPress database  
**Privacy:** No external services, no tracking, no cookies  
**Retention:** Configurable - keep or delete on uninstall  
**Requests:** Only validates URLs found in your content

## Installation

1. Upload `link-diagnostic-and-insights` folder to `/wp-content/plugins/`
2. Activate through WordPress Plugins menu
3. Navigate to 'Link Diagnostics' in admin menu
4. Configure Settings (select content types)
5. Start your first scan

## Configuration

### Settings Tab

- **Content Types**: Select which post types to scan
- **Data Retention**: Choose to preserve or delete data on uninstall
- **Scan Options**: Configure scanning behavior

### First Scan

1. Click 'Internal Links' tab
2. Select content type (or use default)
3. Click 'Start Scan'
4. Monitor progress in real-time
5. Review results when complete

## Use Cases

### Pre-Migration Audit
Run comprehensive scans before site redesigns to identify and fix all link issues upfront.

### Regular Maintenance
Schedule monthly scans to catch broken links and maintain site quality.

### SEO Optimization
Use intelligence insights to strengthen internal linking and improve topical authority.

### Content Strategy
Identify content hubs, find orphaned pages, and optimize link distribution.

### Redirect Management
Handle site restructuring professionally with bulk redirect tools.

## Technical Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- MySQL 5.6+ / MariaDB 10.1+
- Administrator capability for access

## Frequently Asked Questions

### How long does scanning take?

Scan time varies by site size. A 100-post site typically completes in minutes. The plugin uses efficient AJAX processing to handle sites of any size safely.

### Can I scan specific content types?

Yes! Configure which post types to include in Settings. Scan only posts and pages or include custom post types as needed.

### How do redirects work?

Create redirects by adding source URL(s) and a destination. The plugin processes redirects before WordPress page rendering with proper HTTP status codes. Each source URL can only exist once to prevent conflicts.

### What about data retention?

You control data retention. By default, scan data is preserved. Enable 'Delete on Uninstall' in Settings for automatic cleanup.

### Does it work on large sites?

Absolutely. The plugin is designed for production environments of any size with efficient one-URL-per-request processing.

## Credits

**Developed by:** [Àgbà Akin](https://akinolaakeem.com)  
**Managed by:** [Ssu-Technology Limited](https://swiftspeed.org)  
**Supported by:** [Swiftspeed](https://swiftspeed.app)

## License

GPL v2 or later. See LICENSE file for details.
