<?php
if (!defined('ABSPATH')) {
    exit;
}

class Guide_CMS_Page_Manager {
    const TABLE = 'guide_cms_pages';
    const VERSION = '1.0.0';

    private $template_manager;

    public function __construct() {
        $this->template_manager = new Guide_CMS_Template_Manager();
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
            slug VARCHAR(191) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            category_id BIGINT(20) UNSIGNED DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            meta_data LONGTEXT DEFAULT NULL,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY template_key (template_key),
            KEY category_id (category_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql);
        update_option('guide_cms_pages_version', self::VERSION);
    }

    public function create_page($data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $defaults = [
            'status' => 'draft',
            'meta_data' => '{}'
        ];

        $data = wp_parse_args($data, $defaults);
        
        // Ensure content and meta_data are JSON
        if (is_array($data['content'])) {
            $data['content'] = json_encode($data['content']);
        }
        if (is_array($data['meta_data'])) {
            $data['meta_data'] = json_encode($data['meta_data']);
        }

        return $wpdb->insert($table, $data);
    }

    public function update_page($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        // Ensure content and meta_data are JSON
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content']);
        }
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            $data['meta_data'] = json_encode($data['meta_data']);
        }

        return $wpdb->update(
            $table,
            $data,
            ['id' => $id]
        );
    }

    public function delete_page($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->delete($table, ['id' => $id]);
    }

    public function get_page($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));

        if ($page) {
            $page->content = json_decode($page->content, true);
            $page->meta_data = json_decode($page->meta_data, true);
        }

        return $page;
    }

    public function get_page_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s",
            $slug
        ));

        if ($page) {
            $page->content = json_decode($page->content, true);
            $page->meta_data = json_decode($page->meta_data, true);
        }

        return $page;
    }

    public function get_pages($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $defaults = [
            'template_key' => null,
            'category_id' => null,
            'status' => 'publish',
            'search' => null,
            'orderby' => 'created',
            'order' => 'DESC',
            'limit' => 10,
            'offset' => 0
        ];

        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $values = [];

        if ($args['template_key']) {
            $where[] = 'template_key = %s';
            $values[] = $args['template_key'];
        }

        if ($args['category_id']) {
            $where[] = 'category_id = %d';
            $values[] = $args['category_id'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['search']) {
            $where[] = '(title LIKE %s OR content LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }

        $where = implode(' AND ', $where);
        
        if (!empty($values)) {
            $where = $wpdb->prepare($where, $values);
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        $pages = $wpdb->get_results($sql);

        foreach ($pages as &$page) {
            $page->content = json_decode($page->content, true);
            $page->meta_data = json_decode($page->meta_data, true);
        }

        return $pages;
    }

    public function get_total_pages($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $defaults = [
            'template_key' => null,
            'category_id' => null,
            'status' => 'publish',
            'search' => null
        ];

        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $values = [];

        if ($args['template_key']) {
            $where[] = 'template_key = %s';
            $values[] = $args['template_key'];
        }

        if ($args['category_id']) {
            $where[] = 'category_id = %d';
            $values[] = $args['category_id'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['search']) {
            $where[] = '(title LIKE %s OR content LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }

        $where = implode(' AND ', $where);
        
        if (!empty($values)) {
            $where = $wpdb->prepare($where, $values);
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        
        return (int) $wpdb->get_var($sql);
    }

    public function update_page_status($id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        
        $data = ['status' => $status];
        
        if ($status === 'publish') {
            $data['published'] = current_time('mysql');
        }
        
        return $wpdb->update(
            $table,
            $data,
            ['id' => $id]
        );
    }
} 