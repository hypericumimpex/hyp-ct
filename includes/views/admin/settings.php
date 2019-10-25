<h2><?php _e( 'Settings', 'woocommerce-conversion-tracking-pro' ); ?></h2>

<div class="postbox permission-form">
    <h2 class="hndle"><?php _e( 'Integration Access', 'woocommerce-conversion-tracking-pro' ); ?></h2>

    <div class="inside">
        <p class="description">
            <?php _e( 'Allow your team to access the Conversion Tracking integration settings by role.', 'woocommerce-conversion-tracking-pro' ); ?>
        </p>

        <form action="" method="post" id="wcct-save-permission">
            <?php
                foreach( $roles as $key => $role ) {
                    $permission     = isset( $get_permission[ $key ] ) ? $get_permission[ $key ] : '';
                    ?>
                    <fieldset>
                        <label for="<?php echo $key ?>">
                            <input
                             type="checkbox"
                             name="<?php echo $key ?>"
                             id="<?php echo $key ?>"
                             value="1"
                             <?php checked( 1, $permission )?> >
                            <?php echo $role ?>
                        </label>
                    </fieldset>
                    <?php
                }
            ?>
            <p class="submit">
                <?php wp_nonce_field( 'wcct-settings' ); ?>
                <input type="hidden" name="action" value="wcct_save_permissions">
                <button class="button button-primary" type="submit"><?php _e( 'Save Changes', 'woocommerce-conversion-tracking-pro' ) ?></button>
            </p>
        </form>
    </div>
</div>