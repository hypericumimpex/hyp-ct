<?php

/**
 * Dokan Update class
 *
 * Performas license validation and update checking
 *
 * @package Dokan
 */
class WCCT_Update {

    /**
     * The license product ID
     *
     * @var string
     */
    private $product_id = 'wcct-pro';

    /**
     * The license plan ID
     *
     * @var string
     */
    private $plan_id;

    const base_url     = 'https://wedevs.com/';
    const api_endpoint = 'http://api.wedevs.com/';
    const option       = 'wcct_license';
    const slug         = 'wcct-pro';

    function __construct( $plan ) {

        $this->plan_id = $plan;

        // bail out if it's a local server
        if ( $this->is_local_server() ) {
            return;
        }

        add_filter( 'wcct_nav_tab', array( $this, 'add_license_tab' ) );

        add_action( 'wcct_nav_content_license', array( $this, 'plugin_update' ) );

        if ( is_multisite() ) {
            if ( is_main_site() ) {
                add_action( 'admin_notices', array($this, 'license_enter_notice') );
                add_action( 'admin_notices', array($this, 'license_check_notice') );
            }
        } else {
            add_action( 'admin_notices', array($this, 'license_enter_notice') );
            add_action( 'admin_notices', array($this, 'license_check_notice') );
        }

        add_filter( 'pre_set_site_transient_update_plugins', array($this, 'check_update') );
        add_filter( 'pre_set_transient_update_plugins', array($this, 'check_update') );

        add_action( 'in_plugin_update_message-' . plugin_basename( WCCT_PRO_FILE ), array( $this, 'plugin_update_message' ) );
        add_filter( 'plugins_api', array($this, 'check_info'), 10, 3 );
    }

    /**
     * Check if the current server is localhost
     *
     * @return boolean
     */
    private function is_local_server() {

        // if from command line
        if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
            return;
        }

        if ( $_SERVER['HTTP_HOST'] == 'localhost'
            || substr( $_SERVER['REMOTE_ADDR'], 0, 3 ) == '10.'
            || substr( $_SERVER['REMOTE_ADDR'], 0, 7 ) == '192.168' ) {

            return true;
        }

