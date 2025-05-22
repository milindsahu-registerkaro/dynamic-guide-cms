<?php
/**
 * Plugin Name: Guide CMS
 * Description: A flexible and scalable content management system for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: guide-cms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GUIDE_CMS_VERSION', '1.0.0');
define('GUIDE_CMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUIDE_CMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'Guide_CMS_';
    $base_dir = GUIDE_CMS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('_', '-', strtolower($relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once GUIDE_CMS_PLUGIN_DIR . 'includes/fields-templates.php';

if (is_admin()) {
    require_once GUIDE_CMS_PLUGIN_DIR . 'admin/field-templates.php';
}

require_once GUIDE_CMS_PLUGIN_DIR . 'includes/class-guide-cms-rest-controller.php';

class Guide_CMS_Plugin {
    private $template_manager;
    private $page_manager;
    private $api_handler;

    public function __construct() {
        // Initialize components
        $this->template_manager = new Guide_CMS_Template_Manager();
        $this->page_manager = new Guide_CMS_Page_Manager();
        $this->api_handler = new Guide_CMS_API_Handler();

        // Register activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Register deactivation hook
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Add rewrite rules
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Add template loader
        add_filter('template_include', [$this, 'template_loader']);

        // Register custom post types and taxonomies
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        // Force classic editor for our post types
        add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2);
        // Add dynamic meta boxes
        add_action('add_meta_boxes', [$this, 'add_dynamic_meta_boxes']);
        // Save dynamic fields
        add_action('save_post', [$this, 'save_dynamic_fields']);
        // Register REST API meta
        add_action('init', [$this, 'register_rest_meta']);
        // Add Templates submenu under each post type
        add_action('admin_menu', [$this, 'add_templates_submenus']);
    }

    public function activate() {
        // Create database tables
        $this->template_manager->update_database_schema();
        $this->page_manager->update_database_schema();

        // Create default templates
        $this->template_manager->create_default_templates();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^guide/([^/]+)/?$',
            'index.php?guide_page=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'guide_page';
        return $vars;
    }

    public function register_rest_routes() {
        // Register custom routes for id_or_slug for all post types
        $post_types = ['guide_page', 'service_page', 'local_page'];
        foreach ($post_types as $type) {
            $controller = new Guide_CMS_REST_Controller($type);
            
            // Collection route
            register_rest_route(
                'guide-cms/v1',
                '/' . $type,
                [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => [$controller, 'get_items'],
                        'permission_callback' => [$controller, 'get_items_permissions_check'],
                    ]
                ]
            );

            // Single item by id or slug
            register_rest_route(
                'guide-cms/v1',
                '/' . $type . '/(?P<id_or_slug>[\\w\-]+)',
                [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => [$controller, 'get_item_by_id_or_slug'],
                        'permission_callback' => [$controller, 'get_item_permissions_check'],
                        'args' => [
                            'id_or_slug' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                    ]
                ]
            );
        }

        // Add debug route
        register_rest_route(
            'guide-cms/v1',
            '/debug',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function($request) {
                    return [
                        'post_types' => get_post_types(['public' => true], 'names'),
                        'rewrite_rules' => get_option('rewrite_rules'),
                        'rest_routes' => rest_get_server()->get_routes()
                    ];
                },
                'permission_callback' => '__return_true'
            ]
        );
    }

    public function template_loader($template) {
        if (get_query_var('guide_page')) {
            $page = $this->page_manager->get_page_by_slug(get_query_var('guide_page'));
            
            if ($page) {
                $template_file = GUIDE_CMS_PLUGIN_DIR . 'templates/page.php';
                
                if (file_exists($template_file)) {
                    return $template_file;
                }
            }
        }

        return $template;
    }

    public function register_post_types() {
        // Guide Pages
        register_post_type('guide_page', [
            'labels' => [
                'name' => 'Guide Pages',
                'singular_name' => 'Guide Page',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Guide Page',
                'edit_item' => 'Edit Guide Page',
                'new_item' => 'New Guide Page',
                'view_item' => 'View Guide Page',
                'search_items' => 'Search Guide Pages',
                'not_found' => 'No Guide Pages found',
                'not_found_in_trash' => 'No Guide Pages found in Trash',
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_rest' => true,
            'rest_base' => 'guide_page',
            'rest_controller_class' => 'Guide_CMS_REST_Controller',
        ]);
        // Service Pages
        register_post_type('service_page', [
            'labels' => [
                'name' => 'Service Pages',
                'singular_name' => 'Service Page',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Service Page',
                'edit_item' => 'Edit Service Page',
                'new_item' => 'New Service Page',
                'view_item' => 'View Service Page',
                'search_items' => 'Search Service Pages',
                'not_found' => 'No Service Pages found',
                'not_found_in_trash' => 'No Service Pages found in Trash',
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-hammer',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_rest' => true,
            'rest_base' => 'service_page',
            'rest_controller_class' => 'Guide_CMS_REST_Controller',
        ]);
        // Local Pages
        register_post_type('local_page', [
            'labels' => [
                'name' => 'Local Pages',
                'singular_name' => 'Local Page',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Local Page',
                'edit_item' => 'Edit Local Page',
                'new_item' => 'New Local Page',
                'view_item' => 'View Local Page',
                'search_items' => 'Search Local Pages',
                'not_found' => 'No Local Pages found',
                'not_found_in_trash' => 'No Local Pages found in Trash',
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-location-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_rest' => true,
            'rest_base' => 'local_page',
            'rest_controller_class' => 'Guide_CMS_REST_Controller',
        ]);
    }

    public function register_taxonomies() {
        $post_types = ['guide_page', 'service_page', 'local_page'];
        foreach ($post_types as $pt) {
            register_taxonomy("{$pt}_category", $pt, [
                'label' => 'Categories',
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
                'rest_base' => "{$pt}_category",
                'rest_controller_class' => 'WP_REST_Terms_Controller',
                'rewrite' => ['slug' => "{$pt}-category"],
            ]);
        }
    }

    public function disable_gutenberg($use_block_editor, $post_type) {
        if (in_array($post_type, ['guide_page', 'service_page', 'local_page'])) {
            return false;
        }
        return $use_block_editor;
    }

    public function add_dynamic_meta_boxes() {
        require_once GUIDE_CMS_PLUGIN_DIR . 'includes/field-template-manager.php';
        $manager = new Guide_CMS_Field_Template_Manager();
        $post_types = ['guide_page', 'service_page', 'local_page'];
        foreach ($post_types as $post_type) {
            $fields = $manager->get_fields($post_type);
            add_meta_box(
                $post_type . '_fields',
                ucfirst(str_replace('_', ' ', $post_type)) . ' Fields',
                function($post) use ($fields) {
                    foreach ($fields as $field) {
                        $value = get_post_meta($post->ID, $field->field_key, true);
                        echo '<p><label><strong>' . esc_html($field->field_label) . '</strong></label><br>';
                        switch ($field->field_type) {
                            case 'text':
                                echo '<input type="text" name="' . esc_attr($field->field_key) . '" value="' . esc_attr($value) . '" style="width:100%;">';
                                break;
                            case 'textarea':
                                echo '<textarea name="' . esc_attr($field->field_key) . '" style="width:100%;">' . esc_textarea($value) . '</textarea>';
                                break;
                            case 'wysiwyg':
                                wp_editor($value, $field->field_key, [
                                    'textarea_name' => $field->field_key,
                                    'media_buttons' => true,
                                    'textarea_rows' => 8,
                                ]);
                                break;
                            case 'select':
                                $options = $field->field_options && is_array($field->field_options) ? $field->field_options : [];
                                echo '<select name="' . esc_attr($field->field_key) . '">';
                                foreach ($options as $option) {
                                    $opt_value = isset($option['value']) ? $option['value'] : $option;
                                    $opt_label = isset($option['label']) ? $option['label'] : $option;
                                    echo '<option value="' . esc_attr($opt_value) . '"' . selected($value, $opt_value, false) . '>' . esc_html($opt_label) . '</option>';
                                }
                                echo '</select>';
                                break;
                            case 'boolean':
                                echo '<input type="checkbox" name="' . esc_attr($field->field_key) . '" value="1"' . checked($value, '1', false) . '> Yes';
                                break;
                            case 'image':
                                echo '<input type="text" name="' . esc_attr($field->field_key) . '" value="' . esc_attr($value) . '" style="width:80%;"> <button class="button select-image" data-target="' . esc_attr($field->field_key) . '">Select Image</button>';
                                if ($value) echo '<br><img src="' . esc_url($value) . '" style="max-width:200px;margin-top:5px;">';
                                break;
                            case 'repeater':
                                $repeater = is_array($value) ? $value : [];
                                echo '<div class="repeater-wrapper" data-key="' . esc_attr($field->field_key) . '">';
                                $sub_fields = isset($field->field_options['sub_fields']) ? $field->field_options['sub_fields'] : [];
                                $count = max(1, count($repeater));
                                for ($i = 0; $i < $count; $i++) {
                                    echo '<div class="repeater-item" style="border:1px solid #eee;padding:10px;margin-bottom:10px;">';
                                    foreach ($sub_fields as $sub) {
                                        $sub_value = isset($repeater[$i][$sub['key']]) ? $repeater[$i][$sub['key']] : '';
                                        echo '<label>' . esc_html($sub['label']) . '</label><br>';
                                        if ($sub['type'] === 'text') {
                                            echo '<input type="text" name="' . esc_attr($field->field_key) . '[' . $i . '][' . esc_attr($sub['key']) . ']" value="' . esc_attr($sub_value) . '" style="width:100%;">';
                                        } elseif ($sub['type'] === 'textarea') {
                                            echo '<textarea name="' . esc_attr($field->field_key) . '[' . $i . '][' . esc_attr($sub['key']) . ']" style="width:100%;">' . esc_textarea($sub_value) . '</textarea>';
                                        } elseif ($sub['type'] === 'wysiwyg') {
                                            wp_editor($sub_value, $field->field_key . '_' . $i . '_' . $sub['key'], [
                                                'textarea_name' => $field->field_key . '[' . $i . '][' . $sub['key'] . ']',
                                                'media_buttons' => true,
                                                'textarea_rows' => 5,
                                            ]);
                                        }
                                    }
                                    echo '</div>';
                                }
                                echo '<button class="button add-repeater-item" data-key="' . esc_attr($field->field_key) . '">Add Item</button>';
                                echo '</div>';
                                break;
                        }
                        echo '</p>';
                    }
                },
                $post_type,
                'normal',
                'default'
            );
        }
    }

    public function save_dynamic_fields($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        require_once GUIDE_CMS_PLUGIN_DIR . 'includes/field-template-manager.php';
        $manager = new Guide_CMS_Field_Template_Manager();
        $type = get_post_type($post_id);
        
        $fields = $manager->get_fields($type);
        if (!$fields) return;

        foreach ($fields as $field) {
            $key = $field->field_key;
            if (!isset($_POST[$key])) {
                if ($field->field_type === 'boolean') {
                    update_post_meta($post_id, $key, '0');
                }
                continue;
            }

            $value = $_POST[$key];
            
            switch ($field->field_type) {
                case 'repeater':
                    $value = is_array($value) ? $value : [];
                    break;
                case 'boolean':
                    $value = $value ? '1' : '0';
                    break;
                case 'image':
                    $value = esc_url_raw($value);
                    break;
                case 'wysiwyg':
                    $value = wp_kses_post($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
            }
            
            update_post_meta($post_id, $key, $value);
        }
    }

    public function register_rest_meta() {
        require_once GUIDE_CMS_PLUGIN_DIR . 'includes/field-template-manager.php';
        $manager = new Guide_CMS_Field_Template_Manager();
        
        $post_types = ['guide_page', 'service_page', 'local_page'];
        foreach ($post_types as $type) {
            $fields = $manager->get_fields($type);
            if (!$fields) continue;

            foreach ($fields as $field) {
                register_post_meta($type, $field->field_key, [
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => 'string',
                    'sanitize_callback' => function($value) use ($field) {
                        switch ($field->field_type) {
                            case 'wysiwyg':
                                return wp_kses_post($value);
                            case 'image':
                                return esc_url_raw($value);
                            case 'boolean':
                                return $value ? '1' : '0';
                            case 'repeater':
                                return is_array($value) ? $value : [];
                            default:
                                return sanitize_text_field($value);
                        }
                    },
                ]);
            }
        }
    }

    public function add_templates_submenus() {
        require_once GUIDE_CMS_PLUGIN_DIR . 'includes/field-template-manager.php';
        $manager = new Guide_CMS_Field_Template_Manager();

        $types = [
            'guide_page' => 'Guide Pages',
            'service_page' => 'Service Pages',
            'local_page' => 'Local Pages',
        ];

        foreach ($types as $type => $label) {
            // Templates submenu
            add_submenu_page(
                'edit.php?post_type=' . $type,
                $label . ' Template',
                'Templates',
                'manage_options',
                $type . '-template',
                function() use ($type, $label, $manager) {
                    $fields = $manager->get_fields($type);
                    echo '<div class="wrap"><h1>' . esc_html($label) . ' Template</h1>';
                    
                    if ($fields) {
                        echo '<table class="widefat"><thead><tr>';
                        echo '<th>Order</th>';
                        echo '<th>Key</th>';
                        echo '<th>Label</th>';
                        echo '<th>Type</th>';
                        echo '<th>Options</th>';
                        echo '</tr></thead><tbody>';
                        
                        foreach ($fields as $field) {
                            echo '<tr>';
                            echo '<td>' . intval($field->field_order) . '</td>';
                            echo '<td><code>' . esc_html($field->field_key) . '</code></td>';
                            echo '<td>' . esc_html($field->field_label) . '</td>';
                            echo '<td>' . esc_html($field->field_type) . '</td>';
                            echo '<td>';
                            
                            if ($field->field_options) {
                                $options = json_decode($field->field_options, true);
                                
                                if ($field->field_type === 'select' && isset($options['options'])) {
                                    echo '<strong>Options:</strong><br>';
                                    foreach ($options['options'] as $option) {
                                        echo '<code>' . esc_html($option['value']) . '</code> → ' . esc_html($option['label']) . '<br>';
                                    }
                                } elseif ($field->field_type === 'repeater' && isset($options['sub_fields'])) {
                                    echo '<strong>Sub-fields:</strong><br>';
                                    foreach ($options['sub_fields'] as $sub) {
                                        echo '<code>' . esc_html($sub['key']) . '</code> (' . esc_html($sub['type']) . ') → ' . esc_html($sub['label']) . '<br>';
                                    }
                                }
                            }
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                        
                        // Add usage instructions
                        echo '<div class="card" style="max-width:800px;margin-top:20px;">';
                        echo '<h2>Using These Fields</h2>';
                        echo '<p>These fields will appear in the editor when creating or editing a ' . esc_html($label) . '. You can manage these fields in the <a href="' . esc_url(admin_url('admin.php?page=guide-cms-field-templates&post_type=' . $type)) . '">Field Templates</a> section.</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="notice notice-warning">';
                        echo '<p>No template fields defined for this type. <a href="' . esc_url(admin_url('admin.php?page=guide-cms-field-templates&post_type=' . $type)) . '">Add some fields</a>.</p>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            );

            // REST API Docs submenu
            add_submenu_page(
                'edit.php?post_type=' . $type,
                $label . ' REST API Docs',
                'REST API Docs',
                'manage_options',
                $type . '-api-docs',
                function() use ($type, $label) {
                    $endpoint = '/wp-json/wp/v2/' . $type;
                    $category_endpoint = '/wp-json/wp/v2/' . $type . '_category';
                    echo '<div class="wrap"><h1>' . esc_html($label) . ' REST API Documentation</h1>';
                    
                    echo '<div class="card" style="max-width:800px;margin-top:20px;">';
                    echo '<h2>Endpoints</h2>';
                    
                    echo '<h3>Pages Endpoints</h3>';
                    echo '<ul>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '</code> - List all ' . esc_html($label) . '</li>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '?slug=example</code> - Get a specific ' . esc_html($label) . ' by slug</li>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '/{id}</code> - Get a specific ' . esc_html($label) . ' by ID</li>';
                    echo '</ul>';
                    
                    echo '<h3>Categories Endpoints</h3>';
                    echo '<ul>';
                    echo '<li><code>GET ' . esc_html($category_endpoint) . '</code> - List all categories</li>';
                    echo '<li><code>GET ' . esc_html($category_endpoint) . '/{id}</code> - Get a specific category by ID</li>';
                    echo '<li><code>GET ' . esc_html($category_endpoint) . '?slug=example</code> - Get a specific category by slug</li>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '?' . esc_html($type) . '_category={id}</code> - Get all pages in a specific category</li>';
                    echo '</ul>';
                    
                    echo '<h2>Query Parameters</h2>';
                    echo '<h3>Pages Endpoints</h3>';
                    echo '<ul>';
                    echo '<li><code>page</code> - Page number for pagination (default: 1)</li>';
                    echo '<li><code>per_page</code> - Number of items per page (default: 10, max: 100)</li>';
                    echo '<li><code>search</code> - Search query to filter results</li>';
                    echo '<li><code>slug</code> - Filter by post slug</li>';
                    echo '<li><code>status</code> - Filter by post status (publish, draft, etc.)</li>';
                    echo '<li><code>' . esc_html($type) . '_category</code> - Filter by category ID</li>';
                    echo '</ul>';
                    
                    echo '<h3>Categories Endpoints</h3>';
                    echo '<ul>';
                    echo '<li><code>page</code> - Page number for pagination (default: 1)</li>';
                    echo '<li><code>per_page</code> - Number of items per page (default: 10, max: 100)</li>';
                    echo '<li><code>search</code> - Search query to filter results</li>';
                    echo '<li><code>slug</code> - Filter by category slug</li>';
                    echo '<li><code>hide_empty</code> - Whether to hide categories with no posts (true/false)</li>';
                    echo '</ul>';
                    
                    echo '<h2>Response Format</h2>';
                    echo '<h3>Pages Response</h3>';
                    echo '<pre>{
  "id": 123,
  "title": {
    "rendered": "Example Title"
  },
  "slug": "example-title",
  "status": "publish",
  "date": "2024-01-01T00:00:00",
  "modified": "2024-01-01T00:00:00",
  "content": {
    "rendered": "Post content...",
    "protected": false
  },
  "excerpt": {
    "rendered": "Post excerpt...",
    "protected": false
  },
  "' . esc_html($type) . '_category": [1, 2, 3]
}</pre>';
                    
                    echo '<h3>Categories Response</h3>';
                    echo '<pre>{
  "id": 1,
  "count": 5,
  "description": "Category description",
  "link": "http://example.com/category/example",
  "name": "Example Category",
  "slug": "example-category",
  "taxonomy": "' . esc_html($type) . '_category",
  "parent": 0
}</pre>';
                    
                    echo '<h2>Example Usage</h2>';
                    echo '<h3>Get All Pages</h3>';
                    echo '<pre>curl -X GET "' . esc_html(home_url($endpoint)) . '"</pre>';
                    
                    echo '<h3>Get Page by Slug</h3>';
                    echo '<pre>curl -X GET "' . esc_html(home_url($endpoint)) . '?slug=example-slug"</pre>';
                    
                    echo '<h3>Get All Categories</h3>';
                    echo '<pre>curl -X GET "' . esc_html(home_url($category_endpoint)) . '"</pre>';
                    
                    echo '<h3>Get Pages in Category</h3>';
                    echo '<pre>curl -X GET "' . esc_html(home_url($endpoint)) . '?' . esc_html($type) . '_category=1"</pre>';
                    
                    echo '<h2>Working Examples</h2>';
                    echo '<ul>';
                    echo '<li>List all pages: <code>' . esc_html(home_url($endpoint)) . '</code></li>';
                    echo '<li>Get page by slug: <code>' . esc_html(home_url($endpoint)) . '?slug=example</code></li>';
                    echo '<li>Get page by ID: <code>' . esc_html(home_url($endpoint)) . '/123</code></li>';
                    echo '<li>List all categories: <code>' . esc_html(home_url($category_endpoint)) . '</code></li>';
                    echo '<li>Get category by ID: <code>' . esc_html(home_url($category_endpoint)) . '/123</code></li>';
                    echo '<li>Get category by slug: <code>' . esc_html(home_url($category_endpoint)) . '?slug=example</code></li>';
                    echo '<li>Get pages in category: <code>' . esc_html(home_url($endpoint)) . '?' . esc_html($type) . '_category=123</code></li>';
                    echo '</ul>';
                    
                    echo '<h2>Reference</h2>';
                    echo '<ul>';
                    echo '<li><a href="https://developer.wordpress.org/rest-api/" target="_blank">WordPress REST API Handbook</a></li>';
                    echo '<li><a href="https://developer.wordpress.org/rest-api/using-the-rest-api/" target="_blank">Using the REST API</a></li>';
                    echo '</ul>';
                    
                    echo '</div>';
                    echo '</div>';
                }
            );
        }
    }
}

// Initialize the plugin
new Guide_CMS_Plugin();