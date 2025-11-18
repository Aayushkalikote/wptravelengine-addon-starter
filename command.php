<?php
/**
 * WP-CLI command registration file
 *
 * @package WPTravelEngine\AddonStarter
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

// Autoload dependencies
$autoload_file = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload_file ) ) {
    require_once $autoload_file;
}

// Register the command
WP_CLI::add_command( 'wptravelengine-addon-starter', 'WPTravelEngine\AddonStarter\WPCLICommand' );
