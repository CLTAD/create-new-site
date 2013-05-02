<?php 
if (!defined('ABSPATH')){
    die(__('No direct access allowed'));
}
global $current_site;
?>

<div class="buddypress-page">
    <!-- Display Plugin Icon, Header, and Description -->
    <div id="status"></div>
    <p>You can create up to <?php echo $data['sites_per_user']; ?> of your own sites, though you may be a member of many more.</p>
    <?php if ($data['sites_remaining'] < 1 ): ?>
        <div id="currentsites">
            <p><strong>You have reached the limit of <?php echo $data['sites_per_user']; ?> site<?php if ($data['sites_per_user'] > 1) { echo 's'; }?>, so you can't create any more. Please contact elearning-support@arts.ac.uk if you have any queries.</strong></p>
        </div>    
    <?php else : ?>
        <div id="currentsites">
            <p><strong>So far you have created <span id="sites-created"><?php echo $data['count_is_admin_all_sites'] - $data['count_is_admin_group_sites']; ?></span> site<?php if ($data['count_is_admin_all_sites'] - $data['count_is_admin_group_sites'] > 1){ echo('s'); }?> - you can create <span id="sites-remaining"><?php echo $data['sites_remaining']; ?></span> more.</strong></p>
        </div>

    <form method="post" action="" id="create-new-site-form">
    <?php wp_nonce_field('ajax-create-new-site', 'ajax-create-new-site') ?>
        <table class="form-table">

         <!-- Text Area Control -->
         <tr class="form-field form-required">
            <th scope="row"><?php _e('Site URL') ?></th>
            <td>
            <?php if (is_subdomain_install()) { ?>
                <input id="blogurl" name="blogurl" maxlength="50" size="30" type="text" class="regular-text" title="<?php esc_attr_e('Domain') ?>"/> <span class="no-break">.<?php echo preg_replace('|^www\.|', '', $current_site->domain); ?></span>
            <?php } else {
                echo $current_site->domain . $current_site->path ?> <input id="blogurl" name="blogurl" maxlength="50" size="30" class="regular-text" type="text" title="<?php esc_attr_e('Domain') ?>"/>
            <?php }
            echo '<p><em>' . __("The web address of your site. Only lowercase letters (a-z) and numbers are allowed - no spaces. This can't be changed later.") . '</em></p>';
            ?>
            </td>
        </tr>
        <tr class="form-field form-required">
            <th scope="row"><?php _e( 'Site Title' ) ?></th>
            <td>
                <input id="blogtitle" name="blogtitle"  maxlength="255" size="30" type="text" class="regular-text" title="<?php esc_attr_e( 'Title' ) ?>"/>
                <p><em>This can be changed later.</em></p>
            </td>
        </tr>

        </table>
        <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Create Site') ?>" />
        </p>
    </form>

    <?php endif; ?>

</div>
