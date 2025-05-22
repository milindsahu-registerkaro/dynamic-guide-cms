# Guide CMS Field System Documentation

## Overview

The Guide CMS Field System provides a flexible and scalable way to manage custom fields in your WordPress plugin. It allows you to define, manage, and render different types of fields without modifying the core plugin code.

## Field Types

The system supports the following field types:

1. **Text Input** (`text`)
   - Simple text input field
   - Supports validation, default value, and placeholder

2. **Text Area** (`textarea`)
   - Multi-line text input
   - Supports validation, default value, placeholder, and custom rows

3. **TinyMCE Editor** (`tinymce`)
   - Rich text editor
   - Supports validation, default value, media buttons, and teeny mode

4. **Image Upload** (`image`)
   - Image upload with preview
   - Supports validation, default value, and dimension requirements

5. **Select Dropdown** (`select`)
   - Dropdown selection
   - Supports options, default value, and multiple selection

6. **Checkbox** (`checkbox`)
   - Multiple checkbox selection
   - Supports options and default value

7. **Radio Buttons** (`radio`)
   - Single option selection
   - Supports options and default value

8. **Repeater Field** (`repeater`)
   - Repeatable group of fields
   - Supports sub-fields, minimum and maximum items

9. **Field Group** (`group`)
   - Group of related fields
   - Supports sub-fields

## Adding New Fields

To add a new field, you can use the `Guide_CMS_Field_Definitions` class:

```php
$field_definitions = new Guide_CMS_Field_Definitions();

// Add a text field
$field_definitions->add_field_definition([
    'field_key' => 'my_text_field',
    'field_type' => 'text',
    'field_label' => 'My Text Field',
    'field_description' => 'Enter some text',
    'field_validation' => json_encode([
        'required' => true,
        'min_length' => 3,
        'max_length' => 100
    ]),
    'field_default' => 'Default value',
    'field_group' => 'main',
    'field_order' => 1,
    'is_required' => true
]);

// Add a repeater field
$field_definitions->add_field_definition([
    'field_key' => 'my_repeater',
    'field_type' => 'repeater',
    'field_label' => 'My Repeater',
    'field_description' => 'Add multiple items',
    'field_options' => json_encode([
        'sub_fields' => [
            [
                'field_key' => 'title',
                'field_type' => 'text',
                'field_label' => 'Title',
                'is_required' => true
            ],
            [
                'field_key' => 'description',
                'field_type' => 'textarea',
                'field_label' => 'Description'
            ]
        ],
        'min' => 1,
        'max' => 5
    ]),
    'field_group' => 'main',
    'field_order' => 2
]);
```

## Field Groups

Fields can be organized into groups for better organization. To render fields from a specific group:

```php
$form_generator = new Guide_CMS_Form_Generator();
echo $form_generator->render_form('main', $values);
```

## Field Validation

Each field can have its own validation rules. The validation rules are stored as JSON in the `field_validation` column:

```php
$validation = [
    'required' => true,
    'min_length' => 3,
    'max_length' => 100,
    'pattern' => '/^[a-zA-Z0-9]+$/',
    'custom' => 'my_custom_validation_function'
];
```

## Field Options

Field-specific options are stored as JSON in the `field_options` column:

```php
// Select field options
$options = [
    'choices' => [
        'option1' => 'Option 1',
        'option2' => 'Option 2',
        'option3' => 'Option 3'
    ],
    'multiple' => true
];

// TinyMCE options
$options = [
    'media_buttons' => true,
    'teeny' => false,
    'textarea_rows' => 10
];
```

## Versioning

Each field has a version number that can be used to track changes. When updating a field, you can increment the version:

```php
$field_definitions->update_field_definition('my_field', [
    'field_label' => 'New Label',
    'field_version' => '1.0.1'
]);
```

## Backward Compatibility

The system maintains backward compatibility by:

1. Preserving field data in the database
2. Supporting field versioning
3. Providing default values for missing fields
4. Handling deprecated field types

## Best Practices

1. **Field Keys**: Use unique, descriptive keys for your fields
2. **Field Groups**: Organize fields into logical groups
3. **Validation**: Always validate field data before saving
4. **Versioning**: Increment field versions when making changes
5. **Documentation**: Document your field structure and changes

## Example Usage

Here's a complete example of creating a custom form:

```php
// Initialize the field definitions
$field_definitions = new Guide_CMS_Field_Definitions();

// Add fields
$field_definitions->add_field_definition([
    'field_key' => 'title',
    'field_type' => 'text',
    'field_label' => 'Title',
    'is_required' => true
]);

$field_definitions->add_field_definition([
    'field_key' => 'content',
    'field_type' => 'tinymce',
    'field_label' => 'Content',
    'field_options' => json_encode([
        'media_buttons' => true,
        'teeny' => false
    ])
]);

$field_definitions->add_field_definition([
    'field_key' => 'gallery',
    'field_type' => 'repeater',
    'field_label' => 'Gallery',
    'field_options' => json_encode([
        'sub_fields' => [
            [
                'field_key' => 'image',
                'field_type' => 'image',
                'field_label' => 'Image'
            ],
            [
                'field_key' => 'caption',
                'field_type' => 'text',
                'field_label' => 'Caption'
            ]
        ]
    ])
]);

// Render the form
$form_generator = new Guide_CMS_Form_Generator();
echo $form_generator->render_form('main', $values);
```

## Troubleshooting

1. **Field Not Showing**: Check if the field is active and in the correct group
2. **Validation Errors**: Verify the validation rules and field requirements
3. **Data Not Saving**: Ensure the field key matches the database column
4. **JavaScript Errors**: Check the browser console for any JavaScript issues

## Support

For support or feature requests, please contact the plugin author or submit an issue on the GitHub repository. 