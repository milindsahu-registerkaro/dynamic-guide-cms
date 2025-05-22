<?php
if (!defined('ABSPATH')) {
    exit;
}

class Guide_CMS_Form_Generator {
    private $field_definitions;
    
    public function __construct() {
        $this->field_definitions = new Guide_CMS_Field_Definitions();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'guide-cms') === false) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_editor();
        
        wp_enqueue_script(
            'guide-cms-form',
            plugins_url('admin/js/form-generator.js', dirname(__FILE__)),
            ['jquery', 'jquery-ui-sortable'],
            '1.0.0',
            true
        );
        
        wp_localize_script('guide-cms-form', 'guideCmsForm', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('guide-cms-form'),
            'fieldTypes' => $this->field_definitions->get_field_types()
        ]);
    }
    
    public function render_form($group = 'main', $values = []) {
        $fields = $this->field_definitions->get_field_definitions($group);
        
        if (empty($fields)) {
            return '<p>No fields defined for this group.</p>';
        }
        
        $output = '<div class="guide-cms-form">';
        
        foreach ($fields as $field) {
            $output .= $this->render_field($field, $values);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_field($field, $values) {
        $value = isset($values[$field->field_key]) ? $values[$field->field_key] : $field->field_default;
        $required = $field->is_required ? 'required' : '';
        
        $output = sprintf(
            '<div class="guide-cms-field guide-cms-field-%s" data-field-key="%s">',
            esc_attr($field->field_type),
            esc_attr($field->field_key)
        );
        
        $output .= sprintf(
            '<label for="%s">%s%s</label>',
            esc_attr($field->field_key),
            esc_html($field->field_label),
            $field->is_required ? ' <span class="required">*</span>' : ''
        );
        
        switch ($field->field_type) {
            case 'text':
                $output .= $this->render_text_field($field, $value, $required);
                break;
                
            case 'textarea':
                $output .= $this->render_textarea_field($field, $value, $required);
                break;
                
            case 'tinymce':
                $output .= $this->render_tinymce_field($field, $value, $required);
                break;
                
            case 'image':
                $output .= $this->render_image_field($field, $value, $required);
                break;
                
            case 'select':
                $output .= $this->render_select_field($field, $value, $required);
                break;
                
            case 'checkbox':
                $output .= $this->render_checkbox_field($field, $value, $required);
                break;
                
            case 'radio':
                $output .= $this->render_radio_field($field, $value, $required);
                break;
                
            case 'repeater':
                $output .= $this->render_repeater_field($field, $value, $required);
                break;
                
            case 'group':
                $output .= $this->render_group_field($field, $value, $required);
                break;
        }
        
        if ($field->field_description) {
            $output .= sprintf(
                '<p class="description">%s</p>',
                esc_html($field->field_description)
            );
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_text_field($field, $value, $required) {
        return sprintf(
            '<input type="text" id="%s" name="%s" value="%s" class="regular-text" %s>',
            esc_attr($field->field_key),
            esc_attr($field->field_key),
            esc_attr($value),
            $required
        );
    }
    
    private function render_textarea_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $rows = isset($options['rows']) ? $options['rows'] : 5;
        
        return sprintf(
            '<textarea id="%s" name="%s" rows="%d" class="large-text" %s>%s</textarea>',
            esc_attr($field->field_key),
            esc_attr($field->field_key),
            esc_attr($rows),
            $required,
            esc_textarea($value)
        );
    }
    
    private function render_tinymce_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $media_buttons = isset($options['media_buttons']) ? $options['media_buttons'] : true;
        $teeny = isset($options['teeny']) ? $options['teeny'] : false;
        
        ob_start();
        wp_editor($value, $field->field_key, [
            'textarea_name' => $field->field_key,
            'media_buttons' => $media_buttons,
            'teeny' => $teeny,
            'textarea_rows' => 10
        ]);
        return ob_get_clean();
    }
    
    private function render_image_field($field, $value, $required) {
        $output = '<div class="image-preview-wrapper">';
        if ($value) {
            $output .= sprintf(
                '<img src="%s" style="max-width: 300px;">',
                esc_url($value)
            );
        }
        $output .= '</div>';
        
        $output .= sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s" %s>',
            esc_attr($field->field_key),
            esc_attr($field->field_key),
            esc_attr($value),
            $required
        );
        
        $output .= '<button type="button" class="button select-image">Select Image</button>';
        $output .= '<button type="button" class="button remove-image" style="display: none;">Remove Image</button>';
        
        return $output;
    }
    
    private function render_select_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $multiple = isset($options['multiple']) && $options['multiple'];
        
        $output = sprintf(
            '<select id="%s" name="%s%s" %s%s>',
            esc_attr($field->field_key),
            esc_attr($field->field_key),
            $multiple ? '[]' : '',
            $required,
            $multiple ? ' multiple' : ''
        );
        
        if (isset($options['choices'])) {
            foreach ($options['choices'] as $key => $label) {
                $selected = is_array($value) ? in_array($key, $value) : $value == $key;
                $output .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($key),
                    $selected ? ' selected' : '',
                    esc_html($label)
                );
            }
        }
        
        $output .= '</select>';
        
        return $output;
    }
    
    private function render_checkbox_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $output = '';
        
        if (isset($options['choices'])) {
            foreach ($options['choices'] as $key => $label) {
                $checked = is_array($value) ? in_array($key, $value) : $value == $key;
                $output .= sprintf(
                    '<label><input type="checkbox" name="%s[]" value="%s"%s> %s</label><br>',
                    esc_attr($field->field_key),
                    esc_attr($key),
                    $checked ? ' checked' : '',
                    esc_html($label)
                );
            }
        }
        
        return $output;
    }
    
    private function render_radio_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $output = '';
        
        if (isset($options['choices'])) {
            foreach ($options['choices'] as $key => $label) {
                $output .= sprintf(
                    '<label><input type="radio" name="%s" value="%s"%s> %s</label><br>',
                    esc_attr($field->field_key),
                    esc_attr($key),
                    $value == $key ? ' checked' : '',
                    esc_html($label)
                );
            }
        }
        
        return $output;
    }
    
    private function render_repeater_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $sub_fields = isset($options['sub_fields']) ? $options['sub_fields'] : [];
        
        $output = '<div class="repeater-wrapper">';
        $output .= '<div class="repeater-items">';
        
        if (!empty($value)) {
            foreach ($value as $index => $item) {
                $output .= $this->render_repeater_item($field, $sub_fields, $item, $index);
            }
        }
        
        $output .= '</div>';
        $output .= sprintf(
            '<button type="button" class="button add-repeater-item" data-field-key="%s">Add Item</button>',
            esc_attr($field->field_key)
        );
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_repeater_item($field, $sub_fields, $item, $index) {
        $output = '<div class="repeater-item">';
        $output .= '<div class="repeater-item-header">';
        $output .= '<span class="repeater-item-title">Item ' . ($index + 1) . '</span>';
        $output .= '<button type="button" class="button remove-repeater-item">Remove</button>';
        $output .= '</div>';
        
        foreach ($sub_fields as $sub_field) {
            $sub_field->field_key = $field->field_key . '[' . $index . '][' . $sub_field->field_key . ']';
            $value = isset($item[$sub_field->field_key]) ? $item[$sub_field->field_key] : '';
            $output .= $this->render_field($sub_field, [$sub_field->field_key => $value]);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_group_field($field, $value, $required) {
        $options = json_decode($field->field_options, true);
        $sub_fields = isset($options['sub_fields']) ? $options['sub_fields'] : [];
        
        $output = '<div class="group-wrapper">';
        
        foreach ($sub_fields as $sub_field) {
            $sub_field->field_key = $field->field_key . '[' . $sub_field->field_key . ']';
            $sub_value = isset($value[$sub_field->field_key]) ? $value[$sub_field->field_key] : '';
            $output .= $this->render_field($sub_field, [$sub_field->field_key => $sub_value]);
        }
        
        $output .= '</div>';
        
        return $output;
    }
} 