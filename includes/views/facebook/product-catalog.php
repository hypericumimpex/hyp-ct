<div class="catalog-settings-wrap">
    <h2><?php _e( 'Facebook Catalog', 'woocommerce-conversion-tracking-pro' ); ?></h2>
    <?php
        $data_feed      = get_option( 'wcct_data_feed' );
        $auth           = wcct_get_auth();
        $username       = isset( $auth['username'] ) ? $auth['username'] : '';
        $password       = isset( $auth['password'] ) ? $auth['password'] : '';
        $authenticate   = isset( $auth['wcct-authenticate-enable'] ) ? $auth['wcct-authenticate-enable'] : '';
    ?>
    <div class="wcct-catalog">
        <p class="description">
            <?php _e( 'Facebook Product Catalog or simply Catalog is the uploaded list of the products usually promoted by Facebook Dynamic Ads or Collection Ad Format. The list of products of the catalog normally includes information such as Product ID, Description, Name, URL, Image URL and other attributes that are required to create the Ads for those uploaded products.' ) ?>
        </p>
        <h2 class="instruction-header"><?php _e( 'Setup Instructions', 'woocommerce-conversion-tracking-pro' ) ?></h2>
        <ol class="wcct-catalog-doc">
            <li>Go to <a href="https://www.facebook.com/products/" target="_blank"><?php _e('products catalog', 'woocommerce-conversion-tracking-pro'); ?></a>.</li>
            <li>Click <strong>Create Catalog</strong>.</li>
            <li>Select <strong>E-commerce</strong>.</li>
            <li>Provide a catalog name and create catalog.</li>
            <li>Then, in the <strong>Data Sources</strong> tab, click <strong>Add Data Source</strong>.</li>
            <li>Select <strong>Set a Schedule</strong>, copy the following data feed URL and set it as facebook data feed url.</li>
            <li>Click start upload.</li>
        </ol>

        <p class="help">
            <?php echo sprintf( __( '<a href="%s" target="_blank">Learn more</a> on setting up product catalog.', 'woocommerce-conversion-tracking-pro' ), 'https://wedevs.com/docs/woocommerce-conversion-tracking/facebook/facebook-product-catalog/?utm_source=wp-admin&utm_medium=inline-help&utm_campaign=wcct_docs&utm_content=FB_Product_Catalog' ); ?>
        </p>
    </div>

    <div class="wcct-catalog">
        <div class="wcct-product-form">
            <form action="" method="post" id="wcct-authenticate-form" >
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Authentication', 'woocommerce-conversion-tracking-pro' ) ?></th>
                            <td>
                                <fieldset>
                                    <label for="wcct-authenticate-form-enable">
                                        <input type="checkbox" id="wcct-authenticate-form-enable" name="authenticate[wcct-authenticate-enable]" value="yes" <?php checked( 'yes', $authenticate ) ?>>
                                        <?php _e( 'Protect using a username and password', 'woocommerce-conversion-tracking-pro' ) ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr class="form-field form-required authenticate-form">
                            <th scope="row"><label for="username">Username <span class="description">(required)</span></label></th>
                            <td><input name="authenticate[username]" type="text" id="username" value="<?php echo $username ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60"></td>
                        </tr>
                        <tr class="form-field form-required authenticate-form">
                            <th scope="row"><label for="password">Password <span class="description">(required)</span></label></th>
                            <td><input name="authenticate[password]" type="password" id="password" value="<?php echo $password ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60"></td>
                        </tr>

                        <tr>
                            <th><?php _e( 'Exclude Products', 'woocommerce-conversion-tracking-pro' ) ?></th>
                            <td>
                                <h4><?php _e( 'By Product Types:', 'woocommerce-conversion-tracking-pro' ) ?></h4>
                                <?php foreach( $product_type as $key => $value ): ?>
                                <?php
                                    $types   = isset( $data_feed['types'] ) ? $data_feed['types'] : array();
                                    $type    = in_array( $key, $types ) ? $key : '';
                                ?>
                                <fieldset>
                                    <label for="types-<?php echo $key ?>">
                                        <input type="checkbox" name="types[]" id="types-<?php echo $key ?>" value="<?php echo $key?>" <?php checked( $key, $type ) ?>>
                                        <?php echo $value ?>
                                    </label>
                                </fieldset>
                                <?php endforeach ?>

                                <h4><?php _e( 'By Product Categories:', 'woocommerce-conversion-tracking-pro' ) ?></h4>
                                <?php
                                    $selected_cats = isset( $data_feed['product_cat'] ) ? $data_feed['product_cat'] : false;
                                    wcct_category_checklist( $post_id = 0, $selected_cats, $attr = array(), $class = null );
                                ?>

                                <?php wp_nonce_field( 'wcct-authenticate' ); ?>
                                <input type="hidden" name="action" value="wcct_save_authenticate">
                                <button class="button button-primary" id="wcct-authenticate-submit" style="margin-top:20px"><?php _e( 'Save Changes', 'woocommerce-conversion-tracking-pro' ) ?></button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <div class="wcct-catalog wcct-data-url">
        <p>
            <strong><?php _e( 'Product Data Feed URL', 'woocommerce-conversion-tracking-pro' ); ?></strong>
        </p>
        <input id="link" class="regular-text code" type="text" value="<?php echo site_url( '/' ) . '?action=wcct-product-export'?>" readonly>

        <span class="wcct-copy-clipboard wcct-tooltip" data-clipboard-target="#link">Copy
            <span class="wcct-tooltiptext clip-board-tooltip">Copy to clipboard</span>
        </span>
    </div>
</div>