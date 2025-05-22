<?php
/**
 * Front-end Template for Custom Pages
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add template_redirect action to detect custom page requests
 */
add_action('template_redirect', 'csp_custom_template');

function csp_custom_template() {
    // Check if this is a request for a custom page
    if (isset($_GET['custom_page']) || get_query_var('custom_page')) {
        $slug = isset($_GET['custom_page']) ? sanitize_text_field($_GET['custom_page']) : get_query_var('custom_page');
        
        // Preview mode check
        $is_preview = isset($_GET['preview']) && $_GET['preview'] == 1;
        
        // Get the page data
        $page = csp_get_page_by_slug($slug);
        
        if ($page) {
            // If it's not a preview and the page is a draft, 404
            if (!$is_preview && $page['status'] !== 'published') {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }
            
            // Render the page
            csp_render_page($page);
            exit;
        }
    }
}

/**
 * Get a page by slug
 */
function csp_get_page_by_slug($slug) {
    global $wpdb;
    $table = $wpdb->prefix . 'enhanced_csp_pages';
    
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug), ARRAY_A);
    
    if (!$row) {
        return false;
    }
    
    // Transform the row data
    $row['id'] = intval($row['id']);
    $row['in_sitemap'] = (bool) $row['in_sitemap'];
    $row['in_header_menu'] = (bool) $row['in_header_menu'];
    
    // JSON fields
    $json_fields = ['sections', 'faq_items', 'video_components', 'breadcrumbs'];
    foreach ($json_fields as $field) {
        $row[$field] = !empty($row[$field]) ? json_decode($row[$field], true) : [];
    }
    
    return $row;
}

/**
 * Render a custom page
 */
