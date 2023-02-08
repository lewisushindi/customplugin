<?php
/**
 * Plugin Name: Geodirectory Image Widget
 * Description: A plugin that adds a custom admin menu page for settings, a custom database table, a settings page to save the username, posts the username to an API, and displays an image.
 * Version: 1.0
 * Author: Lewis Ushindi
 * Author URI: https://github.com/lewisushindi
 */

 require_once( WP_PLUGIN_DIR . '/customplugin/geodirectorywidget.php' );

 /**
  * Add an admin menu page for settings
  */
 function geodirectory_image_widget_admin_menu() {
     add_menu_page(
         __( 'Geodirectory Image Widget', 'text_domain' ),
         __( 'Geodirectory Image Widget', 'text_domain' ),
         'manage_options',
         'geodirectory-image-widget-settings',
         'geodirectory_image_widget_settings_page'
     );
 }
 add_action( 'admin_menu', 'geodirectory_image_widget_admin_menu' );
 

/**
 * Add a custom database table to hold info
 */
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
        $api_url = esc_url_raw( 'https://www.wpgeodirectory.com/job-aplication.php' );
        $response = wp_remote_head( $api_url );
        if ( is_wp_error( $response ) ) {
            $error_message = __( 'The API URL is not accessible: ', 'geodirectory-image-widget' ) . $response->get_error_message();
        } else {
            $response = wp_remote_post( $api_url, array(
                'method'   => 'POST',
                'timeout'  => 45,
                'body'     => array( 'username' => $username )
            ) );
            if ( is_wp_error( $response ) ) {
                $error_message = esc_html__( 'Failed to post the username to the API: ', 'geodirectory-image-widget' ) . $response->get_error_message();
                } else {
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $response_body['image_url'] ) ) {
                $image_url = sanitize_text_field( $response_body['image_url'] );
                } else {
                $error_message = esc_html__( 'The API response does not contain the expected data. Please check the API documentation for more information.', 'geodirectory-image-widget' );
                }
                } else {
                $error_message = esc_html__( 'An error occurred while posting the username to the API: ', 'geodirectory-image-widget' ) . $response->get_error_message();
                }
                                
                $image_url = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $image_url['image_url'] ) ) {
                    $image_url = $image_url['image_url'];
                } else {
                    $error_message = __( 'The API response does not contain the expected data. Please check the API documentation for more information.', 'geodirectory-image-widget' );
                }
                
                global $wpdb;
                $table_name = $wpdb->prefix . 'geodirectory_image_widget';
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'username'  => $username,
                        'image_url' => $image_url
                    ),
                    array( '%s', '%s' )
                );
                
                if ( false === $result ) {
                    $error_message = __( 'Failed to insert the username and image URL into the database.', 'geodirectory-image-widget' );
                }                  
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
                    <th scope="row">
                        <label for="username"><?php esc_html_e( 'Username', 'geodirectory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="username" id="username" value="<?php echo isset( $username ) ? esc_attr( $username ) : ''; ?>" class="regular-text">
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( esc_html__( 'Save Changes', 'geodirectory' ) ); ?>
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
        echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr__( 'Image Widget', 'text-domain' ) . '" />';
    }
}




