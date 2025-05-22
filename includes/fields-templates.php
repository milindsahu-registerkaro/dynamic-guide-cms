<?php
// Field templates for Guide CMS custom post types
function guide_cms_get_field_templates() {
    return [
        'guide_page' => [
            [
                'key' => 'meta_title',
                'label' => 'Meta Title',
                'type' => 'text',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta Description',
                'type' => 'textarea',
            ],
            [
                'key' => 'h1_title',
                'label' => 'H1 Title',
                'type' => 'text',
            ],
            [
                'key' => 'intro_text',
                'label' => 'Introduction',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'show_sidebar',
                'label' => 'Show Sidebar?',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Yes'],
                    ['value' => '0', 'label' => 'No'],
                ],
            ],
            [
                'key' => 'banner_image',
                'label' => 'Banner Image',
                'type' => 'image',
            ],
            [
                'key' => 'sections',
                'label' => 'Content Sections',
                'type' => 'repeater',
                'sub_fields' => [
                    [
                        'key' => 'section_heading',
                        'label' => 'Section Heading',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'section_content',
                        'label' => 'Section Content',
                        'type' => 'wysiwyg',
                    ],
                ],
            ],
        ],
        'service_page' => [
            [
                'key' => 'meta_title',
                'label' => 'Meta Title',
                'type' => 'text',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta Description',
                'type' => 'textarea',
            ],
            [
                'key' => 'service_name',
                'label' => 'Service Name',
                'type' => 'text',
            ],
            [
                'key' => 'service_description',
                'label' => 'Service Description',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'is_featured',
                'label' => 'Is Featured?',
                'type' => 'boolean',
            ],
            [
                'key' => 'banner_image',
                'label' => 'Banner Image',
                'type' => 'image',
            ],
            [
                'key' => 'features',
                'label' => 'Features',
                'type' => 'repeater',
                'sub_fields' => [
                    [
                        'key' => 'feature_title',
                        'label' => 'Feature Title',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'feature_description',
                        'label' => 'Feature Description',
                        'type' => 'textarea',
                    ],
                ],
            ],
        ],
        'local_page' => [
            [
                'key' => 'meta_title',
                'label' => 'Meta Title',
                'type' => 'text',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta Description',
                'type' => 'textarea',
            ],
            [
                'key' => 'location_name',
                'label' => 'Location Name',
                'type' => 'text',
            ],
            [
                'key' => 'location_description',
                'label' => 'Location Description',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'map_embed',
                'label' => 'Map Embed',
                'type' => 'textarea',
            ],
            [
                'key' => 'banner_image',
                'label' => 'Banner Image',
                'type' => 'image',
            ],
            [
                'key' => 'local_services',
                'label' => 'Local Services',
                'type' => 'repeater',
                'sub_fields' => [
                    [
                        'key' => 'service_name',
                        'label' => 'Service Name',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'service_details',
                        'label' => 'Service Details',
                        'type' => 'textarea',
                    ],
                ],
            ],
        ],
    ];
} 