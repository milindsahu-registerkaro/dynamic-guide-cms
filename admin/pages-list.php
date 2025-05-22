<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Custom CMS Pages</h1>
    <a href="<?php echo admin_url('admin.php?page=custom-cms-new'); ?>" class="page-title-action">Add New</a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1) : ?>
    <div class="notice notice-success is-dismissible">
        <p>Page deleted successfully.</p>
    </div>
    <?php endif; ?>
    
    <div class="notice notice-info is-dismissible">
        <p>This is the custom CMS system. Use this to create and manage structured content pages.</p>
    </div>
    
    <div id="custom-cms-pages-list" class="custom-cms-container">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action-selector-top">
                    <option value="-1">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Set to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="Apply">
            </div>
            <div class="alignleft actions category-filter">
                
                <select id="category-filter-dropdown">
                    <option value="">All Categories</option>
                    <!-- Categories will be loaded here by JS -->
                </select>
                <label for="category-filter-dropdown" style="font-weight:600;">Category:</label>
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num" id="items-count">0 items</span>
                <span class="pagination-links" id="pagination-links">
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span class="total-pages" id="total-pages">1</span></span>
                    </span>
                    <a class="next-page button" id="next-page">
                        <span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span>
                    </a>
                    <a class="last-page button" id="last-page">
                        <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>
                    </a>
                </span>
            </div>
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-title column-primary">Title</th>
                    <th scope="col" class="manage-column">Slug</th>
                    <th scope="col" class="manage-column">Status</th>
                    <th scope="col" class="manage-column">Region</th>
                    <th scope="col" class="manage-column">Service</th>
                    <th scope="col" class="manage-column">Created</th>
                    <th scope="col" class="manage-column">Last Updated</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <tr class="no-items">
                    <td class="colspanchange" colspan="8">No pages found.</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-title column-primary">Title</th>
                    <th scope="col" class="manage-column">Slug</th>
                    <th scope="col" class="manage-column">Status</th>
                    <th scope="col" class="manage-column">Region</th>
                    <th scope="col" class="manage-column">Service</th>
                    <th scope="col" class="manage-column">Created</th>
                    <th scope="col" class="manage-column">Last Updated</th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action-selector-bottom">
                    <option value="-1">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Set to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="Apply">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num" id="items-count-bottom">0 items</span>
                <span class="pagination-links" id="pagination-links-bottom">
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                    <span class="paging-input">
                        <label for="current-page-selector-bottom" class="screen-reader-text">Current Page</label>
                        <input class="current-page" id="current-page-selector-bottom" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span class="total-pages" id="total-pages-bottom">1</span></span>
                    </span>
                    <a class="next-page button" id="next-page-bottom">
                        <span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span>
                    </a>
                    <a class="last-page button" id="last-page-bottom">
                        <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>
                    </a>
                </span>
            </div>
            <br class="clear">
        </div>
    </div>
</div>

<!-- Page item template -->
<script type="text/template" id="page-item-template">
    <tr id="page-{{id}}">
        <th scope="row" class="check-column">
            <input type="checkbox" name="page[]" value="{{id}}">
        </th>
        <td class="title column-title column-primary">
            <strong>
                <a class="row-title" href="<?php echo admin_url('admin.php?page=custom-cms-new&id='); ?>{{id}}">{{h1}}</a>
            </strong>
            <div class="row-actions">
                <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=custom-cms-new&id='); ?>{{id}}">Edit</a> | 
                </span>
                <span class="status">
                    {{#if status_draft}}
                    <a href="#" class="publish-page" data-id="{{id}}">Publish</a> | 
                    {{else}}
                    <a href="#" class="unpublish-page" data-id="{{id}}">Unpublish</a> | 
                    {{/if}}
                </span>
                <span class="delete">
                    <a href="#" class="delete-page" data-id="{{id}}">Delete</a>
                </span>
            </div>
        </td>
        <td>{{slug}}</td>
        <td>
            {{#if status_published}}
            <span class="status-published">Published</span>
            {{else}}
            <span class="status-draft">Draft</span>
            {{/if}}
        </td>
        <td>{{region}}</td>
        <td>{{service}}</td>
        <td>{{created}}</td>
        <td>{{updated}}</td>
    </tr>
</script>