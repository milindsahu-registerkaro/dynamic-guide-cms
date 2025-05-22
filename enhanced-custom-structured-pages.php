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
        // Routes are registered in the API handler
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
            'rest_controller_class' => 'WP_REST_Posts_Controller',
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
            'rest_controller_class' => 'WP_REST_Posts_Controller',
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
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ]);
    }

    public function register_taxonomies() {
        $post_types = ['guide_page', 'service_page', 'local_page'];
        foreach ($post_types as $pt) {
            register_taxonomy("{$pt}_category", $pt, [
                'label' => 'Categories',
                'hierarchical' => true,
                'show_admin_column' => true,
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
                function() use ($type, $label) {
                    $fields = guide_cms_get_field_templates()[$type] ?? [];
                    echo '<div class="wrap"><h1>' . esc_html($label) . ' Template</h1>';
                    if ($fields) {
                        echo '<table class="widefat"><thead><tr><th>Key</th><th>Label</th><th>Type</th></tr></thead><tbody>';
                        foreach ($fields as $field) {
                            echo '<tr><td>' . esc_html($field['key']) . '</td><td>' . esc_html($field['label']) . '</td><td>' . esc_html($field['type']) . '</td></tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No template fields defined for this type.</p>';
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
                    $fields = guide_cms_get_field_templates()[$type] ?? [];
                    echo '<div class="wrap"><h1>' . esc_html($label) . ' REST API Docs</h1>';
                    echo '<h2>Endpoints</h2>';
                    echo '<ul>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '</code> (list all)</li>';
                    echo '<li><code>GET ' . esc_html($endpoint) . '/{id}</code> (get single)</li>';
                    echo '<li><code>POST ' . esc_html($endpoint) . '</code> (create)</li>';
                    echo '<li><code>POST ' . esc_html($endpoint) . '/{id}</code> (update)</li>';
                    echo '<li><code>DELETE ' . esc_html($endpoint) . '/{id}</code> (delete)</li>';
                    echo '</ul>';
                    echo '<h2>Custom Fields (meta)</h2>';
                    if ($fields) {
                        echo '<table class="widefat"><thead><tr><th>Key</th><th>Type</th></tr></thead><tbody>';
                        foreach ($fields as $field) {
                            echo '<tr><td>' . esc_html($field['key']) . '</td><td>' . esc_html($field['type']) . '</td></tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No custom fields defined for this type.</p>';
                    }
                    echo '<h2>Example: Create</h2>';
                    echo '<pre>{
  "title": "Sample ' . esc_html($label) . '",
  "status": "publish",
  "meta": {
';
                    foreach ($fields as $field) {
                        echo '    "' . esc_html($field['key']) . '": "...",
';
                    }
                    echo '  }
}</pre>';
                    echo '<h2>Categories</h2>';
                    echo '<p>Taxonomy: <code>' . esc_html($type) . '_category</code></p>';
                    echo '<p>Assign categories via the <code>' . esc_html($type) . '_category</code> field in POST/PUT.</p>';
                    echo '<h2>Reference</h2>';
                    echo '<ul>';
                    echo '<li><a href="https://developer.wordpress.org/rest-api/" target="_blank">WordPress REST API Handbook</a></li>';
                    echo '<li><a href="https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/" target="_blank">Registering Meta Fields</a></li>';
                    echo '</ul>';
                    echo '</div>';
                }
            );
        }
    }
}

// Initialize the plugin
new Guide_CMS_Plugin();