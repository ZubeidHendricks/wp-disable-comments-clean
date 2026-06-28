<?php
/**
 * Uninstall cleanup.
 *
 * @package DisableCommentsClean
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'disable-comments-clean_options' );
