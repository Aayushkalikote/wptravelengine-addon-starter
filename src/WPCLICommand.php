<?php

namespace WPTravelEngine\AddonStarter;

use WP_CLI;
use Symfony\Component\Filesystem\Filesystem;

/**
 * WP-CLI command for scaffolding WP Travel Engine addons.
 *
 * @package WPTravelEngine\AddonStarter
 */
class WPCLICommand {

    /**
     * Scaffold a new WP Travel Engine addon.
     *
     * ## OPTIONS
     *
     * [--name=<name>]
     * : The addon name (e.g., "PayStack Payment Gateway").
     *
     * [--description=<description>]
     * : The addon description.
     *
     * [--type=<type>]
     * : The addon type: payment-gateway or basic
     * ---
     * options:
     *   - payment-gateway
     *   - basic
     * ---
     *
     * [--pro]
     * : Requires WP Travel Engine Pro compatibility.
     *
     * [--settings=<settings>]
     * : Settings type for basic addons: none, global, trip-edit, both
     * ---
     * default: none
     * options:
     *   - none
     *   - global
     *   - trip-edit
     *   - both
     * ---
     *
     * [--webpack]
     * : Include webpack configuration.
     *
     * ## EXAMPLES
     *
     *     # Create a payment gateway
     *     wp wptravelengine-addon-starter scaffold --name="PayStack Payment Gateway" --type=payment-gateway --pro
     *
     *     # Create a basic addon with global settings
     *     wp wptravelengine-addon-starter scaffold --name="Trip Difficulty Level" --settings=both --webpack
     *
     * @when after_wp_load
     */
    public function scaffold( $args, $assoc_args ) {
        // Get WordPress plugins directory
        $plugins_dir = WP_CONTENT_DIR . '/plugins';

        // Interactive mode if name not provided
        if ( empty( $assoc_args['name'] ) ) {
            $assoc_args['name'] = $this->prompt( 'Addon Name (e.g., "PayStack Payment Gateway" or "Trip Difficulty Level")' );
        }

        $addon_name = trim( $assoc_args['name'] );

        if ( empty( $addon_name ) ) {
            WP_CLI::error( 'Addon name is required.' );
        }

        // Get description
        $description = isset( $assoc_args['description'] )
            ? $assoc_args['description']
            : $this->prompt( 'Addon Description', $addon_name . ' for WP Travel Engine' );

        // Get type
        $is_gateway = ( isset( $assoc_args['type'] ) && $assoc_args['type'] === 'payment-gateway' );

        if ( !isset( $assoc_args['type'] ) ) {
            $type_choice = $this->choice( 'Is this a payment gateway addon?', ['no', 'yes'], 0 );
            $is_gateway = ( $type_choice === 'yes' );
        }

        // Get pro compatibility
        $requires_pro = isset( $assoc_args['pro'] );

        if ( !isset( $assoc_args['pro'] ) ) {
            $pro_choice = $this->choice( 'Does this addon require WP Travel Engine Pro compatibility?', ['no', 'yes'], 0 );
            $requires_pro = ( $pro_choice === 'yes' );
        }

        // Settings type for basic addons
        $settings_type = 'none';
        if ( !$is_gateway ) {
            $settings_type = isset( $assoc_args['settings'] ) ? $assoc_args['settings'] : 'none';

            if ( !isset( $assoc_args['settings'] ) ) {
                $settings_type = $this->choice(
                    'What type of settings does this addon need?',
                    ['none', 'global', 'trip-edit', 'both'],
                    0
                );
            }
        } else {
            $settings_type = 'global'; // Payment gateways always have global settings
        }

        // Webpack
        $use_webpack = isset( $assoc_args['webpack'] );

        if ( !isset( $assoc_args['webpack'] ) && !$is_gateway ) {
            $webpack_choice = $this->choice( 'Does this addon require Webpack configuration?', ['no', 'yes'], 0 );
            $use_webpack = ( $webpack_choice === 'yes' );
        }

        // Generate naming conventions
        $names = $this->generateNamingConventions( $addon_name, $is_gateway );

        $data = [
            'addon_name'    => $addon_name,
            'description'   => $description,
            'is_gateway'    => $is_gateway,
            'requires_pro'  => $requires_pro,
            'settings_type' => $settings_type,
            'use_webpack'   => $use_webpack,
            'names'         => $names,
        ];

        // Display configuration
        WP_CLI::log( WP_CLI::colorize( "\n%G✓ Addon Configuration:%n" ) );
        WP_CLI::log( "  Name: {$addon_name}" );
        WP_CLI::log( "  Type: " . ( $is_gateway ? 'Payment Gateway' : 'Basic Addon' ) );
        WP_CLI::log( "  Pro Compatible: " . ( $requires_pro ? 'Yes' : 'No' ) );
        WP_CLI::log( "  Settings: {$settings_type}" );
        WP_CLI::log( "  Webpack: " . ( $use_webpack ? 'Yes' : 'No' ) );
        WP_CLI::log( "  Slug: {$names['full_slug']}" );

        // Generate addon in plugins directory
        $addon_dir = $plugins_dir . '/' . $names['full_slug'];

        if ( file_exists( $addon_dir ) ) {
            WP_CLI::error( "Directory already exists: {$addon_dir}" );
        }

        // Generate files
        $this->generateAddonFiles( $data, $addon_dir );

        WP_CLI::success( "Addon scaffold created successfully!" );
        WP_CLI::log( WP_CLI::colorize( "%YLocation:%n {$addon_dir}" ) );
        WP_CLI::log( "\nNext steps:" );
        WP_CLI::log( "  1. cd {$addon_dir}" );
        WP_CLI::log( "  2. composer install" );
        if ( $use_webpack ) {
            WP_CLI::log( "  3. yarn install" );
            WP_CLI::log( "  4. yarn build" );
        }
        WP_CLI::log( "\nThen activate the plugin:" );
        WP_CLI::log( "  wp plugin activate {$names['full_slug']}" );
    }

