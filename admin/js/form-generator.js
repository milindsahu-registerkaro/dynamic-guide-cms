jQuery(document).ready(function($) {
    // Initialize form
    initForm();
    
    function initForm() {
        // Initialize image uploaders
        initImageUploaders();
        
        // Initialize repeater fields
        initRepeaterFields();
        
        // Initialize sortable fields
        initSortableFields();
    }
    
    function initImageUploaders() {
        $('.guide-cms-field-image').each(function() {
            var $field = $(this);
            var $preview = $field.find('.image-preview-wrapper');
            var $input = $field.find('input[type="hidden"]');
            var $selectButton = $field.find('.select-image');
            var $removeButton = $field.find('.remove-image');
            
            // Show/hide remove button based on image presence
            updateImageButtons();
            
            // Handle image selection
            $selectButton.on('click', function(e) {
                e.preventDefault();
                
                var frame = wp.media({
                    title: 'Select Image',
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url);
                    $preview.html('<img src="' + attachment.url + '" style="max-width: 300px;">');
                    updateImageButtons();
                });
                
                frame.open();
            });
            
            // Handle image removal
            $removeButton.on('click', function(e) {
                e.preventDefault();
                $input.val('');
                $preview.empty();
                updateImageButtons();
            });
            
            function updateImageButtons() {
                if ($input.val()) {
                    $selectButton.hide();
                    $removeButton.show();
                } else {
                    $selectButton.show();
                    $removeButton.hide();
                }
            }
        });
    }
    
    function initRepeaterFields() {
        // Add new repeater item
        $('.add-repeater-item').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $wrapper = $button.closest('.repeater-wrapper');
            var $items = $wrapper.find('.repeater-items');
            var fieldKey = $button.data('field-key');
            var index = $items.children().length;
            
            // Get template from server
            $.ajax({
                url: guideCmsForm.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_repeater_template',
                    nonce: guideCmsForm.nonce,
                    field_key: fieldKey,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        $items.append(response.data.html);
                        initForm(); // Reinitialize form elements
                    }
                }
            });
        });
        
        // Remove repeater item
        $(document).on('click', '.remove-repeater-item', function(e) {
            e.preventDefault();
            $(this).closest('.repeater-item').remove();
            updateRepeaterIndexes();
        });
    }
    
    function initSortableFields() {
        $('.repeater-items').sortable({
            handle: '.repeater-item-header',
            update: function() {
                updateRepeaterIndexes();
            }
        });
    }
    
    function updateRepeaterIndexes() {
        $('.repeater-items').each(function() {
            var $items = $(this).children();
            $items.each(function(index) {
                var $item = $(this);
                var $title = $item.find('.repeater-item-title');
                var $inputs = $item.find('input, select, textarea');
                
                // Update title
                $title.text('Item ' + (index + 1));
                
                // Update input names
                $inputs.each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        });
    }
    
    // Form submission
    $('#custom-cms-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var formData = new FormData(this);
        
        // Add action and nonce
        formData.append('action', 'save_custom_cms_page');
        formData.append('nonce', guideCmsForm.nonce);
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: guideCmsForm.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    var $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                    $form.before($notice);
                    
                    // Redirect if needed
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    // Show error message
                    var $notice = $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                    $form.before($notice);
                }
            },
            error: function() {
                // Show error message
                var $notice = $('<div class="notice notice-error is-dismissible"><p>An error occurred while saving the page.</p></div>');
                $form.before($notice);
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
}); 