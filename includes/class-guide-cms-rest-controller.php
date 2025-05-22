<?php
if (!defined('ABSPATH')) exit;

class Guide_CMS_REST_Controller extends WP_REST_Posts_Controller {
    public function __construct($post_type) {
        parent::__construct($post_type);
    }

    public function get_item($request) {
        $id = $request['id'];
        error_log('Guide CMS REST: get_item called with id: ' . $id);
        
        if (!is_numeric($id)) {
            // Try to get post by slug
            $posts = get_posts([
                'name' => $id,
                'post_type' => $this->post_type,
                'post_status' => 'publish',
                'numberposts' => 1
            ]);
            
            if (!empty($posts)) {
                $request['id'] = $posts[0]->ID;
                error_log('Guide CMS REST: Found post by slug, ID: ' . $posts[0]->ID);
                return parent::get_item($request);
            }
            
            error_log('Guide CMS REST: No post found by slug: ' . $id);
            return new WP_Error('rest_post_invalid_id', __('Invalid post ID or slug.'), array('status' => 404));
        }
        return parent::get_item($request);
    }

    // For custom route: /wp-json/guide-cms/v1/{post_type}/{id_or_slug}
    public function get_item_by_id_or_slug($request) {
        $id_or_slug = $request['id_or_slug'];
        error_log('Guide CMS REST: get_item_by_id_or_slug called with: ' . $id_or_slug . ' for post_type: ' . $this->post_type);
        
        // First try to get by ID
        if (is_numeric($id_or_slug)) {
            $post = get_post((int)$id_or_slug);
            if ($post && $post->post_type === $this->post_type) {
                error_log('Guide CMS REST: Found post by ID: ' . $post->ID);
                $request['id'] = $post->ID;
                return parent::get_item($request);
            }
            error_log('Guide CMS REST: No post found by ID: ' . $id_or_slug);
        }
        
        // If not found by ID or not numeric, try by slug
        $posts = get_posts([
            'name' => $id_or_slug,
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        
        if (!empty($posts)) {
            error_log('Guide CMS REST: Found post by slug, ID: ' . $posts[0]->ID);
            $request['id'] = $posts[0]->ID;
            return parent::get_item($request);
        }
        
        error_log('Guide CMS REST: No post found by slug: ' . $id_or_slug);
        return new WP_Error('rest_post_invalid_id', __('Invalid post ID or slug.'), array('status' => 404));
    }

    public function get_items($request) {
        error_log('Guide CMS REST: get_items called for post_type: ' . $this->post_type);
        
        // Add support for slug parameter in collection requests
        if (!empty($request['slug'])) {
            $posts = get_posts([
                'name' => $request['slug'],
                'post_type' => $this->post_type,
                'post_status' => 'publish',
                'numberposts' => 1
            ]);
            
            if (!empty($posts)) {
                $request['id'] = $posts[0]->ID;
                return $this->get_item($request);
            }
        }
        
        return parent::get_items($request);
    }

    public function get_item_permissions_check($request) {
        error_log('Guide CMS REST: Checking permissions for post_type: ' . $this->post_type);
        return true; // Allow public access
    }

    public function get_items_permissions_check($request) {
        error_log('Guide CMS REST: Checking collection permissions for post_type: ' . $this->post_type);
        return true; // Allow public access
    }
} 