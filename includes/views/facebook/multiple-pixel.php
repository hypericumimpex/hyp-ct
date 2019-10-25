<div class="inner-setting">

    <?php foreach ( $settings as $index => $setting ) { ?>

    <div class="wc-ct-form-group <?php echo $border ?>">

        <table class="form-table custom-table">

            <?php foreach ( $setting_field as $field_key => $field ) {?>
                <tr>
                    <th>
                        <label for="<?php echo $id . '-' . $field['label']; ?>"><?php echo $field['label']; ?></label>
                    </th>

                    <td>
                        <?php
                        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

                        switch ( $field['type'] ) {
                            case 'text':
                               $value = isset( $settings[ $index ][ $field['name'] ] ) ? $settings[ $index ][ $field['name'] ] : '';
                                printf( '<input type="text" name="settings[%s][%d][%s]" placeholder="%s" value="%s" id="%s">', $id, $index, $field['name'], $placeholder, $value, $id . '-' . $field['name'] );

                                break;

                            case 'textarea':
                                $value = isset( $settings[ $index ][ $field['name'] ] ) ? $settings[ $index ][ $field['name'] ] : '';
                                printf( '<textarea type="text" name="settings[%s][%s]" placeholder="%s" id="%s" cols="30" rows="3">%s</textarea>', $id, $field['name'], $placeholder, $id . '-' . $field['name'], $value );
                                break;

                            case 'checkbox':
                                $value = isset( $settings[ $index ][ $field['name'] ] ) ? $settings[ $index ][ $field['name'] ] : 'off';
                                printf( '<label for="%5$s"><input type="checkbox" name="settings[%1$s][%2$s]" %3$s id="%5$s" value="on"> %4$s</label>', $id, $field['name'], checked( 'on', $value, false ), $field['description'], $id . '-' . $field['name'] );
                                break;

                            case 'multicheck':
                                ?>
                                <div class="wc-ct-option">
                                    <?php
                                    foreach ( $field['options'] as $key => $option ) {
                                        $field_name = $field['name'];

                                        $checked = isset( $settings[$index][ $field_name ][ $key ] ) ? 'on' : '';
                                        ?>
                                            <label for="<?php echo $id . '-' . $key; ?>">
                                                <input type="checkbox" name="settings[<?php echo $id; ?>][<?php echo $index?>][<?php echo $field_name; ?>][<?php echo $key; ?>]" <?php checked( 'on', $checked ); ?> id="<?php echo $id . '-' . $key; ?>">
                                                <?php echo $option; ?>
                                            </label>
                                            <br>
                                            <?php
                                    }
                                    ?>
                                </div>
                            <?php
                        }

                        if ( isset( $field['help'] ) && ! empty( $field['help'] ) ) {
                            echo '<p class="help">' . $field['help'] . '</p>';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <button id="remove" class="button button-secondary button-small" style="float:right;margin-right: 12px;margin-top: -30px;"><span class="dashicons dashicons-trash"></span></button>
    </div>

<?php
    }
?>
</div>
<div class="wcct-add-new">
    <button class="button button-secondary button-small" id="add-new-pixel"><span class="dashicons dashicons-plus-alt"></span> <?php _e( 'Add New', 'woocommerce-conversion-tracking-pro' ); ?></button>
</div>
