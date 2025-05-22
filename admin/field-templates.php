<?php
if (!defined('ABSPATH')) exit;
require_once dirname(__DIR__) . '/includes/field-template-manager.php';

class Guide_CMS_Field_Templates_Admin {
    private $manager;
    private $post_types;

    public function __construct() {
        $this->manager = new Guide_CMS_Field_Template_Manager();
        $this->post_types = [
            'guide_page' => 'Guide Pages',
            'service_page' => 'Service Pages',
            'local_page' => 'Local Pages',
        ];
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_guide_cms_add_field', [$this, 'handle_add_field']);
        add_action('admin_post_guide_cms_delete_field', [$this, 'handle_delete_field']);
        add_action('admin_post_guide_cms_move_field', [$this, 'handle_move_field']);
        add_action('admin_post_guide_cms_edit_field', [$this, 'handle_edit_field']);
        // (edit/delete handlers can be added similarly)
    }

    public function add_menu() {
        add_menu_page(
            'Field Templates',
            'Field Templates',
            'manage_options',
            'guide-cms-field-templates',
            [$this, 'render_page'],
            'dashicons-feedback',
            80
        );
    }

    public function render_page() {
        $selected = isset($_GET['post_type']) ? $_GET['post_type'] : 'guide_page';
        $fields = $this->manager->get_fields($selected);
        
        // Add admin notices
        if (isset($_GET['message'])) {
            switch ($_GET['message']) {
                case 'deleted':
                    echo '<div class="notice notice-success"><p>Field deleted successfully.</p></div>';
                    break;
                case 'updated':
                    echo '<div class="notice notice-success"><p>Field updated successfully.</p></div>';
                    break;
            }
        }
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'delete_failed':
                    echo '<div class="notice notice-error"><p>Failed to delete field.</p></div>';
                    break;
                case 'update_failed':
                    echo '<div class="notice notice-error"><p>Failed to update field.</p></div>';
                    break;
                case 'field_not_found':
                    echo '<div class="notice notice-error"><p>Field not found.</p></div>';
                    break;
            }
        }

        // Add nonce for delete and move actions
        wp_nonce_field('guide_cms_delete_field', '_wpnonce', false);
        wp_nonce_field('guide_cms_move_field', '_wpnonce', false);

        echo '<div class="wrap"><h1>Field Templates</h1>';
        echo '<form method="get" style="margin-bottom:20px;">';
        echo '<input type="hidden" name="page" value="guide-cms-field-templates">';
        echo '<select name="post_type" onchange="this.form.submit()">';
        foreach ($this->post_types as $pt => $label) {
            echo '<option value="' . esc_attr($pt) . '"' . selected($selected, $pt, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> <input type="submit" class="button" value="Select">';
        echo '</form>';
        echo '<table class="widefat"><thead><tr><th>Order</th><th>Key</th><th>Label</th><th>Type</th><th>Actions</th></tr></thead><tbody>';
        foreach ($fields as $i => $field) {
            echo '<tr>';
            echo '<td>' . intval($field->field_order) . '</td>';
            echo '<td>' . esc_html($field->field_key) . '</td>';
            echo '<td>' . esc_html($field->field_label) . '</td>';
            echo '<td>' . esc_html($field->field_type) . '</td>';
            echo '<td>';
            // Edit button
            echo '<a href="#" class="button button-small edit-field" data-id="' . intval($field->id) . '">Edit</a> ';
            // Delete button
            echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=guide_cms_delete_field&id=' . intval($field->id) . '&post_type=' . esc_attr($selected)), 'guide_cms_delete_field')) . '" class="button button-small delete-field" onclick="return confirm(\'Delete this field?\')">Delete</a> ';
            // Up/Down buttons
            echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=guide_cms_move_field&id=' . intval($field->id) . '&direction=up&post_type=' . esc_attr($selected)), 'guide_cms_move_field')) . '" class="button button-small">↑</a> ';
            echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=guide_cms_move_field&id=' . intval($field->id) . '&direction=down&post_type=' . esc_attr($selected)), 'guide_cms_move_field')) . '" class="button button-small">↓</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        // Add field form
        echo '<h2>Add Field</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('guide_cms_add_field');
        echo '<input type="hidden" name="action" value="guide_cms_add_field">';
        echo '<input type="hidden" name="post_type" value="' . esc_attr($selected) . '">';
        echo '<p><label>Key <input type="text" name="field_key" required></label></p>';
        echo '<p><label>Label <input type="text" name="field_label" required></label></p>';
        echo '<p><label>Type <select name="field_type" id="add-field-type">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="wysiwyg">WYSIWYG</option>
            <option value="select">Select</option>
            <option value="boolean">Boolean</option>
            <option value="image">Image</option>
            <option value="repeater">Repeater</option>
        </select></label></p>';
        echo '<p><label>Order <input type="number" name="field_order" value="0"></label></p>';
        
        // Field Options UI for Add Form
        echo '<div id="add-field-options-ui" style="display:none;">';
        // Select Options UI
        echo '<div id="add-select-options-ui" style="display:none;">';
        echo '<h3>Select Options</h3>';
        echo '<div id="add-select-options-list"></div>';
        echo '<button type="button" class="button add-select-option-add">Add Option</button>';
        echo '</div>';
        
        // Repeater Sub-fields UI
        echo '<div id="add-repeater-fields-ui" style="display:none;">';
        echo '<h3>Sub-fields</h3>';
        echo '<div id="add-repeater-fields-list"></div>';
        echo '<button type="button" class="button add-repeater-field-add">Add Sub-field</button>';
        echo '</div>';
        echo '</div>';
        
        echo '<p><input type="submit" class="button button-primary" value="Add Field"></p>';
        echo '</form>';

        // Add Edit Modal
        echo '<div id="edit-field-modal" style="display:none;" class="modal">';
        echo '<div class="modal-content">';
        echo '<h2>Edit Field</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('guide_cms_edit_field');
        echo '<input type="hidden" name="action" value="guide_cms_edit_field">';
        echo '<input type="hidden" name="field_id" id="edit-field-id">';
        echo '<input type="hidden" name="post_type" value="' . esc_attr($selected) . '">';
        
        echo '<p><label>Key <input type="text" name="field_key" id="edit-field-key" required></label></p>';
        echo '<p><label>Label <input type="text" name="field_label" id="edit-field-label" required></label></p>';
        echo '<p><label>Type <select name="field_type" id="edit-field-type">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="wysiwyg">WYSIWYG</option>
            <option value="select">Select</option>
            <option value="boolean">Boolean</option>
            <option value="image">Image</option>
            <option value="repeater">Repeater</option>
        </select></label></p>';
        echo '<p><label>Order <input type="number" name="field_order" id="edit-field-order" value="0"></label></p>';
        
        // Field Options UI (initially hidden)
        echo '<div id="field-options-ui" style="display:none;">';
        // Select Options UI
        echo '<div id="select-options-ui" style="display:none;">';
        echo '<h3>Select Options</h3>';
        echo '<div id="select-options-list"></div>';
        echo '<button type="button" class="button add-select-option">Add Option</button>';
        echo '</div>';
        
        // Repeater Sub-fields UI
        echo '<div id="repeater-fields-ui" style="display:none;">';
        echo '<h3>Sub-fields</h3>';
        echo '<div id="repeater-fields-list"></div>';
        echo '<button type="button" class="button add-repeater-field">Add Sub-field</button>';
        echo '</div>';
        echo '</div>';
        
        echo '<p><input type="submit" class="button button-primary" value="Update Field"></p>';
        echo '</form>';
        echo '</div>';
        echo '</div>';

        // Add JavaScript for dynamic UI
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Show/hide field options UI based on field type
            function toggleFieldOptions(type) {
                $('#field-options-ui').hide();
                $('#select-options-ui').hide();
                $('#repeater-fields-ui').hide();
                
                if (type === 'select') {
                    $('#field-options-ui, #select-options-ui').show();
                } else if (type === 'repeater') {
                    $('#field-options-ui, #repeater-fields-ui').show();
                }
            }

            // Handle field type change
            $('#edit-field-type').on('change', function() {
                toggleFieldOptions($(this).val());
            });

            // Add select option
            $('.add-select-option').on('click', function() {
                var optionHtml = '<div class="select-option">' +
                    '<input type="text" name="field_options[options][][label]" placeholder="Label" required>' +
                    '<input type="text" name="field_options[options][][value]" placeholder="Value" required>' +
                    '<button type="button" class="button remove-option">Remove</button>' +
                    '</div>';
                $('#select-options-list').append(optionHtml);
            });

            // Remove select option
            $(document).on('click', '.remove-option', function() {
                $(this).closest('.select-option').remove();
            });

            // Add repeater sub-field
            $('.add-repeater-field').on('click', function() {
                var fieldHtml = '<div class="repeater-field">' +
                    '<input type="text" name="field_options[sub_fields][][key]" placeholder="Key" required>' +
                    '<input type="text" name="field_options[sub_fields][][label]" placeholder="Label" required>' +
                    '<select name="field_options[sub_fields][][type]">' +
                    '<option value="text">Text</option>' +
                    '<option value="textarea">Textarea</option>' +
                    '<option value="wysiwyg">WYSIWYG</option>' +
                    '</select>' +
                    '<button type="button" class="button remove-field">Remove</button>' +
                    '</div>';
                $('#repeater-fields-list').append(fieldHtml);
            });

            // Remove repeater sub-field
            $(document).on('click', '.remove-field', function() {
                $(this).closest('.repeater-field').remove();
            });

            // Handle edit button click
            $('.edit-field').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                
                $('#edit-field-id').val(id);
                $('#edit-field-key').val(row.find('td:eq(1)').text());
                $('#edit-field-label').val(row.find('td:eq(2)').text());
                $('#edit-field-type').val(row.find('td:eq(3)').text());
                $('#edit-field-order').val(row.find('td:eq(0)').text());
                
                // Load field options if any
                var field = <?php echo json_encode($fields); ?>.find(f => f.id == id);
                if (field && field.field_options) {
                    var options = JSON.parse(field.field_options);
                    
                    // Clear existing options
                    $('#select-options-list, #repeater-fields-list').empty();
                    
                    if (field.field_type === 'select' && options.options) {
                        options.options.forEach(function(opt) {
                            var optionHtml = '<div class="select-option">' +
                                '<input type="text" name="field_options[options][][label]" value="' + opt.label + '" required>' +
                                '<input type="text" name="field_options[options][][value]" value="' + opt.value + '" required>' +
                                '<button type="button" class="button remove-option">Remove</button>' +
                                '</div>';
                            $('#select-options-list').append(optionHtml);
                        });
                    } else if (field.field_type === 'repeater' && options.sub_fields) {
                        options.sub_fields.forEach(function(sub) {
                            var fieldHtml = '<div class="repeater-field">' +
                                '<input type="text" name="field_options[sub_fields][][key]" value="' + sub.key + '" required>' +
                                '<input type="text" name="field_options[sub_fields][][label]" value="' + sub.label + '" required>' +
                                '<select name="field_options[sub_fields][][type]">' +
                                '<option value="text"' + (sub.type === 'text' ? ' selected' : '') + '>Text</option>' +
                                '<option value="textarea"' + (sub.type === 'textarea' ? ' selected' : '') + '>Textarea</option>' +
                                '<option value="wysiwyg"' + (sub.type === 'wysiwyg' ? ' selected' : '') + '>WYSIWYG</option>' +
                                '</select>' +
                                '<button type="button" class="button remove-field">Remove</button>' +
                                '</div>';
                            $('#repeater-fields-list').append(fieldHtml);
                        });
                    }
                }
                
                toggleFieldOptions(field.field_type);
                $('#edit-field-modal').show();
            });

            // Close modal when clicking outside
            $(window).on('click', function(e) {
                if ($(e.target).is('#edit-field-modal')) {
                    $('#edit-field-modal').hide();
                }
            });
        });
        </script>
        <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 100000;
        }
        .modal-content {
            background: #fff;
            width: 80%;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 4px;
        }
        .select-option, .repeater-field {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .select-option input, .repeater-field input, .repeater-field select {
            margin-right: 10px;
        }
        </style>
        <?php
        echo '</div>';
    }

    public function handle_add_field() {
        check_admin_referer('guide_cms_add_field');
        
        // Prepare field options based on field type
        $field_options = [];
        if ($_POST['field_type'] === 'select' && isset($_POST['field_options']['options'])) {
            $field_options['options'] = array_map(function($opt) {
                return [
                    'label' => sanitize_text_field($opt['label']),
                    'value' => sanitize_text_field($opt['value'])
                ];
            }, $_POST['field_options']['options']);
        } elseif ($_POST['field_type'] === 'repeater' && isset($_POST['field_options']['sub_fields'])) {
            $field_options['sub_fields'] = array_map(function($sub) {
                return [
                    'key' => sanitize_text_field($sub['key']),
                    'label' => sanitize_text_field($sub['label']),
                    'type' => sanitize_text_field($sub['type'])
                ];
            }, $_POST['field_options']['sub_fields']);
        }

        $data = [
            'post_type' => sanitize_text_field($_POST['post_type']),
            'field_key' => sanitize_text_field($_POST['field_key']),
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_type' => sanitize_text_field($_POST['field_type']),
            'field_order' => intval($_POST['field_order']),
            'is_active' => 1,
            'field_options' => json_encode($field_options),
        ];
        
        $this->manager->add_field($data);
        wp_redirect(add_query_arg(['page' => 'guide-cms-field-templates', 'post_type' => $data['post_type']], admin_url('admin.php')));
        exit;
    }

    public function handle_delete_field() {
        check_admin_referer('guide_cms_delete_field');
        $id = intval($_GET['id']);
        $post_type = sanitize_text_field($_GET['post_type']);
        
        if ($this->manager->delete_field($id)) {
            wp_redirect(add_query_arg(
                ['page' => 'guide-cms-field-templates', 'post_type' => $post_type, 'message' => 'deleted'],
                admin_url('admin.php')
            ));
        } else {
            wp_redirect(add_query_arg(
                ['page' => 'guide-cms-field-templates', 'post_type' => $post_type, 'error' => 'delete_failed'],
                admin_url('admin.php')
            ));
        }
        exit;
    }

    public function handle_move_field() {
        check_admin_referer('guide_cms_move_field');
        $id = intval($_GET['id']);
        $direction = sanitize_text_field($_GET['direction']);
        $post_type = sanitize_text_field($_GET['post_type']);
        
        $field = $this->manager->get_field($id);
        if (!$field) {
            wp_redirect(add_query_arg(
                ['page' => 'guide-cms-field-templates', 'post_type' => $post_type, 'error' => 'field_not_found'],
                admin_url('admin.php')
            ));
            exit;
        }

        $fields = $this->manager->get_fields($post_type);
        $current_index = array_search($field, $fields);
        
        if ($direction === 'up' && $current_index > 0) {
            $swap_field = $fields[$current_index - 1];
            $this->manager->update_field($field->id, ['field_order' => $swap_field->field_order]);
            $this->manager->update_field($swap_field->id, ['field_order' => $field->field_order]);
        } elseif ($direction === 'down' && $current_index < count($fields) - 1) {
            $swap_field = $fields[$current_index + 1];
            $this->manager->update_field($field->id, ['field_order' => $swap_field->field_order]);
            $this->manager->update_field($swap_field->id, ['field_order' => $field->field_order]);
        }

        wp_redirect(add_query_arg(
            ['page' => 'guide-cms-field-templates', 'post_type' => $post_type],
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_edit_field() {
        check_admin_referer('guide_cms_edit_field');
        
        $id = intval($_POST['field_id']);
        $data = [
            'field_key' => sanitize_text_field($_POST['field_key']),
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_type' => sanitize_text_field($_POST['field_type']),
            'field_order' => intval($_POST['field_order']),
            'field_options' => isset($_POST['field_options']) ? json_encode($_POST['field_options']) : '{}',
        ];

        if ($this->manager->update_field($id, $data)) {
            wp_redirect(add_query_arg(
                ['page' => 'guide-cms-field-templates', 'post_type' => $_POST['post_type'], 'message' => 'updated'],
                admin_url('admin.php')
            ));
        } else {
            wp_redirect(add_query_arg(
                ['page' => 'guide-cms-field-templates', 'post_type' => $_POST['post_type'], 'error' => 'update_failed'],
                admin_url('admin.php')
            ));
        }
        exit;
    }
}
new Guide_CMS_Field_Templates_Admin(); 