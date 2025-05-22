<?php
if (!defined('ABSPATH')) {
    exit;
}

class Guide_CMS_API_Handler {
    private $page_manager;
    private $template_manager;
    private $namespace = 'guide-cms/v1';

    public function __construct() {
        $this->page_manager = new Guide_CMS_Page_Manager();
        $this->template_manager = new Guide_CMS_Template_Manager();
        
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Pages endpoints
        register_rest_route($this->namespace, '/pages', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_pages'],
                'permission_callback' => [$this, 'get_pages_permissions_check'],
                'args' => [
                    'template_key' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'category_id' => [
                        'required' => false,
                        'type' => 'integer'
                    ],
                    'status' => [
                        'required' => false,
                        'type' => 'string',
                        'enum' => ['draft', 'publish']
                    ],
                    'search' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'page' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 1
                    ],
                    'per_page' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 10
                    ],
                    'orderby' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'created'
                    ],
                    'order' => [
                        'required' => false,
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'default' => 'desc'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_page'],
                'permission_callback' => [$this, 'create_page_permissions_check'],
                'args' => [
                    'template_key' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'title' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'slug' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'content' => [
                        'required' => true,
                        'type' => 'object'
                    ],
                    'category_id' => [
                        'required' => false,
                        'type' => 'integer'
                    ],
                    'status' => [
                        'required' => false,
                        'type' => 'string',
                        'enum' => ['draft', 'publish'],
                        'default' => 'draft'
                    ],
                    'meta_data' => [
                        'required' => false,
                        'type' => 'object',
                        'default' => []
                    ]
                ]
            ]
        ]);

        register_rest_route($this->namespace, '/pages/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_page'],
                'permission_callback' => [$this, 'get_page_permissions_check'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_page'],
                'permission_callback' => [$this, 'update_page_permissions_check'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer'
                    ],
                    'title' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'slug' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'content' => [
                        'required' => false,
                        'type' => 'object'
                    ],
                    'category_id' => [
                        'required' => false,
                        'type' => 'integer'
                    ],
                    'status' => [
                        'required' => false,
                        'type' => 'string',
                        'enum' => ['draft', 'publish']
                    ],
                    'meta_data' => [
                        'required' => false,
                        'type' => 'object'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_page'],
                'permission_callback' => [$this, 'delete_page_permissions_check'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer'
                    ]
                ]
            ]
        ]);

        // Templates endpoints
        register_rest_route($this->namespace, '/templates', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_templates'],
                'permission_callback' => [$this, 'get_templates_permissions_check']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_template'],
                'permission_callback' => [$this, 'create_template_permissions_check'],
                'args' => [
                    'template_key' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'template_name' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'template_description' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'template_fields' => [
                        'required' => true,
                        'type' => 'object'
                    ]
                ]
            ]
        ]);

        register_rest_route($this->namespace, '/templates/(?P<key>[a-zA-Z0-9_-]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_template'],
                'permission_callback' => [$this, 'get_template_permissions_check'],
                'args' => [
                    'key' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_template'],
                'permission_callback' => [$this, 'update_template_permissions_check'],
                'args' => [
                    'key' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'template_name' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'template_description' => [
                        'required' => false,
                        'type' => 'string'
                    ],
                    'template_fields' => [
                        'required' => false,
                        'type' => 'object'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_template'],
                'permission_callback' => [$this, 'delete_template_permissions_check'],
                'args' => [
                    'key' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]
        ]);
    }

    // Permission checks
    public function get_pages_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function create_page_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function get_page_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function update_page_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function delete_page_permissions_check($request) {
        return current_user_can('delete_posts');
    }

    public function get_templates_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function create_template_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function get_template_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    public function update_template_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function delete_template_permissions_check($request) {
        return current_user_can('manage_options');
    }

    // Page endpoints
    public function get_pages($request) {
        $args = [
            'template_key' => $request->get_param('template_key') !== '' ? $request->get_param('template_key') : null,
            'category_id' => $request->get_param('category_id') !== '' ? $request->get_param('category_id') : null,
            'status' => $request->get_param('status') !== '' ? $request->get_param('status') : null,
            'search' => $request->get_param('search') !== '' ? $request->get_param('search') : null,
            'orderby' => $request->get_param('orderby'),
            'order' => $request->get_param('order'),
            'limit' => $request->get_param('per_page'),
            'offset' => ($request->get_param('page') - 1) * $request->get_param('per_page')
        ];

        $pages = $this->page_manager->get_pages($args);
        $total = $this->page_manager->get_total_pages($args);

        return new WP_REST_Response([
            'pages' => $pages,
            'total' => $total,
            'total_pages' => ceil($total / $request->get_param('per_page'))
        ]);
    }

    public function create_page($request) {
        $data = [
            'template_key' => $request->get_param('template_key'),
            'title' => $request->get_param('title'),
            'slug' => $request->get_param('slug'),
            'content' => $request->get_param('content'),
            'category_id' => $request->get_param('category_id'),
            'status' => $request->get_param('status'),
            'meta_data' => $request->get_param('meta_data')
        ];

        $result = $this->page_manager->create_page($data);

        if ($result === false) {
            return new WP_Error(
                'create_failed',
                'Failed to create page',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Page created successfully',
            'page_id' => $result
        ], 201);
    }

    public function get_page($request) {
        $page = $this->page_manager->get_page($request->get_param('id'));

        if (!$page) {
            return new WP_Error(
                'not_found',
                'Page not found',
                ['status' => 404]
            );
        }

        return new WP_REST_Response($page);
    }

    public function update_page($request) {
        $data = [
            'title' => $request->get_param('title'),
            'slug' => $request->get_param('slug'),
            'content' => $request->get_param('content'),
            'category_id' => $request->get_param('category_id'),
            'status' => $request->get_param('status'),
            'meta_data' => $request->get_param('meta_data')
        ];

        $result = $this->page_manager->update_page($request->get_param('id'), $data);

        if ($result === false) {
            return new WP_Error(
                'update_failed',
                'Failed to update page',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Page updated successfully'
        ]);
    }

    public function delete_page($request) {
        $result = $this->page_manager->delete_page($request->get_param('id'));

        if ($result === false) {
            return new WP_Error(
                'delete_failed',
                'Failed to delete page',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Page deleted successfully'
        ]);
    }

    // Template endpoints
    public function get_templates($request) {
        $templates = $this->template_manager->get_templates();
        return new WP_REST_Response($templates);
    }

    public function create_template($request) {
        $data = [
            'template_key' => $request->get_param('template_key'),
            'template_name' => $request->get_param('template_name'),
            'template_description' => $request->get_param('template_description'),
            'template_fields' => $request->get_param('template_fields')
        ];

        $result = $this->template_manager->create_template($data);

        if ($result === false) {
            return new WP_Error(
                'create_failed',
                'Failed to create template',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Template created successfully',
            'template_key' => $result
        ], 201);
    }

    public function get_template($request) {
        $template = $this->template_manager->get_template($request->get_param('key'));

        if (!$template) {
            return new WP_Error(
                'not_found',
                'Template not found',
                ['status' => 404]
            );
        }

        return new WP_REST_Response($template);
    }

    public function update_template($request) {
        $data = [
            'template_name' => $request->get_param('template_name'),
            'template_description' => $request->get_param('template_description'),
            'template_fields' => $request->get_param('template_fields')
        ];

        $result = $this->template_manager->update_template($request->get_param('key'), $data);

        if ($result === false) {
            return new WP_Error(
                'update_failed',
                'Failed to update template',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Template updated successfully'
        ]);
    }

    public function delete_template($request) {
        $result = $this->template_manager->delete_template($request->get_param('key'));

        if ($result === false) {
            return new WP_Error(
                'delete_failed',
                'Failed to delete template',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'message' => 'Template deleted successfully'
        ]);
    }
} 