function csp_render_page($page) {
    // Get header
    get_header();
    ?>
    <div class="csp-page-container">
        <div class="csp-page-header">
            <?php if (!empty($page['hero_banner_text'])) : ?>
            <div class="csp-hero-banner-text">
                <?php echo wp_kses_post($page['hero_banner_text']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($page['hero_image'])) : ?>
            <div class="csp-hero-image">
                <img src="<?php echo esc_url($page['hero_image']); ?>" alt="<?php echo esc_attr($page['h1']); ?>">
            </div>
            <?php endif; ?>
            
            <h1><?php echo esc_html($page['h1']); ?></h1>
            
            <?php if (!empty($page['intro_text'])) : ?>
            <div class="csp-intro-text">
                <?php echo wp_kses_post($page['intro_text']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($page['section_text'])) : ?>
            <div class="csp-section-text">
                <?php echo wp_kses_post($page['section_text']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($page['breadcrumbs'])) : ?>
            <nav class="csp-breadcrumbs" aria-label="Breadcrumb">
                <ul>
                    <li><a href="<?php echo esc_url(home_url()); ?>">Home</a></li>
                    <?php foreach ($page['breadcrumbs'] as $breadcrumb) : ?>
                        <li>
                            <?php if (!empty($breadcrumb['url'])) : ?>
                                <a href="<?php echo esc_url($breadcrumb['url']); ?>"><?php echo esc_html($breadcrumb['text']); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($breadcrumb['text']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        
        <div class="csp-page-content">
            <?php if (!empty($page['sections'])) : ?>
                <?php foreach ($page['sections'] as $section) : ?>
                    <section class="csp-section" id="<?php echo esc_attr($section['anchor']); ?>">
                        <h2><?php echo esc_html($section['heading']); ?></h2>
                        <div class="csp-section-content">
                            <?php echo wp_kses_post($section['content']); ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($page['conclusion_heading']) || !empty($page['conclusion_content'])) : ?>
                <div class="csp-conclusion-section">
                    <?php if (!empty($page['conclusion_heading'])) : ?>
                        <h2><?php echo esc_html($page['conclusion_heading']); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($page['conclusion_content'])) : ?>
                        <div class="csp-conclusion-content">
                            <?php echo wp_kses_post($page['conclusion_content']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($page['video_components'])) : ?>
                <?php foreach ($page['video_components'] as $video) : ?>
                    <div class="csp-video-component">
                        <?php if (!empty($video['heading'])) : ?>
                            <h3><?php echo esc_html($video['heading']); ?></h3>
                        <?php endif; ?>
                        
                        <?php if (!empty($video['text'])) : ?>
                            <div class="csp-video-text">
                                <?php echo wp_kses_post($video['text']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($video['embed'])) : ?>
                            <div class="csp-video-embed">
                                <?php echo wp_kses_post($video['embed']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($page['author_name']) || !empty($page['author_bio'])) : ?>
                <section class="csp-author-section">
                    <h2>About the Author</h2>
                    <div class="csp-author-container">
                        <?php if (!empty($page['author_image'])) : ?>
                            <div class="csp-author-image">
                                <img src="<?php echo esc_url($page['author_image']); ?>" alt="<?php echo esc_attr($page['author_name']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="csp-author-details">
                            <?php if (!empty($page['author_name'])) : ?>
                                <h3 class="csp-author-name"><?php echo esc_html($page['author_name']); ?></h3>
                            <?php endif; ?>
                            
                            <?php if (!empty($page['author_bio'])) : ?>
                                <div class="csp-author-bio">
                                    <?php echo wp_kses_post($page['author_bio']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($page['author_social_links'])) : ?>
                                <div class="csp-author-social">
                                    <?php foreach ($page['author_social_links'] as $social) : ?>
                                        <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="csp-social-link">
                                            <?php echo esc_html($social['platform']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if (!empty($page['faq_items'])) : ?>
                <section class="csp-faq-section">
                    <h2><?php echo !empty($page['faq_heading']) ? esc_html($page['faq_heading']) : 'Frequently Asked Questions'; ?></h2>
                    <div class="csp-faq-items">
                        <?php foreach ($page['faq_items'] as $faq) : ?>
                            <div class="csp-faq-item">
                                <div class="csp-faq-question">
                                    <h3><?php echo esc_html($faq['question']); ?></h3>
                                </div>
                                <div class="csp-faq-answer">
                                    <?php echo wp_kses_post($faq['answer']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
    <?php
    // Get footer
    get_footer();
}

/**
 * Add custom page meta tags
 */
add_action('wp_head', 'csp_add_meta_tags');

function csp_add_meta_tags() {
    // Check if this is a custom page
    if (isset($_GET['custom_page']) || get_query_var('custom_page')) {
        $slug = isset($_GET['custom_page']) ? sanitize_text_field($_GET['custom_page']) : get_query_var('custom_page');
        $page = csp_get_page_by_slug($slug);
        
        if ($page) {
            // Meta title
            if (!empty($page['meta_title'])) {
                // Remove default wp_title action
                remove_action('wp_head', '_wp_render_title_tag', 1);
                ?>
                <title><?php echo esc_html($page['meta_title']); ?></title>
                <?php
            }
            
            // Meta description
            if (!empty($page['meta_desc'])) {
                ?>
                <meta name="description" content="<?php echo esc_attr($page['meta_desc']); ?>">
                <?php
            }
            
            // OG tags
            if (!empty($page['og_title'])) {
                ?>
                <meta property="og:title" content="<?php echo esc_attr($page['og_title']); ?>">
                <?php
            }
            
            if (!empty($page['og_desc'])) {
                ?>
                <meta property="og:description" content="<?php echo esc_attr($page['og_desc']); ?>">
                <?php
            }
            
            if (!empty($page['og_image'])) {
                ?>
                <meta property="og:image" content="<?php echo esc_url($page['og_image']); ?>">
                <?php
            }
            
            // Canonical URL
            if (!empty($page['canonical'])) {
                ?>
                <link rel="canonical" href="<?php echo esc_url($page['canonical']); ?>">
                <?php
            } else {
                ?>
                <link rel="canonical" href="<?php echo esc_url(home_url('/' . $page['slug'])); ?>">
                <?php
            }
            
            // Meta robots
            if (!empty($page['robots'])) {
                ?>
                <meta name="robots" content="<?php echo esc_attr($page['robots']); ?>">
                <?php
            }
        }
    }
}

/**
 * Add custom pages to sitemap
 * This is a basic implementation that can be expanded for different SEO plugins
 */
add_filter('wp_sitemaps_posts_query_args', 'csp_add_to_sitemap', 10, 2);

function csp_add_to_sitemap($args, $post_type) {
    // This is a placeholder - you would need to implement specific integration
    // with your chosen SEO plugin. Most plugins have hooks to add custom URLs.
    return $args;
}