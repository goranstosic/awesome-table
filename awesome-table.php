<?php
/*
Plugin Name: Awesome Table
Plugin URI: http://example.com/
Description: A brief description of what the plugin does.
Version: 1.0
Author: Goran Stošić
Author URI: http://example.com/
License: GPL2
*/

global $my_awesome_table_version;
$my_awesome_table_version = '1.0';

function create_database_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'form_data';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

function handle_form_submission() {
    global $wpdb;

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    create_database_table();

    $table_name = $wpdb->prefix . 'form_data';

    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
        )
    );

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}

add_action('admin_post_handle_form_submission', 'handle_form_submission');
add_action('admin_post_nopriv_handle_form_submission', 'handle_form_submission');

function my_form_shortcode() {
    ob_start(); ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="handle_form_submission">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required><br>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required><br>
        <input type="submit" value="Submit">
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('my_form', 'my_form_shortcode');

function display_form_data($atts) {
    global $wpdb;

    $search='';
    if (!empty($_REQUEST['search'])) {

        $search = sanitize_text_field($_REQUEST['search']);
    }

    $table_name = $wpdb->prefix . 'form_data';

    $sql = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $sql .= $wpdb->prepare(" WHERE name LIKE '%s' OR email LIKE '%s'", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    }
    $results = $wpdb->get_results($sql);

    ob_start(); ?>

    <div>
        <form method="get" action="">
            <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search...">
            <input type="submit" value="Search">
        </form>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
            </tr>
            <?php if ($results) : ?>
                <?php foreach ($results as $result) : ?>
                    <tr>
                        <td><?php echo esc_html($result->name); ?></td>
                        <td><?php echo esc_html($result->email); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2">No results found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('display_form_data', 'display_form_data');

class My_Form_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('awesome-table/v1', '/insert', array(
            'methods' => 'POST',
            'callback' => array($this, 'insert_data'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('awesome-table/v1', '/select', array(
            'methods' => 'GET',
            'callback' => array($this, 'select_data'),
            'permission_callback' => '__return_true',
        ));
    }

    public function insert_data(WP_REST_Request $request) {
        global $wpdb;

        $name = sanitize_text_field($request->get_param('name'));
        $email = sanitize_email($request->get_param('email'));

        // Create the database table if it doesn't exist
        create_database_table();

        // Insert data
        $table_name = $wpdb->prefix . 'form_data';
        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
            )
        );

        return new WP_REST_Response(array('message' => 'Data inserted successfully'), 200);
    }

    public function select_data(WP_REST_Request $request) {
        global $wpdb;

        $search = sanitize_text_field($request->get_param('search'));

        // Query data
        $table_name = $wpdb->prefix . 'form_data';
        $sql = "SELECT * FROM $table_name";
        if (!empty($search)) {
            $sql .= $wpdb->prepare(" WHERE name LIKE %s OR email LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }
        $results = $wpdb->get_results($sql);

        return new WP_REST_Response($results, 200);
    }
}

new My_Form_API();
