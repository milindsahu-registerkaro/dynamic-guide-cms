<?php
if (!defined('ABSPATH')) {
    exit;
}

class Guide_CMS_Field_Definitions {
    const TABLE = 'guide_cms_field_definitions';
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
            field_key VARCHAR(191) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            field_label VARCHAR(255) NOT NULL,
            field_description TEXT DEFAULT NULL,
            field_options LONGTEXT DEFAULT NULL,
            field_validation LONGTEXT DEFAULT NULL,
            field_default TEXT DEFAULT NULL,
            field_group VARCHAR(100) DEFAULT 'main',
            field_order INT DEFAULT 0,
            field_version VARCHAR(20) DEFAULT '1.0.0',
            is_required TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY field_key (field_key),
            KEY field_type (field_type),
            KEY field_group (field_group)
        ) $charset_collate;";

        dbDelta($sql);
        update_option('guide_cms_field_definitions_version', self::VERSION);
    }

    public function get_field_definitions($group = null) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $where = $group ? $wpdb->prepare('WHERE field_group = %s AND is_active = 1', $group) : 'WHERE is_active = 1';
        $sql = "SELECT * FROM $table $where ORDER BY field_order ASC";
        
        return $wpdb->get_results($sql);
    }

    public function add_field_definition($data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $defaults = [
            'field_type' => 'text',
            'field_options' => '[]',
            'field_validation' => '[]',
            'field_default' => '',
            'field_group' => 'main',
            'field_order' => 0,
            'field_version' => '1.0.0',
            'is_required' => 0,
            'is_active' => 1
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert($table, $data);
    }

    public function update_field_definition($field_key, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        return $wpdb->update(
            $table,
            $data,
            ['field_key' => $field_key]
        );
    }

    public function delete_field_definition($field_key) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        return $wpdb->delete($table, ['field_key' => $field_key]);
    }

    public function get_field_types() {
        return [
            'text' => [
                'label' => 'Text Input',
                'supports' => ['validation', 'default', 'placeholder']
            ],
            'textarea' => [
                'label' => 'Text Area',
                'supports' => ['validation', 'default', 'placeholder', 'rows']
            ],
            'tinymce' => [
                'label' => 'TinyMCE Editor',
                'supports' => ['validation', 'default', 'media_buttons', 'teeny']
            ],
            'image' => [
                'label' => 'Image Upload',
                'supports' => ['validation', 'default', 'dimensions']
            ],
            'select' => [
                'label' => 'Select Dropdown',
                'supports' => ['options', 'default', 'multiple']
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'supports' => ['default', 'options']
            ],
            'radio' => [
                'label' => 'Radio Buttons',
                'supports' => ['options', 'default']
            ],
            'repeater' => [
                'label' => 'Repeater Field',
                'supports' => ['sub_fields', 'min', 'max']
            ],
            'group' => [
                'label' => 'Field Group',
                'supports' => ['sub_fields']
            ]
        ];
    }
} 