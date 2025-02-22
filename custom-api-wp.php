<?php
/**
 * Plugin Name: WP OOPS API Services
 * Description: Custom plugin to interact with WordPress OOPS API services for managing pages and posts.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WPOOPS_API_Services {
    private $namespace = 'wp-oops/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function register_routes() {
        $routes = [
            'create_page' => 'create-page',
            'update_page' => 'update-page',
            'delete_page' => 'delete-page',
            'create_child_page' => 'create-child-page',
            'update_child_page' => 'update-child-page',
            'get_page' => 'get-page?id={page_id}',
            'get_post' => 'get-post?id={post_id}',
            'get_all_pages' => 'get-all-pages',
            'get_revisions' => 'get-revisions?id={post_id}',
            'set_page_status' => 'set-page-status',
            'create_app_password' => 'create-app-password' // New Endpoint
        ];
        
        foreach ($routes as $function => $route) {
            register_rest_route($this->namespace, "/$route", [
                'methods'  => ['GET', 'POST', 'PUT', 'DELETE'],
                'callback' => [$this, $function],
                'permission_callback' => [$this, 'authenticate_user']
            ]);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP OOPS API',
            'WP OOPS API',
            'manage_options',
            'wp-oops-api',
            [$this, 'api_admin_page'],
            'dashicons-rest-api',
            90
        );
    }

    public function api_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>WP OOPS API Endpoints</h1>';
        echo '<p>Below is the list of available API endpoints and how to use them:</p>';
   
        
        $routes = [
            ["name" => "Create Page", "route" => "{{base_url}}/create-page", "method" => "POST", "body" => ["title" => "Page Title", "content" => "Page Content", "status" => "publish"]],
            ["name" => "Update Page", "route" => "{{base_url}}/update-page", "method" => "PUT", "body" => ["page_id" => 1, "title" => "Updated Title", "content" => "Updated Content"]],
            ["name" => "Delete Page", "route" => "{{base_url}}/delete-page", "method" => "DELETE", "body" => ["page_id" => 1]],
            ["name" => "Create Child Page", "route" => "{{base_url}}/create-child-page", "method" => "POST", "body" => ["title" => "Child Page", "content" => "Content", "parent_id" => 1]],
            ["name" => "Update Child Page", "route" => "{{base_url}}/update-child-page", "method" => "PUT", "body" => ["page_id" => 2, "title" => "Updated Child Title", "content" => "Updated Content"]],
            ["name" => "Get Page", "route" => "{{base_url}}/get-page", "method" => "GET", "body" => ["id" => "{page_id}"]],
            ["name" => "Get Post", "route" => "{{base_url}}/get-post", "method" => "GET", "body" => ["id" => "{post_id}"]],
            ["name" => "Get All Pages", "route" => "{{base_url}}/get-all-pages", "method" => "GET", "body" => null],
            ["name" => "Get Revisions", "route" => "{{base_url}}/get-revisions", "method" => "GET", "body" => ["id" => "{post_id}"]],
            ["name" => "Set Page Status", "route" => "{{base_url}}/set-page-status", "method" => "POST", "body" => ["page_id" => 1, "status" => "pending"]],
            // $username = sanitize_text_field($params['username']);
       // $app_name = sanitize_text_field($params['app_name']);
            ["name" => "Create App Password", "route" => "{{base_url}}/create-app-password", "method" => "POST", "body" => ["username" => "admin", "expiration_days" => 30]]
        ];
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>API Name</th><th>Route</th><th>Method</th><th>Request Body</th></tr></thead><tbody>';
        foreach ($routes as $details) {
            echo '<tr>';
            echo '<td>' . esc_html($details['name']) . '</td>';
            echo '<td><code>' . esc_html($details['route']) . '</code></td>';
            echo '<td>' . esc_html($details['method']) . '</td>';
            echo '<td><pre>' . esc_html(json_encode($details['body'], JSON_PRETTY_PRINT)) . '</pre></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    
        echo '<button id="exportPostman" class="button button-primary">Export Postman Collection</button>';
        echo '<script>
            document.getElementById("exportPostman").addEventListener("click", function() {
                let routes = ' . json_encode($routes, JSON_PRETTY_PRINT) . ';
                let postmanItems = routes.map(route => ({
                    name: route.name,
                    request: {
                        method: route.method,
                        header: [],
                        body: route.method !== "GET" ? {
                            mode: "raw",
                            raw: JSON.stringify(route.body, null, 4)
                        } : undefined,
                        url: {
                            raw: route.route,
                            host: ["{{base_url}}"],
                            path: route.route.replace("{{base_url}}/", "").split("/"),
                            query: route.method === "GET" && route.body ? Object.entries(route.body).map(([key, value]) => ({ key, value })) : []
                        }
                    }
                }));
                
                let postmanData = {
                    info: {
                        name: "WP OOPS API Collection",
                        schema: "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
                    },
                    item: postmanItems
                };
                
                let blob = new Blob([JSON.stringify(postmanData, null, 2)], { type: "application/json" });
                let a = document.createElement("a");
                a.href = URL.createObjectURL(blob);
                a.download = "wp-oops-api-postman.json";
                a.click();
            });
        </script>';
        
        
        echo '</div>';
    }

    public function authenticate_user($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('You are not allowed to access this API.'), ['status' => 401]);
        }
        return true;
    }

    public function create_page(WP_REST_Request $request) {
        return $this->handle_page_creation($request);
    }

    public function update_page(WP_REST_Request $request) {
        return $this->handle_page_update($request);
    }

    public function create_child_page(WP_REST_Request $request) {
        return $this->handle_page_creation($request, true);
    }

    public function update_child_page(WP_REST_Request $request) {
        return $this->handle_page_update($request, true);
    }

    public function get_page(WP_REST_Request $request) {
        $page_id = $request->get_param('id');
        $page = get_post($page_id);

        if (!$page || $page->post_type !== 'page') {
            return new WP_REST_Response(['message' => 'Page not found'], 404);
        }

        return new WP_REST_Response($page, 200);
    }

    public function get_post(WP_REST_Request $request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return new WP_REST_Response(['message' => 'Post not found'], 404);
        }

        return new WP_REST_Response($post, 200);
    }

    public function get_all_pages(WP_REST_Request $request) {
        $pages = get_posts([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        if (empty($pages)) {
            return new WP_REST_Response(['message' => 'No pages found'], 404);
        }

        return new WP_REST_Response($pages, 200);
    }

    private function handle_page_creation(WP_REST_Request $request, $is_child = false) {
        $params = $request->get_json_params();
        
        $page_data = [
            'post_title'   => sanitize_text_field($params['title']),
            'post_content' => wp_kses_post($params['content']),
            'post_status'  => isset($params['status']) ? sanitize_text_field($params['status']) : 'publish',
            'post_type'    => 'page',
            'post_parent'  => $is_child && isset($params['parent_id']) ? intval($params['parent_id']) : 0
        ];

        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            return new WP_REST_Response(['message' => 'Failed to create page'], 400);
        }

        return new WP_REST_Response(['message' => 'Page created', 'page_id' => $page_id], 200);
    }

    private function handle_page_update(WP_REST_Request $request, $is_child = false) {
        $params = $request->get_json_params();
        
        if (!isset($params['page_id'])) {
            return new WP_REST_Response(['message' => 'Page ID is required'], 400);
        }

        $page_data = [
            'ID'           => intval($params['page_id']),
            'post_title'   => isset($params['title']) ? sanitize_text_field($params['title']) : '',
            'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
            'post_status'  => isset($params['status']) ? sanitize_text_field($params['status']) : '',
        ];

        if ($is_child && isset($params['parent_id'])) {
            $page_data['post_parent'] = intval($params['parent_id']);
        }

        $page_id = wp_update_post($page_data);
        
        if (is_wp_error($page_id)) {
            return new WP_REST_Response(['message' => 'Failed to update page'], 400);
        }

        return new WP_REST_Response(['message' => 'Page updated', 'page_id' => $page_id], 200);
    }

    public function delete_page(WP_REST_Request $request) {
        $page_id = $request->get_param('page_id');
        if (wp_delete_post($page_id, true)) {
            return new WP_REST_Response(['message' => 'Page deleted'], 200);
        }
        return new WP_REST_Response(['message' => 'Failed to delete page'], 400);
    }

    public function get_revisions(WP_REST_Request $request) {
        $post_id = $request->get_param('id');
        $revisions = wp_get_post_revisions($post_id);
        return new WP_REST_Response($revisions, 200);
    }

    public function set_page_status(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $page_id = intval($params['page_id']);
        $status = sanitize_text_field($params['status']);

        if (!in_array($status, ['draft', 'publish', 'pending'], true)) {
            return new WP_REST_Response(['message' => 'Invalid status'], 400);
        }

        $update = wp_update_post(['ID' => $page_id, 'post_status' => $status]);
        if (is_wp_error($update)) {
            return new WP_REST_Response(['message' => 'Failed to update status'], 400);
        }

        return new WP_REST_Response(['message' => 'Status updated', 'page_id' => $page_id, 'status' => $status], 200);
    }

    public function create_app_password(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $username = sanitize_text_field($params['username']);
        $app_name = sanitize_text_field($params['app_name']);
    
        $user = get_user_by('login', $username);
        if (!$user) {
            return new WP_REST_Response(['message' => 'User not found'], 404);
        }
    
        $password = WP_Application_Passwords::create_new_application_password($user->ID, ['name' => $app_name]);
        
        if (is_wp_error($password)) {
            return new WP_REST_Response(['message' => 'Failed to generate application password'], 400);
        }
    
        return new WP_REST_Response(['message' => 'Application password created', 'password' => $password], 200);
    }
}

new WPOOPS_API_Services();


add_filter( 'wp_is_application_passwords_available', '__return_true' );