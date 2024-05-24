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

function awesome_table_init() {
    echo '<p>My Awesome Table is activated!</p>';
}
add_action('admin_notices', 'awesome_table_init');