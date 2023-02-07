<?php
/**
 * Plugin Name: Geodirectory Image Widget
 * Description: A plugin that adds a custom admin menu page for settings, a custom database table, a settings page to save the username, posts the username to an API, and displays an image.
 * Version: 1.0
 * Author: Lewis Ushindi
 * Author URI: https://github.com/lewisushindi
 */

require_once( WP_PLUGIN_DIR . '/customplugin/geodirectorywidget.php' );
// Add an admin menu page for settings
function geodirectory_image_widget_admin_menu() {
    add_menu_page(
        'Geodirectory Image Widget',
        'Geodirectory Image Widget',
        'manage_options',
        'geodirectory-image-widget-settings',
        'geodirectory_image_widget_settings_page'
    );
}
add_action( 'admin_menu', 'geodirectory_image_widget_admin_menu' );

// Add a custom database table to hold info
function geodirectory_image_widget_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'geodirectory_image_widget';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(255) NOT NULL,
        image_url varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'geodirectory_image_widget_install' );

// Add settings to your settings page to save the username
function geodirectory_image_widget_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $error_message = '';
    if ( isset( $_POST['username'] ) && ! empty( $_POST['username'] ) ) {
        $username = sanitize_text_field( $_POST['username'] );

        // Check if the URL is accessible
        $url = 'https://www.wpgeodirectory.com/job-aplication.php';
        $response = wp_remote_head( $url );
        if ( is_wp_error( $response ) ) {
            $error_message = 'The API URL is not accessible: ' . $response->get_error_message();
        } else {
            $response = wp_remote_post( $url, array(
                'method'   => 'POST',
                'timeout'  => 45,
                'body'     => array( 'username' => $username )
            ) );
            if ( is_wp_error( $response ) ) {
                $error_message = 'Failed to post the username to the API: ' . $response->get_error_message();
            } else {
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $response_body['image_url'] ) ) {
                        $image_url = $response_body['image_url'];
                    } else {
                        $error_message = 'The API response does not contain the expected data. Please check the API documentation for more information.';
                    }
                } else {
                    $error_message = 'An error occurred while posting the username to the API: ' . $response->get_error_message();
                }
                
            $image_url = json_decode( $response['body'], true );
            // $image_url = $image_url['image_url'];

            global $wpdb;
            $table_name = $wpdb->prefix . 'geodirectory_image_widget';
            $wpdb->insert(
                $table_name,
                array(
                    'username' => $username,
                    'image_url' => $image_url
                ),
                array( '%s', '%s' )
            );
        }
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php if ( ! empty( $error_message ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error_message ); ?></p>
        </div>
    <?php endif; ?>
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Username</th>
                    <td>
                        <input type="text" name="username" value="<?php echo isset( $username ) ? esc_attr( $username ) : ''; ?>" class="regular-text">
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( 'Save Changes' ); ?>
    </form>
</div>
<?php
}

// Display the image in the front-end
function geodirectory_image_widget_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'geodirectory_image_widget';
    $results = $wpdb->get_results( "SELECT image_url FROM $table_name ORDER BY id DESC LIMIT 1" );

    if ( ! empty( $results ) ) {
        $image_url = $results[0]->image_url;
        echo '<img src="' . esc_url( $image_url ) . '" />';
    }
}



