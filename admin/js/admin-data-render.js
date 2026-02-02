/**
 * Link Intelligence - Data Rendering Module
 * With Post Type column for all issue tables
 */
(function($) {
    'use strict';
    
    window.LI_Admin = window.LI_Admin || {};
    
    const DataRender = {
        
        renderIssues: function(data, $table, scanType) {
            // Remove old insights before rendering new ones
            $('.li-seo-insights').remove();
            
            // Determine which columns to show based on scan type
            const hasDestinationColumn = scanType === 'internal_links';
            const hasCheckboxColumn = scanType !== 'external_errors';
            
            // Calculate colspan: Checkbox?, Title, PostType, Anchor, URL, Destination?, Status Code, Type, Status, Actions
            let colspanCount = 8; // Base: Title, PostType, Anchor, URL, Status Code, Type, Status, Actions
            if (hasDestinationColumn) colspanCount++;
            if (hasCheckboxColumn) colspanCount++;
            
            if (!data.issues || data.issues.length === 0) {
                $table.html(`
                    <tr>
                        <td colspan="${colspanCount}" class="li-text-center">
                            <div class="li-empty-state">
                                <div class="li-empty-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                                <div class="li-empty-title">No issues found</div>
                                <div class="li-empty-text">Great! Your site has no issues in this category</div>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            // Add SEO insights banner based on scan type
            if (scanType) {
                const insights = this.getSEOInsightsForIssues(scanType);
                const sectionId = `#li-section-${scanType.replace(/_/g, '-')}`;
                $(`${sectionId} .li-card-body`).prepend(`
                    <div class="li-seo-insights">
                        <div class="li-insight-header">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <strong>SEO Insights</strong>
                        </div>
                        <div class="li-insight-content">
                            <p><strong>What this means:</strong> ${insights.meaning}</p>
                            <p><strong>Why it matters:</strong> ${insights.importance}</p>
                            <p><strong>Action items:</strong> ${insights.action}</p>
                        </div>
                    </div>
                `);
            }
            
            let html = '';
            data.issues.forEach(issue => {
                const isFixable = issue.is_fixable == 1;
                const isFixed = issue.status === 'fixed';
                const isPending = issue.status === 'pending';
                const editUrl = lhcfwpAjax.site_url + '/wp-admin/post.php?post=' + issue.post_id + '&action=edit';
                
                html += `<tr>`;
                
                // Checkbox column for fixable scan types
                if (hasCheckboxColumn) {
                    html += `
                        <td>
                            <input type="checkbox" class="li-issue-checkbox" data-issue-id="${issue.id}" ${(isFixable && isPending) ? '' : 'disabled'}>
                        </td>`;
                }
                
                // Source (post title)
                html += `<td data-sort="post_title">${issue.post_title}</td>`;
                
                // NEW: Post Type column
                html += `<td data-sort="post_type"><span class="li-badge li-badge-secondary">${this.formatPostType(issue.post_type)}</span></td>`;
                
                // Anchor text (escape this - comes from raw HTML)
                html += `<td data-sort="anchor_text">${this.escapeHtml(this.truncateUrl(issue.anchor_text, 30))}</td>`;
                
                // Current URL
                html += `
                    <td data-sort="current_url">
                        <a href="${issue.current_url}" target="_blank">${this.truncateUrl(issue.current_url)}</a>
                    </td>`;
                
                // Destination URL (internal_links only)
                if (hasDestinationColumn) {
                    html += `
                        <td data-sort="destination_url">
                            ${issue.destination_url ? `<a href="${issue.destination_url}" target="_blank">${this.truncateUrl(issue.destination_url)}</a>` : '-'}
                        </td>`;
                }
                
                // Status code
                html += `
                    <td data-sort="status_code">
                        <span class="li-badge li-badge-${this.getStatusBadgeClass(issue.status_code)}">${issue.status_code || 'Error'}</span>
                    </td>`;
                
                // Issue type
                html += `<td data-sort="issue_type">${this.formatIssueType(issue.issue_type)}</td>`;
                
                // NEW: Status column
                html += `
                    <td data-sort="status">
                        <span class="li-badge li-badge-${isFixed ? 'success' : 'warning'}">${isFixed ? 'Fixed' : 'Pending'}</span>
                    </td>`;
                
                // Actions
                html += `<td><div class="li-table-actions">`;
                
                // Edit button - always show
                html += `
                    <a href="${editUrl}" class="li-btn li-btn-secondary li-btn-sm" target="_blank" title="Edit Post">
                        <span class="dashicons dashicons-edit"></span> Edit
                    </a>`;
                
                // Show Fix and Ignore buttons only if status is pending
                if (isPending) {
                    if (isFixable) {
                        html += `
                            <button class="li-btn li-btn-primary li-btn-sm li-fix-link" data-issue-id="${issue.id}" title="Fix Link">
                                <span class="dashicons dashicons-admin-tools"></span> Fix
                            </button>`;
                    }
                    html += `
                        <button class="li-btn li-btn-danger li-btn-sm li-ignore-link" data-issue-id="${issue.id}" title="Ignore Issue">
                            <span class="dashicons dashicons-hidden"></span> Ignore
                        </button>`;
                }
                
                html += `</div></td></tr>`;
            });
            
            $table.html(html);
        },
        
        getSEOInsightsForIssues: function(scanType) {
            const insights = {
                'internal_links': {
                    meaning: 'These internal links have issues - redirects, broken links, or server errors.',
                    importance: 'Link issues waste crawl budget, harm user experience, and can negatively impact SEO. Redirects slow down page loads and dilute link equity. Broken links signal poor maintenance.',
                    action: 'For redirects (301, 302, 307, 308): Update links to point directly to final destinations using the Fix button. For errors (404, unreachable): Fix or remove broken links immediately. Check if pages were moved or deleted.'
                },
                'external_errors': {
                    meaning: 'These outbound links point to external websites that are broken, unreachable, or returning errors.',
                    importance: 'Linking to broken external sites damages user trust and experience. While less critical than internal errors, they still reflect poorly on content quality and maintenance.',
                    action: 'Review each broken external link. Update to current working URLs, find replacement sources, or remove links to defunct sites. Verify important external links regularly.'
                }
            };
            
            return insights[scanType] || {
                meaning: 'Issues found in your content that may affect SEO and user experience.',
                importance: 'Resolving these issues improves site quality, user experience, and search engine rankings.',
                action: 'Review and fix issues using the available tools.'
            };
        },
        
         renderIntelligence: function(data, $table, metricType, dataActions) {
            if (!data.data || data.data.length === 0) {
                const emptyMessages = {
                    'most_linked_internal': 'No internal links found in scanned content',
                    'anchor_text': 'No frequently used anchor texts (5+ occurrences) found',
                    'external_domain': 'No external links found in scanned content',
                    'zero_inbound': 'All pages have at least one internal link'
                };
                
                $table.html(`
                    <tr>
                        <td colspan="3" class="li-text-center">
                            <div class="li-empty-state">
                                <div class="li-empty-icon"><span class="dashicons dashicons-chart-bar"></span></div>
                                <div class="li-empty-title">No data yet</div>
                                <div class="li-empty-text">${emptyMessages[metricType] || 'Run an intelligence scan first'}</div>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            // Add SEO insights banner - scoped to intelligence section
            const insights = this.getSEOInsights(metricType);
            $('#li-section-intelligence .li-card-body').prepend(`
                <div class="li-seo-insights">
                    <div class="li-insight-header">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <strong>SEO Insights</strong>
                    </div>
                    <div class="li-insight-content">
                        <p><strong>What this means:</strong> ${insights.meaning}</p>
                        <p><strong>Why it matters:</strong> ${insights.importance}</p>
                        <p><strong>Action items:</strong> ${insights.action}</p>
                    </div>
                </div>
            `);
            
            // Collect all post IDs to fetch
            const allPostIds = [];
            data.data.forEach(item => {
                const additionalData = item.additional_data ? JSON.parse(item.additional_data) : null;
                if (additionalData && additionalData.linking_posts) {
                    allPostIds.push(...additionalData.linking_posts);
                }
                if (item.post_id) {
                    allPostIds.push(parseInt(item.post_id));
                }
            });
            
            // Fetch all post titles then render
            dataActions.fetchPostTitles([...new Set(allPostIds)], (posts) => {
                let html = '';
                data.data.forEach((item, index) => {
                    html += this.renderIntelligenceRow(item, metricType, index, posts, dataActions);
                });
                $table.html(html);
            });
        },
        
        renderIntelligenceRow: function(item, metricType, index, posts, dataActions) {
            const rowId = `row-${metricType}-${index}`;
            const isExpanded = dataActions.expandedRows.has(rowId);
            
            let itemDisplay = '';
            let healthIcon = '';
            let recommendation = '';
            
            switch(metricType) {
                case 'most_linked_internal':
                    const postTitle = item.post_title || item.metric_key;
                    itemDisplay = `
                        <div class="li-intel-item">
                            <div class="li-intel-title">${postTitle}</div>
                        </div>
                    `;
                    if (item.metric_value >= 10) {
                        healthIcon = '<span class="li-health-icon li-health-great" title="Power Page"><span class="dashicons dashicons-star-filled"></span></span>';
                        recommendation = 'This is a power page - consider it as a hub in your internal linking strategy';
                    } else if (item.metric_value >= 5) {
                        healthIcon = '<span class="li-health-icon li-health-good" title="Popular Page"><span class="dashicons dashicons-yes"></span></span>';
                        recommendation = 'Well-connected page - good for topic authority';
                    }
                    break;
                    
                case 'anchor_text':
                    itemDisplay = `<div class="li-intel-anchor">"${this.escapeHtml(item.metric_key)}"</div>`;
                    if (item.metric_value >= 20) {
                        healthIcon = '<span class="li-health-icon li-health-warning" title="High Usage"><span class="dashicons dashicons-warning"></span></span>';
                        recommendation = 'High usage - ensure natural variation to avoid over-optimization';
                    } else if (item.metric_value >= 10) {
                        healthIcon = '<span class="li-health-icon li-health-good" title="Common Term"><span class="dashicons dashicons-yes"></span></span>';
                        recommendation = 'Common anchor text - monitor for diversity';
                    }
                    break;
                    
                case 'external_domain':
                    itemDisplay = `
                        <div class="li-intel-domain">
                            <span class="dashicons dashicons-admin-links"></span>
                            ${this.escapeHtml(item.metric_key)}
                        </div>
                    `;
                    if (item.metric_value >= 10) {
                        healthIcon = '<span class="li-health-icon li-health-warning" title="Heavy Link Partner"><span class="dashicons dashicons-warning"></span></span>';
                        recommendation = 'Heavy linking to this domain - verify quality and relevance';
                    }
                    break;
                    
                case 'zero_inbound':
                    const zeroPostTitle = item.post_title || 'Unknown';
                    itemDisplay = `
                        <div class="li-intel-item li-intel-orphan">
                            <div class="li-intel-title">${zeroPostTitle}</div>
                            <div class="li-intel-url">${this.truncateUrl(item.metric_key, 60)}</div>
                        </div>
                    `;
                    healthIcon = '<span class="li-health-icon li-health-danger" title="Orphan Page"><span class="dashicons dashicons-dismiss"></span></span>';
                    break;
            }
            
            const additionalData = item.additional_data ? JSON.parse(item.additional_data) : null;
            const hasDetails = additionalData && ((additionalData.linking_posts && additionalData.linking_posts.length > 0) || metricType === 'zero_inbound');
            
            let html = `
                <tr class="li-intel-row ${isExpanded ? 'li-row-expanded' : ''}" data-row-id="${rowId}">
                    <td>
                        ${healthIcon}
                        ${itemDisplay}
                        ${recommendation ? `<div class="li-intel-recommendation">${recommendation}</div>` : ''}
                    </td>
                    <td>
                        <span class="li-intel-count">${item.metric_value}</span>
                        ${metricType === 'most_linked_internal' ? '<span class="li-intel-label">inbound links</span>' : ''}
                        ${metricType === 'anchor_text' ? '<span class="li-intel-label">occurrences</span>' : ''}
                        ${metricType === 'external_domain' ? '<span class="li-intel-label">links</span>' : ''}
                    </td>
                    <td>
                        <div class="li-table-actions">
                            ${item.post_id ? `
                                <a href="${lhcfwpAjax.site_url}/wp-admin/post.php?post=${item.post_id}&action=edit" 
                                   class="li-btn li-btn-secondary li-btn-sm" target="_blank" title="Edit Page">
                                    <span class="dashicons dashicons-edit"></span> Edit
                                </a>
                            ` : ''}
                            ${hasDetails ? `
                                <button class="li-btn li-btn-primary li-btn-sm li-expand-row" 
                                        data-row-id="${rowId}" 
                                        data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' 
                                        data-metric-type="${metricType}"
                                        title="${isExpanded ? 'Collapse' : 'View Details'}">
                                    <span class="dashicons dashicons-${isExpanded ? 'arrow-up-alt2' : 'arrow-down-alt2'}"></span>
                                    ${isExpanded ? 'Hide' : 'More'} Details
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
            
            if (isExpanded && hasDetails) {
                html += this.renderExpandedRow(item, metricType, additionalData, posts);
            }
            
            return html;
        },
        
        renderExpandedRow: function(item, metricType, additionalData, posts) {
            let content = '';
            
            if (metricType === 'zero_inbound') {
                const postData = additionalData;
                content = `
                    <div class="li-orphan-details">
                        <p><strong>This page has ZERO internal links pointing to it.</strong></p>
                        <p><strong>Post Type:</strong> ${postData.post_type || 'Unknown'} | 
                        <strong>Published:</strong> ${postData.post_date ? new Date(postData.post_date).toLocaleDateString() : 'Unknown'}</p>
                    </div>
                `;
            } else if (metricType === 'external_domain' || metricType === 'most_linked_internal') {
                const linkingPosts = additionalData.linking_posts || [];
                const anchorTexts = additionalData.anchor_texts || {};
                
                // Create a mapping of post_id to anchor texts used in that post
                const postToAnchors = this.mapPostsToAnchors(linkingPosts, anchorTexts, item.metric_key);
                
                content = `
                    <div class="li-expanded-header">
                        <span class="dashicons dashicons-admin-post"></span>
                        <strong>${linkingPosts.length} page(s) linking here:</strong>
                    </div>
                    <div class="li-posts-with-anchors-grid">
                `;
                
                // Render each post with its anchor texts
                linkingPosts.forEach(postId => {
                    const post = posts[postId] || { id: postId, title: 'Unknown', type: 'Unknown' };
                    const postAnchors = postToAnchors[postId] || [];
                    
                    content += `
                        <div class="li-post-anchor-card">
                            <div class="li-post-card-header">
                                <span class="dashicons dashicons-media-document"></span>
                                <div class="li-post-card-title-section">
                                    <div class="li-post-card-title">${this.escapeHtml(post.title)}</div>
                                    <div class="li-post-card-meta">
                                        <span class="li-meta-item">ID: ${post.id}</span>
                                        <span class="li-meta-separator">•</span>
                                        <span class="li-meta-item">Type: ${post.type}</span>
                                        <span class="li-meta-separator">•</span>
                                        <a href="${post.edit_url || (lhcfwpAjax.site_url + '/wp-admin/post.php?post=' + post.id + '&action=edit')}" 
                                           class="li-meta-link" target="_blank">
                                            Edit <span class="dashicons dashicons-external"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="li-post-card-body">
                                <div class="li-anchor-tags-label">
                                    <span class="dashicons dashicons-editor-quote"></span>
                                    Anchor Texts Used:
                                </div>
                                <div class="li-anchor-tags-wrapper">
                    `;
                    
                    if (postAnchors.length > 0) {
                        postAnchors.forEach(anchor => {
                            content += `
                                <div class="li-anchor-tag">
                                    <span class="li-anchor-tag-text">"${this.escapeHtml(anchor.text)}"</span>
                                    <span class="li-anchor-tag-count">${anchor.count}×</span>
                                </div>
                            `;
                        });
                    } else {
                        content += `<div class="li-anchor-tag-empty">No anchor text data available</div>`;
                    }
                    
                    content += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                content += `
                    </div>
                `;
                
            } else if (metricType === 'anchor_text') {
                const linkingPosts = additionalData.linking_posts || [];
                
                content = `
                    <div class="li-expanded-header">
                        <span class="dashicons dashicons-admin-post"></span>
                        <strong>Used in ${linkingPosts.length} page(s):</strong>
                    </div>
                    <div class="li-simple-posts-grid">
                `;
                
                linkingPosts.forEach(postId => {
                    const post = posts[postId] || { id: postId, title: 'Unknown', type: 'Unknown' };
                    content += `
                        <div class="li-simple-post-card">
                            <span class="dashicons dashicons-media-document"></span>
                            <div class="li-simple-post-info">
                                <div class="li-simple-post-title">${this.escapeHtml(post.title)}</div>
                                <div class="li-simple-post-meta">
                                    ID: ${post.id} | Type: ${post.type} | 
                                    <a href="${post.edit_url || (lhcfwpAjax.site_url + '/wp-admin/post.php?post=' + post.id + '&action=edit')}" target="_blank">
                                        Edit <span class="dashicons dashicons-external"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                content += `
                    </div>
                `;
            }
            
            return `
                <tr class="li-expanded-details">
                    <td colspan="3">
                        <div class="li-expanded-content">
                            ${content}
                        </div>
                    </td>
                </tr>
            `;
        },
        
        /**
         * Maps posts to their anchor texts based on the intelligence data
         **/

        mapPostsToAnchors: function(linkingPosts, anchorTexts, targetUrl) {
            const postToAnchors = {};
            
            // Convert anchor texts object to array
            const anchorArray = Object.entries(anchorTexts).map(([text, count]) => ({
                text: text,
                count: count
            }));
            
            // Sort by count descending
            anchorArray.sort((a, b) => b.count - a.count);
            
            // Distribute anchors across posts
            // This is an approximation - we assign anchors proportionally
            if (linkingPosts.length > 0 && anchorArray.length > 0) {
                linkingPosts.forEach((postId, index) => {
                    postToAnchors[postId] = [];
                    
                    // Assign at least one anchor to each post if possible
                    if (index < anchorArray.length) {
                        postToAnchors[postId].push(anchorArray[index]);
                    }
                });
                
                // If there are more anchors than posts, distribute them
                if (anchorArray.length > linkingPosts.length) {
                    for (let i = linkingPosts.length; i < anchorArray.length; i++) {
                        const postIndex = i % linkingPosts.length;
                        const postId = linkingPosts[postIndex];
                        postToAnchors[postId].push(anchorArray[i]);
                    }
                }
            }
            
            return postToAnchors;
        },

        
        getSEOInsights: function(metricType) {
            const insights = {
                'most_linked_internal': {
                    meaning: 'These pages receive the most internal links from your content.',
                    importance: 'Internal links pass authority and help search engines understand page importance.',
                    action: 'Verify your most important pages (money pages, pillar content) are in this list.'
                },
                'anchor_text': {
                    meaning: 'These are the clickable text phrases you use most often in internal links.',
                    importance: 'Anchor text helps search engines understand what linked pages are about.',
                    action: 'Review for over-optimization. Aim for natural, descriptive anchor text variation.'
                },
                'external_domain': {
                    meaning: 'These external websites receive the most outbound links from your content.',
                    importance: 'Outbound links to authoritative sources can boost credibility.',
                    action: 'Ensure top linked domains are reputable and relevant.'
                },
                'zero_inbound': {
                    meaning: 'These pages have no internal links pointing to them - orphan pages.',
                    importance: 'Orphan pages are difficult for search engines to discover and crawl.',
                    action: 'Add internal links to these pages from relevant content.'
                }
            };
            
            return insights[metricType] || {
                meaning: 'Link data analysis.',
                importance: 'Understanding link patterns helps optimize site architecture.',
                action: 'Review the data and make improvements.'
            };
        },
        
         renderScanHistory: function(data, $table) {
            if (!data.scans || data.scans.length === 0) {
                $table.html(`
                    <tr>
                        <td colspan="9" class="li-text-center">
                            <div class="li-empty-state">
                                <div class="li-empty-icon"><span class="dashicons dashicons-backup"></span></div>
                                <div class="li-empty-title">No scan history</div>
                                <div class="li-empty-text">Your completed scans will appear here</div>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            let html = '';
            data.scans.forEach(scan => {
                const config = this.formatScanConfig(scan.scan_config);
                
                html += `
                    <tr>
                        <td><input type="checkbox" class="li-scan-checkbox" data-scan-id="${scan.id}"></td>
                        <td data-sort="scan_type">${this.formatScanType(scan.scan_type)}</td>
                        <td data-sort="config">${config}</td>
                        <td data-sort="total_scanned">${scan.total_posts || 0}</td>
                        <td data-sort="issues_found">${scan.issues_found || 0}</td>
                        <td data-sort="status">
                            <span class="li-badge li-badge-${this.getStatusClass(scan.status)}">${this.formatStatus(scan.status)}</span>
                        </td>
                        <td data-sort="started_at">${scan.started_at || '-'}</td>
                        <td data-sort="completed_at">${scan.completed_at || '-'}</td>
                        <td>
                            <div class="li-table-actions">
                                <button class="li-btn li-btn-danger li-btn-sm li-delete-scan" data-scan-id="${scan.id}" title="Delete Scan">
                                    <span class="dashicons dashicons-trash"></span> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            $table.html(html);
        },
        
        formatScanConfig: function(configJson) {
            if (!configJson) return '-';
            
            try {
                const config = typeof configJson === 'string' ? JSON.parse(configJson) : configJson;
                let parts = [];
                
                if (config.content_type) {
                    const contentTypes = Array.isArray(config.content_type) ? config.content_type : [config.content_type];
                    parts.push(`Content: ${contentTypes.join(', ')}`);
                }
                
                return parts.length > 0 ? parts.join(' + ') : '-';
            } catch (e) {
                return '-';
            }
        },
        
        renderIgnored: function(data, $table) {
            if (!data.issues || data.issues.length === 0) {
                $table.html(`
                    <tr>
                        <td colspan="7" class="li-text-center">
                            <div class="li-empty-state">
                                <div class="li-empty-icon"><span class="dashicons dashicons-hidden"></span></div>
                                <div class="li-empty-title">No ignored issues</div>
                                <div class="li-empty-text">Issues you ignore will appear here</div>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            let html = '';
            data.issues.forEach(issue => {
                const editUrl = lhcfwpAjax.site_url + '/wp-admin/post.php?post=' + issue.post_id + '&action=edit';
                
                html += `
                    <tr>
                        <td data-sort="post_title">${issue.post_title}</td>
                        <td data-sort="post_type"><span class="li-badge li-badge-secondary">${this.formatPostType(issue.post_type)}</span></td>
                        <td data-sort="current_url"><a href="${issue.current_url}" target="_blank">${this.truncateUrl(issue.current_url)}</a></td>
                        <td data-sort="issue_type">${this.formatIssueType(issue.issue_type)}</td>
                        <td data-sort="reason">${this.escapeHtml(issue.reason || '-')}</td>
                        <td data-sort="ignored_at">${issue.ignored_at}</td>
                        <td>
                            <div class="li-table-actions">
                                <a href="${editUrl}" class="li-btn li-btn-secondary li-btn-sm" target="_blank">
                                    <span class="dashicons dashicons-edit"></span> Edit
                                </a>
                                <button class="li-btn li-btn-primary li-btn-sm li-unignore-link" data-issue-id="${issue.id}">
                                    <span class="dashicons dashicons-visibility"></span> Restore
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            $table.html(html);
        },
        
        renderPagination: function(data, $container) {
            if (data.total_pages <= 1) {
                $container.addClass('li-hidden').html('');
                return;
            }
            
            $container.removeClass('li-hidden');
            
            const currentPage = data.page;
            const totalPages = data.total_pages;
            
            let html = '';
            
            if (currentPage > 1) {
                html += `<button class="li-pagination-btn" data-page="${currentPage - 1}">Previous</button>`;
            }
            
            html += `<span class="li-pagination-info">Page ${currentPage} of ${totalPages}</span>`;
            
            if (currentPage < totalPages) {
                html += `<button class="li-pagination-btn" data-page="${currentPage + 1}">Next</button>`;
            }
            
            $container.html(html);
        },
        
        // Utility functions
        truncateUrl: function(url, maxLength = 50) {
            if (!url) return '-';
            if (url.length <= maxLength) return url;
            return url.substring(0, maxLength) + '...';
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        },
        
        formatIssueType: function(type) {
            const types = {
                '301': '301 Redirect',
                '302': '302 Redirect',
                '307': '307 Redirect',
                '308': '308 Redirect',
                '410': '410 Gone',
                '451': '451 Unavailable',
                '404': '404 Not Found',
                '404_trashed': '404 (Trashed)',
                '404_not_published': '404 (Not Published)',
                'server_error': 'Server Error',
                'unreachable': 'Unreachable',
                'redirect': 'Redirect',
                'error': 'Server Error',
                'connection_error': 'Connection Error',
                'external_error': 'External Error',
                'external_404': 'External 404',
                'external_gone': 'External Gone',
                'external_unreachable': 'External Unreachable',
                'external_server_error': 'External Server Error'
            };
            return types[type] || type;
        },
        
        formatPostType: function(type) {
            if (!type) return '-';
            // Capitalize first letter and replace underscores/hyphens with spaces
            return type.charAt(0).toUpperCase() + type.slice(1).replace(/[-_]/g, ' ');
        },
        
        formatScanType: function(type) {
            const types = {
                'internal_links': 'Internal Links',
                'external_errors': 'External Errors',
                'intelligence': 'Link Intelligence'
            };
            return types[type] || type;
        },
        
        formatStatus: function(status) {
            const statuses = {
                'running': 'Running',
                'completed': 'Completed',
                'cancelled': 'Cancelled',
                'failed': 'Failed'
            };
            return statuses[status] || status;
        },
        
        getStatusBadgeClass: function(code) {
            if (code >= 200 && code < 300) return 'success';
            if (code >= 300 && code < 400) return 'primary';
            if (code >= 400) return 'danger';
            return 'primary';
        },
        
        getStatusClass: function(status) {
            const classes = {
                'running': 'primary',
                'completed': 'success',
                'cancelled': 'danger',
                'failed': 'danger'
            };
            return classes[status] || 'primary';
        }
    };
    
    LI_Admin.DataRender = DataRender;
    
})(jQuery);