        if ( in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) ) {
            return true;
        }

        $fragments = explode( '.',  site_url() );

        if ( in_array( end( $fragments ), array( 'dev', 'local', 'localhost', 'test' ) ) ) {
            return true;
        }

        return false;
    }

    /**
     * Add license
     *
     * @return void
     */
    function add_license_tab( $sections ) {

        if ( ! current_user_can( 'manage_options' ) ) {
            return $sections;
        }

        $sections[]    = array(
            'id'    => 'license',
            'title' => __( 'License', 'woocommerce-conversion-tracking-pro' ),
        );

        return $sections;
    }

    /**
     * Get license key
     *
     * @return array
     */
    function get_license_key() {
        return get_option( self::option, array() );
    }

    /**
     * Check if this is a valid license
     *
     * @since 2.7.1
     *
     * @return boolean
     */
    public function is_valid_license() {
        $license_status = get_option( 'wcct_license_status' );

        if ( is_object( $license_status ) && $license_status->activated ) {
            return true;
        }

        return false;
    }

    /**
     * Prompts the user to add license key if it's not already filled out
     *
     * @return void
     */
    function license_enter_notice() {
        if ( $key = $this->get_license_key() ) {
            return;
        }
        ?>
        <div class="error">
            <p><?php printf( __( 'Please <a href="%s">enter</a> your <strong>WooCommerce Conversion Tracking</strong> plugin license key to get regular update and support.', 'woocommerce-conversion-tracking-pro' ), admin_url( 'admin.php?page=conversion-tracking&tab=license' ) ); ?></p>
        </div>
        <?php
    }

    /**
     * Check activation every 12 hours to the server
     *
     * @return void
     */
    function license_check_notice() {
        if ( !$key = $this->get_license_key() ) {
            return;
        }

        $error = __( 'Please activate your copy', 'woocommerce-conversion-tracking-pro' );

        $license_status = get_option( 'wcct_license_status' );

        if ( $this->is_valid_license() ) {

            $status = get_transient( self::option );
            if ( false === $status ) {
                $status   = $this->activation();
                $duration = 60 * 60 * 12; // 12 hour

                set_transient( self::option, $status, $duration );
            }

            if ( $status && $status->success ) {

                // notice if validity expires
                if ( isset( $status->update ) ) {
                    $update = strtotime( $status->update );

                    if ( time() > $update ) {
                        echo '<div class="error">';
                        echo '<p>Your <strong>WooCommerce Conversion Tracking</strong> License has been expired. Please <a href="https://wedevs.com/account/" target="_blank">renew your license</a>.</p>';
                        echo '</div>';
                    }
                }
                return;
            }

            // may be the request didn't completed
            if ( !isset( $status->error )) {
                return;
            }

            $error = $status->error;
        }
        ?>
        <div class="error">
            <p><strong><?php _e( 'WC Conversion Error:', 'woocommerce-conversion-tracking-pro' ); ?></strong> <?php echo $error; ?></p>
        </div>
        <?php
    }

    /**
     * Activation request to the plugin server
     *
     * @return object
     */
    function activation( $request = 'check' ) {
        global $wp_version;

        if ( ! $option = $this->get_license_key() ) {
            return;
        }

        $params = array(
            'timeout'    => ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ? 30 : 3 ),
            'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
            'body'       => array(
                'request'     => $request,
                'email'       => $option['email'],
                'licence_key' => $option['key'],
                'product_id'  => $this->product_id,
                'instance'    => home_url()
            )
        );

        $response = wp_remote_post( self::api_endpoint . 'activation', $params );
        $update   = wp_remote_retrieve_body( $response );

        if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
            if ( is_wp_error( $response ) ) {
                echo '<div class="error"><p><strong>Conversion Tracking Pro Error:</strong> ' . $response->get_error_message() . '</p></div>';
                return false;
            }

            if ( $response['response']['code'] != 200 ) {
                echo '<div class="error"><p><strong>Conversion Tracking Pro Error:</strong> ' . $response['response']['code'] .' - ' . $response['response']['message'] . '</p></div>';
                return false;
            }

            printf('<pre>%s</pre>', print_r( $response, true ) );
        }

        return json_decode( $update );
    }

    /**
     * Integrates into plugin update api check
     *
     * @param object $transient
     * @return object
     */
    function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote_info = $this->get_info();

        if ( !$remote_info ) {
            return $transient;
        }

        list( $plugin_name, $plugin_version) = $this->get_current_plugin_info();

        if ( version_compare( $plugin_version, $remote_info->latest, '<' ) ) {

            $obj              = new stdClass();
            $obj->slug        = self::slug;
            $obj->new_version = $remote_info->latest;
            $obj->url         = self::base_url;

            if ( isset( $remote_info->latest_url ) ) {
                $obj->package = $remote_info->latest_url;
            }

            $basefile = plugin_basename( WCCT_PRO_VERSION );
            $transient->response[$basefile] = $obj;
        }

        return $transient;
    }

    /**
     * Plugin changelog information popup
     *
     * @param type $false
     * @param type $action
     * @param type $args
     * @return \stdClass|boolean
     */
    function check_info( $false, $action, $args ) {

        if ( 'plugin_information' != $action ) {
            return $false;
        }

        if ( self::slug == $args->slug ) {

            $remote_info = $this->get_info();

            $obj              = new stdClass();
            $obj->slug        = self::slug;
            $obj->new_version = $remote_info->latest;

            if ( isset( $remote_info->latest_url ) ) {
                $obj->download_link = $remote_info->latest_url;
            }

            $obj->sections = array(
                'description' => $remote_info->msg
            );

            return $obj;
        }

        return $false;
    }

    /**
     * Collects current plugin information
     *
     * @return array
     */
    function get_current_plugin_info() {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';

        $plugin_data    = get_plugin_data( WCCT_PRO_DIR . '/conversion-tracking-pro.php' );
        $plugin_name    = $plugin_data['Name'];
        $plugin_version = $plugin_data['Version'];

        return array($plugin_name, $plugin_version);
    }

    /**
     * Get plugin update information from server
     *
     * @global string $wp_version
     * @global object $wpdb
     * @return boolean
     */
    function get_info() {
        global $wp_version, $wpdb;

        list( $plugin_name, $plugin_version) = $this->get_current_plugin_info();

        if ( is_multisite() ) {
            $wp_install = network_site_url();
        } else {
            $wp_install = home_url( '/' );
        }

        $license = $this->get_license_key();

        $params = array(
            'timeout'    => 15,
            'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
            'body' => array(
                'name'              => $plugin_name,
                'slug'              => $this->plan_id,
                'type'              => 'plugin',
                'version'           => $plugin_version,
                'wp_version'        => $wp_version,
                'php_version'       => phpversion(),
                'site_url'          => $wp_install,
                'license'           => isset( $license['key'] ) ? $license['key'] : '',
                'license_email'     => isset( $license['email'] ) ? $license['email'] : '',
                'product_id'        => $this->product_id
            )
        );

        $response = wp_remote_post( self::api_endpoint . 'update_check', $params );
        $update   = wp_remote_retrieve_body( $response );

        if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
            return false;
        }

        return json_decode( $update );
    }

    /**
     * Plugin license enter admin UI
     *
     * @return void
     */
    function plugin_update() {
        $errors = array();
        if ( isset( $_POST['submit'] ) ) {
            if ( empty( $_POST['email'] ) ) {
                $errors[] = __( 'Empty email address', 'woocommerce-conversion-tracking-pro' );
            }

            if ( empty( $_POST['license_key'] ) ) {
                $errors[] = __( 'Empty license key', 'woocommerce-conversion-tracking-pro' );
            }

            if ( !$errors ) {
                update_option( self::option, array('email' => $_POST['email'], 'key' => $_POST['license_key']) );
                delete_transient( self::option );

                $license_status = get_option( 'wcct_license_status' );

                if ( !isset( $license_status->activated ) || $license_status->activated != true ) {
                    $response = $this->activation( 'activation' );

                    if ( $response && isset( $response->activated ) && $response->activated ) {
                        update_option( 'wcct_license_status', $response );
                    }
                }


                echo '<div class="updated"><p>' . __( 'Settings Saved', 'woocommerce-conversion-tracking-pro' ) . '</p></div>';
            }
        }

        if ( isset( $_POST['delete_license'] ) ) {
            delete_option( self::option );
            delete_transient( self::option );
            delete_option( 'wcct_license_status' );
        }

        $license = $this->get_license_key();
        $email   = $license ? $license['email'] : '';
        $key     = $license ? $license['key'] : '';
        ?>
            <h1><?php _e( 'Plugin Activation', 'woocommerce-conversion-tracking-pro' ); ?></h1>

            <p class="description">
                <?php _e( 'Enter the E-mail address that was used for purchasing the plugin and the license key.', 'woocommerce-conversion-tracking-pro' ); ?>
                <?php _e( 'We recommend you to enter those details to get regular <strong>plugin update and support</strong>.', 'woocommerce-conversion-tracking-pro' ); ?>
            </p>

            <hr>
            <div class="wcct-pro-pro-license-wrap">
                <?php
                if ( $errors ) {
                    foreach ($errors as $error) {
                        ?>
                        <div class="error"><p><?php echo $error; ?></p></div>
                        <?php
                    }
                }

                $license_status = get_option( 'wcct_license_status' );
                if ( !isset( $license_status->activated ) || $license_status->activated != true ) {
                    ?>

                    <form method="post" action="">
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'E-mail Address', 'woocommerce-conversion-tracking-pro' ); ?></th>
                                <td>
                                    <input type="email" name="email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'Purchased email address', 'woocommerce-conversion-tracking-pro' ); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'License Key', 'woocommerce-conversion-tracking-pro' ); ?></th>
                                <td>
                                    <input type="text" name="license_key" class="regular-text" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'License key', 'woocommerce-conversion-tracking-pro' ); ?>">
                                </td>
                            </tr>
                        </table>

                        <?php submit_button( __( 'Save & Activate', 'woocommerce-conversion-tracking-pro' ) ); ?>
                    </form>
                <?php } else {

                    if ( isset( $license_status->update ) ) {
                        $update = strtotime( $license_status->update );
                        $expired = false;

                        if ( time() > $update ) {
                            $string = __( 'has been expired %s ago', 'woocommerce-conversion-tracking-pro' );
                            $expired = true;
                        } else {
                            $string = __( 'will expire in %s', 'woocommerce-conversion-tracking-pro' );
                        }
                        // $expired = true;
                        ?>
                        <div class="updated <?php echo $expired ? 'error' : ''; ?>">
                            <p>
                                <strong><?php _e( 'Validity:', 'woocommerce-conversion-tracking-pro' ); ?></strong>
                                <?php printf( 'Your license %s.', sprintf( $string, human_time_diff( $update, time() ) ) ); ?>
                            </p>

                            <?php if ( $expired ) { ?>
                                <p><a href="https://wedevs.com/account/" target="_blank" class="button-primary"><?php _e( 'Renew License', 'woocommerce-conversion-tracking-pro' ); ?></a></p>
                            <?php } ?>
                        </div>
                        <?php
                    }
                    ?>

                    <div class="updated">
                        <p><?php _e( 'Plugin is activated', 'woocommerce-conversion-tracking-pro' ); ?></p>
                    </div>

                    <form method="post" action="">
                        <?php submit_button( __( 'Delete License', 'woocommerce-conversion-tracking-pro' ), 'delete', 'delete_license' ); ?>
                    </form>

                <?php } ?>

            </div>


            <?php do_action( 'wcct_update_license_wrap' ); ?>
        <?php
    }

    /**
     * Show plugin udpate message
     *
     * @since  2.7.1
     *
     * @param  array $args
     *
     * @return void
     */
    public function plugin_update_message( $args ) {

        if ( $this->is_valid_license() ) {
            return;
        }

        $upgrade_notice = sprintf(
            '</p><p class="%s-plugin-upgrade-notice" style="background: #dc4b02;color: #fff;padding: 10px;">Please <a href="%s" target="_blank">activate</a> your license key for getting regular updates and support',
            self::slug,
            admin_url( 'admin.php?page=conversion-tracking&tab=license' )
        );

        echo apply_filters( $this->product_id . '_in_plugin_update_message', wp_kses_post( $upgrade_notice ) );
    }

}
