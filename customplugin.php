<?php

/**
 * Plugin Name:       Custom Plugin 
 * Description:       A custom plugin that adds an admin menu page 
 * Version:           1.0.0 
 * Author:            Lewis ushindi
 * Author URI:        https://github.com/lewisushindi 
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *  
 * @category Custom_Plugin
 * @package  Plugin
 * @author   lewis ushindi <coderflame3@gmail.com>
 * @license  GPL-2.0+ 
 * @link     https://github.com/lewisushindi/custom-plugin
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Installs or upgrades the Custom Plugin database table.
 *
 * @return void
 */
function Custom_Plugin_install() 
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_table";
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table exists
    $table_check_sql = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
    $table_check = $wpdb->get_var($table_check_sql);

    if ( $table_check == $table_name ) {
        $sql = $wpdb->prepare( 
            "ALTER TABLE %s 
            ADD COLUMN new_column varchar(200) NOT NULL DEFAULT 'default_value'
            AFTER image_url", $table_name 
        );
        $wpdb->query($sql);
    } else {
        $sql = $wpdb->prepare( 
            "CREATE TABLE %s (
            id medium int(9) NOT NULL AUTO_INCREMENT,
            username varchar(55) NOT NULL,
            image_url varchar(200) NOT NULL,
            PRIMARY KEY  (id)
        ) %s",
            $table_name, $charset_collate 
        );
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    } 

}
register_activation_hook(__FILE__, 'Custom_Plugin_install');

/**
 * Adds the custom plugin settings page to the admin menu.
 *
 * @return void
 */
function Custom_Plugin_menu() 
{
    add_options_page(
        __('Custom Plugin Settings', 'custom-plugin'), 
        __('Custom Plugin', 'custom-plugin'), 
        'manage_options', 
        'custom-plugin', 
        'custom_Plugin_settings_page'
    );
}
add_action('admin_menu', 'Custom_Plugin_menu');


/**
 * Displays the custom plugin settings page.
 *
 * @return void
 */
function Custom_Plugin_Settings_page() 
{
    if (! current_user_can('manage_options') ) {
        wp_die(
            __(
                'You do not have sufficient permissions to access this page.', 
                'custom-plugin'
            )
        );
    }       

    echo '<div class="wrap">';
    echo '<h1>' . __('Custom Plugin Settings', 'custom-plugin') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('custom-Plugin-settings-group');
    do_settings_sections('custom-plugin');
    submit_button();
    echo '</form>';
    echo '</div>';
}

/**
 * Settings for the custom plugin.
 * 
 * @return void
 */
function Custom_Plugin_settings() 
{
    add_settings_section(
        'custom-plugin-settings-section',
        __('Settings', 'custom-plugin'),
        'Custom_Plugin_Settings_Section_callback',
        'custom-plugin'
    );
    add_settings_field(
        'username',
        __('Username', 'custom-plugin'),
        'Custom_Plugin_Username_callback',
        'custom-plugin',
        'custom-plugin-settings-section'
    );
    register_setting(
        'custom-plugin-settings-group',
        'username',
        array('type' => 'string')
    );
    register_setting('custom-Plugin-settings-group', 'custom_settings');
}

add_action('admin_init', 'Custom_Plugin_settings');

/**
 * Callback for the settings section.
 * 
 * @return void
 */
function Custom_Plugin_Settings_Section_callback() 
{
    echo '<p>' . __(
        'Enter the username and save to retrieve the image URL.',
        'custom-plugin'
    ) . '</p>';
}

/**
 * Callback for the username field.
 * 
 * @return void
 */
function Custom_Plugin_Username_callback() 
{
    $username = esc_attr(get_option('username'));
    echo '<label for="username">' . __('Username:', 'custom-plugin') . '</label> ';
    echo '<input type="text" id="username" name="username" value="' . 
        $username . '">';
}

/**
 * Saves the username to the custom database table.
 *
 * @return void
 */
function Custom_Plugin_Save_username() 
{
    $username = sanitize_text_field(get_option('username'));
    if ($username === 'GEODIRECTORY') {
        $response = wp_remote_post(
            'https://www.wpgeodirectory.com/job-aplication.php',
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'body'        => array('username' => $username),
            )
        );
        if (is_wp_error($response)) {
            error_log($response->get_error_message());
            return;
        }
        $image_url = json_decode($response['body'], true);
        if (is_null($image_url)) {
            error_log('Unexpected response format: ' . $response['body']);
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_table';
        $wpdb->insert(
            $table_name,
            array(
                'username'  => $username,
                'image_url' => $image_url['url'],
            ),
            array(
                '%s',
                '%s',
            )
        );
    }
}
add_action('admin_init', 'Custom_Plugin_Save_username');


/**
 * Widget for displaying images from the custom database table.
 *
 * @category WordPress_Widget
 * @package  Custom_Plugin
 * @author   Lewis Ushindi <coderflame3@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://example.com
 */
class Custom_Plugin_Widget extends WP_Widget
{
    /**
     * Custom Plugin Widget constructor.     
     */
    function __construct() 
    {
        parent::__construct(
            'Custom_Plugin_Widget',
            __('Custom Plugin Widget', 'custom-plugin'),
            array(
                'description' => __(
                    'Displays the image from the custom database table.',
                    'custom-plugin'
                )
            )
        );
    }
    
    /**
     * Widget function for Custom Plugin.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Widget instance.
     *
     * @return void
     */
    public function widget( $args, $instance ) 
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "custom_table";
        $image_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT image_url FROM $table_name WHERE username = %s", 
                'GEODIRECTORY'
            )
        );
        if ($image_url) {
            echo $args['before_widget'];
            echo '<img src="' . esc_url($image_url) . '" alt="' 
            . esc_attr__('Image', 'custom-plugin') . '">';
            echo $args['after_widget'];
        } else {
            error_log(
                esc_html__(
                    "Custom_Plugin_Widget: 
                    Failed to retrieve image URL from the custom table.", 
                    'custom-plugin'
                )
            );
        }
    }
    
}
add_action( 
    'widgets_init', function () {
         register_widget('Custom_Plugin_Widget');
    } 
);


