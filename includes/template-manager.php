<?php
if (!defined('ABSPATH')) {
    exit;
}

class Guide_CMS_Template_Manager {
    const TABLE = 'guide_cms_templates';
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'update_database_schema']);
    }

    public function update_database_schema() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_key VARCHAR(191) NOT NULL,
            template_name VARCHAR(255) NOT NULL,
            template_description TEXT DEFAULT NULL,
            template_fields LONGTEXT NOT NULL,
            template_version VARCHAR(20) DEFAULT '1.0.0',
            is_active TINYINT(1) DEFAULT 1,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_key (template_key)
        ) $charset_collate;";

        dbDelta($sql);
        update_option('guide_cms_templates_version', self::VERSION);
    }

    public function create_template($data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        // Check if template already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE template_key = %s",
            $data['template_key']
        ));

        if ($existing) {
            // Update existing template instead
            return $this->update_template($data['template_key'], $data);
        }

        $defaults = [
            'template_version' => '1.0.0',
            'is_active' => 1
        ];

        $data = wp_parse_args($data, $defaults);
        
        // Ensure template_fields is JSON
        if (is_array($data['template_fields'])) {
            $data['template_fields'] = json_encode($data['template_fields']);
        }

        return $wpdb->insert($table, $data);
    }

    public function update_template($template_key, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        // Ensure template_fields is JSON
        if (isset($data['template_fields']) && is_array($data['template_fields'])) {
            $data['template_fields'] = json_encode($data['template_fields']);
        }

        return $wpdb->update(
            $table,
            $data,
            ['template_key' => $template_key]
        );
    }

    public function delete_template($template_key) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->delete($table, ['template_key' => $template_key]);
    }

    public function get_template($template_key) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_key = %s",
            $template_key
        ));

        if ($template) {
            $template->template_fields = json_decode($template->template_fields, true);
        }

        return $template;
    }

    public function get_templates($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $where = $active_only ? 'WHERE is_active = 1' : '';
        $templates = $wpdb->get_results("SELECT * FROM $table $where ORDER BY template_name ASC");

        foreach ($templates as &$template) {
            $template->template_fields = json_decode($template->template_fields, true);
        }

        return $templates;
    }

    public function create_default_templates() {
        // Guide Template
        $this->create_template([
            'template_key' => 'guide',
            'template_name' => 'Guide Page',
            'template_description' => 'Template for guide pages with structured content',
            'template_fields' => [
                [
                    'field_key' => 'meta_title',
                    'field_type' => 'text',
                    'field_label' => 'Meta Title',
                    'field_description' => 'SEO meta title',
                    'is_required' => true
                ],
                [
                    'field_key' => 'meta_description',
                    'field_type' => 'textarea',
                    'field_label' => 'Meta Description',
                    'field_description' => 'SEO meta description',
                    'is_required' => true
                ],
                [
                    'field_key' => 'h1_title',
                    'field_type' => 'text',
                    'field_label' => 'H1 Title',
                    'field_description' => 'Main heading of the page',
                    'is_required' => true
                ],
                [
                    'field_key' => 'intro_text',
                    'field_type' => 'tinymce',
                    'field_label' => 'Introduction',
                    'field_description' => 'Introduction text below the heading',
                    'field_options' => [
                        'media_buttons' => true,
                        'teeny' => false
                    ]
                ],
                [
                    'field_key' => 'sections',
                    'field_type' => 'repeater',
                    'field_label' => 'Content Sections',
                    'field_description' => 'Add multiple content sections',
                    'field_options' => [
                        'sub_fields' => [
                            [
                                'field_key' => 'heading',
                                'field_type' => 'text',
                                'field_label' => 'Section Heading'
                            ],
                            [
                                'field_key' => 'content',
                                'field_type' => 'tinymce',
                                'field_label' => 'Section Content',
                                'field_options' => [
                                    'media_buttons' => true,
                                    'teeny' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Service Template
        $this->create_template([
            'template_key' => 'service',
            'template_name' => 'Service Page',
            'template_description' => 'Template for service pages',
            'template_fields' => [
                [
                    'field_key' => 'meta_title',
                    'field_type' => 'text',
                    'field_label' => 'Meta Title',
                    'field_description' => 'SEO meta title',
                    'is_required' => true
                ],
                [
                    'field_key' => 'meta_description',
                    'field_type' => 'textarea',
                    'field_label' => 'Meta Description',
                    'field_description' => 'SEO meta description',
                    'is_required' => true
                ],
                [
                    'field_key' => 'service_name',
                    'field_type' => 'text',
                    'field_label' => 'Service Name',
                    'field_description' => 'Name of the service',
                    'is_required' => true
                ],
                [
                    'field_key' => 'service_description',
                    'field_type' => 'tinymce',
                    'field_label' => 'Service Description',
                    'field_description' => 'Detailed description of the service',
                    'field_options' => [
                        'media_buttons' => true,
                        'teeny' => false
                    ]
                ],
                [
                    'field_key' => 'features',
                    'field_type' => 'repeater',
                    'field_label' => 'Service Features',
                    'field_description' => 'List of service features',
                    'field_options' => [
                        'sub_fields' => [
                            [
                                'field_key' => 'feature_title',
                                'field_type' => 'text',
                                'field_label' => 'Feature Title'
                            ],
                            [
                                'field_key' => 'feature_description',
                                'field_type' => 'textarea',
                                'field_label' => 'Feature Description'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Local Template
        $this->create_template([
            'template_key' => 'local',
            'template_name' => 'Local Page',
            'template_description' => 'Template for location-specific pages',
            'template_fields' => [
                [
                    'field_key' => 'meta_title',
                    'field_type' => 'text',
                    'field_label' => 'Meta Title',
                    'field_description' => 'SEO meta title',
                    'is_required' => true
                ],
                [
                    'field_key' => 'meta_description',
                    'field_type' => 'textarea',
                    'field_label' => 'Meta Description',
                    'field_description' => 'SEO meta description',
                    'is_required' => true
                ],
                [
                    'field_key' => 'location_name',
                    'field_type' => 'text',
                    'field_label' => 'Location Name',
                    'field_description' => 'Name of the location',
                    'is_required' => true
                ],
                [
                    'field_key' => 'location_description',
                    'field_type' => 'tinymce',
                    'field_label' => 'Location Description',
                    'field_description' => 'Description of the location',
                    'field_options' => [
                        'media_buttons' => true,
                        'teeny' => false
                    ]
                ],
                [
                    'field_key' => 'local_services',
                    'field_type' => 'repeater',
                    'field_label' => 'Local Services',
                    'field_description' => 'Services available in this location',
                    'field_options' => [
                        'sub_fields' => [
                            [
                                'field_key' => 'service_name',
                                'field_type' => 'text',
                                'field_label' => 'Service Name'
                            ],
                            [
                                'field_key' => 'service_details',
                                'field_type' => 'textarea',
                                'field_label' => 'Service Details'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
} 