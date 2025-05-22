<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Categories</h1>
    <a href="javascript:void(0);" class="page-title-action" id="add-new-category">Add New</a>
    
    <hr class="wp-header-end">
    
    <!-- Notification area for operation results -->
    <div id="category-notification" class="notice" style="display: none;"></div>
    
    <div id="category-list-container" class="custom-cms-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary">Name</th>
                    <th scope="col" class="manage-column">Slug</th>
                    <th scope="col" class="manage-column">Description</th>
                    <th scope="col" class="manage-column">Parent</th>
                    <th scope="col" class="manage-column">Pages</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody id="the-category-list">
                <tr class="no-items">
                    <td class="colspanchange" colspan="5">No categories found.</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-primary">Name</th>
                    <th scope="col" class="manage-column">Slug</th>
                    <th scope="col" class="manage-column">Description</th>
                    <th scope="col" class="manage-column">Parent</th>
                    <th scope="col" class="manage-column">Pages</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Category Form Modal -->
<div id="category-modal" class="csp-modal">
    <div class="csp-modal-content">
        <span class="csp-close">&times;</span>
        <h2 id="category-modal-title">Add New Category</h2>
        <form id="category-form">
            <input type="hidden" id="category-id" name="id" value="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="category-name">Name</label></th>
                    <td><input type="text" id="category-name" name="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="category-slug">Slug</label></th>
                    <td>
                        <input type="text" id="category-slug" name="slug" class="regular-text" required>
                        <p class="description">The slug will be used in URLs. Example: my-category</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="category-description">Description</label></th>
                    <td><textarea id="category-description" name="description" rows="5" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="category-parent">Parent</label></th>
                    <td>
                        <select id="category-parent" name="parent_id" class="regular-text">
                            <option value="">None</option>
                            <!-- Categories will be loaded here -->
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" id="save-category" class="button button-primary">Save Category</button>
                <button type="button" id="cancel-category" class="button">Cancel</button>
            </p>
        </form>
    </div>
</div>

<!-- Category item template - IMPORTANT for Handlebars to find it -->
<script type="text/x-handlebars-template" id="category-item-template">
    <tr id="category-{{id}}">
        <td class="column-primary">
            <strong>{{name}}</strong>
        </td>
        <td>{{slug}}</td>
        <td>{{description}}</td>
        <td>{{parent_name}}</td>
        <td>{{page_count}}</td>
        <td>
            <a href="javascript:void(0);" class="edit-category" data-id="{{id}}">Edit</a> | 
            <a href="javascript:void(0);" class="delete-category" data-id="{{id}}">Delete</a>
        </td>
    </tr>
</script>

<!-- Inline JavaScript to ensure the modal works without relying on external JS -->
<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Direct Modal controls
        $("#add-new-category").on('click', function(e) {
            e.preventDefault();
            $("#category-modal-title").text("Add New Category");
            $("#category-form")[0].reset();
            $("#category-id").val("");
            $("#category-modal").css('display', 'block');
        });
        
        $(".csp-close, #cancel-category").on('click', function() {
            $("#category-modal").css('display', 'none');
        });
        
        // Close the modal when clicking outside of it
        $(window).on('click', function(e) {
            if ($(e.target).is('.csp-modal')) {
                $("#category-modal").css('display', 'none');
            }
        });
    });
</script>