    /**
     * Interactive prompt
     */
    private function prompt( $question, $default = '' ) {
        if ( !empty( $default ) ) {
            $question .= " [{$default}]";
        }
        $question .= ': ';

        fwrite( STDOUT, $question );
        $input = trim( fgets( STDIN ) );

        return !empty( $input ) ? $input : $default;
    }

    /**
     * Interactive choice
     */
    private function choice( $question, $options, $default = 0 ) {
        WP_CLI::log( $question );
        foreach ( $options as $i => $option ) {
            $default_marker = ( $i === $default ) ? ' (default)' : '';
            WP_CLI::log( "  [{$i}] {$option}{$default_marker}" );
        }

        $input = $this->prompt( 'Enter choice', $default );
        $selected = is_numeric( $input ) ? (int) $input : array_search( $input, $options );

        return isset( $options[$selected] ) ? $options[$selected] : $options[$default];
    }

    /**
     * Confirmation prompt
     */
    private function confirm( $question, $default = false ) {
        $default_text = $default ? 'Y/n' : 'y/N';
        $input = strtolower( $this->prompt( "{$question} ({$default_text})", $default ? 'y' : 'n' ) );

        if ( empty( $input ) ) {
            return $default;
        }

        return in_array( $input, ['y', 'yes', '1', 'true'] );
    }

