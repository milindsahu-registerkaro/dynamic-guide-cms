<?php
if (!defined('ABSPATH')) exit;

class Guide_CMS_Field_Template_Manager {
    const TABLE = 'guide_cms_field_templates';
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'migrate']);
    }

    public function migrate() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_type VARCHAR(64) NOT NULL,
            field_key VARCHAR(191) NOT NULL,
            field_label VARCHAR(255) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            field_options LONGTEXT DEFAULT NULL,
            field_order INT(11) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY post_type (post_type),
            KEY field_key (field_key)
        ) $charset_collate;";
        dbDelta($sql);
        update_option('guide_cms_field_templates_version', self::VERSION);
    }

    public function get_fields($post_type) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE post_type = %s AND is_active = 1 ORDER BY field_order ASC, id ASC",
            $post_type
        ));
        foreach ($rows as &$row) {
            $row->field_options = $row->field_options ? json_decode($row->field_options, true) : [];
        }
        return $rows;
    }

    public function add_field($data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if (isset($data['field_options']) && is_array($data['field_options'])) {
            $data['field_options'] = json_encode($data['field_options']);
        }
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public function update_field($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if (isset($data['field_options']) && is_array($data['field_options'])) {
            $data['field_options'] = json_encode($data['field_options']);
        }
        return $wpdb->update($table, $data, ['id' => $id]);
    }

    public function delete_field($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->delete($table, ['id' => $id]);
    }

    public function get_field($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($row) {
            $row->field_options = $row->field_options ? json_decode($row->field_options, true) : [];
        }
        return $row;
    }
} 