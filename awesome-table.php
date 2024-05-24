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

function my_awesome_plugin_create_table(): void {
    global $wpdb;
    $table_name = $wpdb?->prefix . 'awesome_table';

    $charset_collate = $wpdb?->get_charset_collate();

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    PRIMARY KEY  (id)
) $charset_collate;
SQL;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function my_awesome_plugin_insert_subscription(string $name, string $email): void {
    global $wpdb;
    $table_name = $wpdb?->prefix . 'awesome_table';

    $wpdb?->insert(
        $table_name,
        [
            'name' => $name,
            'email' => $email,
        ]
    );
}

function my_awesome_plugin_subscription_form(): string {
    $submited = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['my_awesome_subscription_form_submitted'])) {
        die('ppp');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        my_awesome_plugin_insert_subscription($name, $email);

        $submited = '<div class="updated"><p>Subscription submitted</p></div>';
    }

    ob_start();
    ?>
    <form method="post" action="" >
        <p>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </p>
        <p>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </p>
        <p>
            <input type="submit" name="my_awesome_subscription_form_submitted" value="Subscribe" class="button button-primary">
        </p>
    </form>
    <?php if (!empty($submited)): echo $submited; endif;
    return ob_get_clean();
}

add_shortcode('subscription_form', 'my_awesome_plugin_subscription_form');

function awesome_table_init(): void {
    my_awesome_plugin_create_table();
}

register_activation_hook(__FILE__, 'awesome_table_init');