    /**
     * Generate naming conventions from addon name
     */
    private function generateNamingConventions( $addon_name, $is_gateway ) {
        // Remove "WP Travel Engine - " prefix if present
        $clean_name = preg_replace( '/^WP\s+Travel\s+Engine\s*-\s*/i', '', $addon_name );

        // For payment gateways, remove common suffixes to get clean slug
        if ( $is_gateway ) {
            $clean_name = preg_replace( '/(Payment\s+Gateway|Gateway|Payment)$/i', '', $clean_name );
            $clean_name = trim( $clean_name );
        }

        // Generate slug (kebab-case, lowercase, no prefixes)
        $slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $clean_name ), '-' ) );

        // Function slug (lowercase snake_case for function names)
        $function_slug = strtolower( preg_replace( '/[^a-z0-9]+/i', '_', $clean_name ) );
        $function_slug = trim( $function_slug, '_' ); // Remove leading/trailing underscores
        $function_slug = preg_replace( '/_+/', '_', $function_slug ); // Replace multiple underscores with single

        // Full slug with wptravelengine prefix (add -payment suffix for gateways)
        $full_slug = $is_gateway
            ? 'wptravelengine-' . $slug . '-payment'
            : 'wptravelengine-' . $slug;

        // Namespace (PascalCase, no spaces, with WPTravelEngine prefix)
        $namespace = 'WPTravelEngine' . str_replace( [' ', '-', '_'], '', ucwords( $clean_name, ' -_' ) );

        // Constants (SCREAMING_SNAKE_CASE with prefix)
        $constant = strtoupper( str_replace( '-', '_', $slug ) );

        // Settings key (lowercase, no separators, no prefixes)
        $settings_key = strtolower( str_replace( ['-', '_', ' '], '', $slug ) );

        // Gateway ID (lowercase_with_underscores_enable)
        $gateway_id = $is_gateway ? str_replace( '-', '_', $slug ) . '_enable' : '';

        // Title (clean name for display, e.g., "Heylight" without "Payment Gateway")
        $title = $clean_name;

        return [
            'slug'          => $slug,
            'full_slug'     => $full_slug,
            'function_slug' => $function_slug,
            'namespace'     => $namespace,
            'constant'      => $constant,
            'settings_key'  => $settings_key,
            'gateway_id'    => $gateway_id,
            'title'         => $title,
        ];
    }

    /**
     * Generate addon files
     */
    private function generateAddonFiles( $data, $addon_dir ) {
        $filesystem = new Filesystem();
        $stubs_path = dirname( __DIR__ ) . '/stubs';

        // Determine which stub type to use
        $stub_type = $data['is_gateway'] ? 'payment-gateway' : 'basic-addon';

        // Create base directories
        $filesystem->mkdir( $addon_dir );
        $filesystem->mkdir( "$addon_dir/includes" );

        // Include the command class for file generation logic
        require_once __DIR__ . '/Console/Commands/MakeAddonCommand.php';

        $command = new \WPTravelEngine\AddonStarter\Console\Commands\MakeAddonCommand();

        // Use reflection to call private methods
        $reflection = new \ReflectionClass( $command );

        // Generate main plugin file
        $method = $reflection->getMethod( 'generateMainPluginFile' );
        $method->setAccessible( true );
        $method->invoke( $command, $addon_dir, $stubs_path, $stub_type, $data );

        // Generate Plugin class
        $method = $reflection->getMethod( 'generatePluginClass' );
        $method->setAccessible( true );
        $method->invoke( $command, $addon_dir, $stubs_path, $stub_type, $data );

        // Generate type-specific files
        if ( $data['is_gateway'] ) {
            $method = $reflection->getMethod( 'generatePaymentGatewayFiles' );
            $method->setAccessible( true );
            $method->invoke( $command, $addon_dir, $stubs_path, $data );
        } else {
            $method = $reflection->getMethod( 'generateBasicAddonFiles' );
            $method->setAccessible( true );
            $method->invoke( $command, $addon_dir, $stubs_path, $data );
        }

        // Generate configuration files
        $method = $reflection->getMethod( 'generateConfigFiles' );
        $method->setAccessible( true );
        $method->invoke( $command, $addon_dir, $stubs_path, $data );

        // Generate webpack files if needed
        if ( $data['use_webpack'] ) {
            $method = $reflection->getMethod( 'generateWebpackFiles' );
            $method->setAccessible( true );
            $method->invoke( $command, $addon_dir, $stubs_path, $data );
        }
    }
}

// Register the command with WP-CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'wptravelengine-addon-starter', 'WPTravelEngine\AddonStarter\WPCLICommand' );
}
