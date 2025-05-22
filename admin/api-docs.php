<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$namespace = Enhanced_CSP_Plugin::NAMESPACE;
$base_url = rest_url($namespace);
?>
<div class="wrap">
    <h1>API Documentation</h1>
    
    <div class="card">
        <h2>Base URL</h2>
        <code><?php echo esc_html($base_url); ?></code>
        <p>All endpoints are accessible under this base URL.</p>
    </div>
    
    <h2>Available Endpoints</h2>
    
    <div class="card">
        <h3>Pages Endpoints</h3>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Endpoint</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/pages</code></td>
                    <td>Get all pages. Supports pagination with <code>page</code> and <code>per_page</code> parameters.</td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/pages/{id_or_slug}</code></td>
                    <td>Get a single page by ID or slug.</td>
                </tr>
                <tr>
                    <td><code>POST</code></td>
                    <td><code>/pages</code></td>
                    <td>Create or update a page. Include <code>id</code> to update an existing page.</td>
                </tr>
                <tr>
                    <td><code>PUT/PATCH</code></td>
                    <td><code>/pages/{id}/status</code></td>
                    <td>Update the status of a page to 'draft' or 'published'.</td>
                </tr>
                <tr>
                    <td><code>DELETE</code></td>
                    <td><code>/pages/{id}</code></td>
                    <td>Delete a page.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h3>Categories Endpoints</h3>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Endpoint</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/categories</code></td>
                    <td>Get all categories.</td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/categories/{id}</code></td>
                    <td>Get a single category by ID.</td>
                </tr>
                <tr>
                    <td><code>POST</code></td>
                    <td><code>/categories</code></td>
                    <td>Create or update a category. Include <code>id</code> to update an existing category.</td>
                </tr>
                <tr>
                    <td><code>DELETE</code></td>
                    <td><code>/categories/{id}</code></td>
                    <td>Delete a category.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <h2>Example Usage</h2>
    
    <div class="card">
        <h3>Get All Pages</h3>
        <pre><code>fetch('<?php echo esc_url($base_url); ?>/pages', {
  method: 'GET',
  headers: {
    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
  }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
    </div>
    
    <div class="card">
        <h3>Create a New Page</h3>
        <pre><code>fetch('<?php echo esc_url($base_url); ?>/pages', {
  method: 'POST',
  headers: {
    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>',
    'Content-Type': 'application/x-www-form-urlencoded'
  },
  body: new URLSearchParams({
    'slug': 'my-page',
    'h1': 'My Page Title',
    'status': 'draft',
    'meta_title': 'My Page | My Site',
    'intro_text': '&lt;p&gt;This is the intro text&lt;/p&gt;'
    // Add other fields as needed
  })
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
    </div>
</div>

<style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 3px;
        margin-bottom: 20px;
        padding: 15px;
    }
    
    .card h2, .card h3 {
        margin-top: 0;
    }
    
    pre {
        background: #f5f5f5;
        padding: 15px;
        overflow-x: auto;
        border-radius: 3px;
    }
    
    code {
        background: #f5f5f5;
        padding: 2px 5px;
        border-radius: 3px;
    }
</style>