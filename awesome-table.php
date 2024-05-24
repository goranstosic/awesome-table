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

// Define the shortcode for the form
// Function to create the database table if it doesn't exist
function create_database_table() {
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'form_data';

    // SQL to create table
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include upgrade.php for dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create or upgrade table
    dbDelta($sql);
}

// Function to handle form submission
function handle_form_submission() {
    global $wpdb;

    // Retrieve form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    // Create database table if it doesn't exist
    create_database_table();

    // Table name
    $table_name = $wpdb->prefix . 'form_data';

    // Insert form data into the database
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
        )
    );

    // Redirect back to the page where the form was submitted from
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}

// Hook the form submission handler
add_action('admin_post_handle_form_submission', 'handle_form_submission');
add_action('admin_post_nopriv_handle_form_submission', 'handle_form_submission');

// Register the shortcode for the form
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

// Function to display data from the database table with search functionality
// Function to display data from the database table with search functionality
function display_form_data($atts) {
    global $wpdb;

    $search='';
    if (!empty($_REQUEST['search'])) {

        // Sanitize search keyword
        $search = sanitize_text_field($_REQUEST['search']);
    }

    // Table name
    $table_name = $wpdb->prefix . 'form_data';

    // SQL to query data with search functionality
    $sql = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $sql .= $wpdb->prepare(" WHERE name LIKE '%s' OR email LIKE '%s'", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
    }
    $results = $wpdb->get_results($sql);
    // Display the results
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


