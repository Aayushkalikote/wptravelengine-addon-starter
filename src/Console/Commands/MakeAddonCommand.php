<?php

namespace WPTravelEngine\AddonStarter\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class MakeAddonCommand extends Command
{
    protected static $defaultName = 'make:addon';

    protected function configure()
    {
        $this->setDescription('Scaffold a new WP Travel Engine addon with proper structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        // 1. Addon name
        $addonNameQuestion = new Question('Addon Name (e.g., "PayStack Payment Gateway" or "Trip Difficulty Level"): ');
        $addonName = trim($helper->ask($input, $output, $addonNameQuestion));

        // 2. Description
        $descriptionQuestion = new Question('Addon Description: ', $addonName . ' for WP Travel Engine');
        $description = $helper->ask($input, $output, $descriptionQuestion);

        // 3. Is Payment Gateway
        $isGatewayQuestion = new ChoiceQuestion(
            'Is this a payment gateway addon?',
            ['no', 'yes'],
            0
        );
        $isGateway = $helper->ask($input, $output, $isGatewayQuestion) === 'yes';

        // 4. Pro Compatibility
        $proCompatQuestion = new ChoiceQuestion(
            'Does this addon require WP Travel Engine Pro compatibility?',
            ['no', 'yes'],
            0
        );
        $requiresPro = $helper->ask($input, $output, $proCompatQuestion) === 'yes';

        $settingsType = 'none';
        $useWebpack = false;

        if (!$isGateway) {
            // 5. Settings type (only for non-payment-gateway addons)
            $settingsQuestion = new ChoiceQuestion(
                'What type of settings does this addon need?',
                ['none', 'global', 'trip-edit', 'both'],
                0
            );
            $settingsType = $helper->ask($input, $output, $settingsQuestion);

            // 6. Webpack configuration
            $webpackQuestion = new ChoiceQuestion(
                'Does this addon require Webpack configuration?',
                ['no', 'yes'],
                0
            );
            $useWebpack = $helper->ask($input, $output, $webpackQuestion) === 'yes';
        } else {
            // Payment gateways always have global settings
            $settingsType = 'global';
        }

        // Generate all naming variations
        $names = $this->generateNamingConventions($addonName, $isGateway);

        $data = [
            'addon_name'    => $addonName,
            'description'   => $description,
            'is_gateway'    => $isGateway,
            'requires_pro'  => $requiresPro,
            'settings_type' => $settingsType,
            'use_webpack'   => $useWebpack,
            'names'         => $names,
        ];

        $output->writeln("\n<info>✅ Addon Configuration:</info>");
        $output->writeln(sprintf("  <comment>Name:</comment> %s", $addonName));
        $output->writeln(sprintf("  <comment>Description:</comment> %s", $description));
        $output->writeln(sprintf("  <comment>Type:</comment> %s", $isGateway ? 'Payment Gateway' : 'Basic Addon'));
        $output->writeln(sprintf("  <comment>Pro Compatible:</comment> %s", $requiresPro ? 'Yes' : 'No'));
        $output->writeln(sprintf("  <comment>Settings:</comment> %s", $settingsType));
        $output->writeln(sprintf("  <comment>Webpack:</comment> %s", $useWebpack ? 'Yes' : 'No'));
        $output->writeln(sprintf("  <comment>Full Slug:</comment> %s", $names['full_slug']));
        $output->writeln(sprintf("  <comment>Namespace:</comment> %s", $names['namespace']));

        // Generate addon files
        $this->generateAddonFiles($data, $output);

        return Command::SUCCESS;
    }

    /**
     * Generate all naming conventions from addon name
     *
     * @param string $addonName Original addon name
     * @param bool $isGateway Whether this is a payment gateway
     * @return array
     */
    private function generateNamingConventions(string $addonName, bool $isGateway): array
    {
        // Remove "WP Travel Engine - " prefix if present
        $cleanName = preg_replace('/^WP\s+Travel\s+Engine\s*-\s*/i', '', $addonName);

        // For payment gateways, remove common suffixes to get clean slug
        if ($isGateway) {
            $cleanName = preg_replace('/(Payment\s+Gateway|Gateway|Payment)$/i', '', $cleanName);
            $cleanName = trim($cleanName);
        }

        // Generate slug (kebab-case, lowercase, no prefixes)
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $cleanName), '-'));

        // Function slug (lowercase snake_case for function names)
        $functionSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $cleanName));
        $functionSlug = trim($functionSlug, '_'); // Remove leading/trailing underscores
        $functionSlug = preg_replace('/_+/', '_', $functionSlug); // Replace multiple underscores with single

        // Full slug with wptravelengine prefix (add -payment suffix for gateways)
        $fullSlug = $isGateway
            ? 'wptravelengine-' . $slug . '-payment'
            : 'wptravelengine-' . $slug;

        // Namespace (PascalCase, no spaces, with WPTravelEngine prefix)
        $namespace = 'WPTravelEngine' . str_replace([' ', '-', '_'], '', ucwords($cleanName, ' -_'));

        // Constants (SCREAMING_SNAKE_CASE with prefix)
        $constant = strtoupper(str_replace('-', '_', $slug));

        // Settings key (lowercase, no separators, no prefixes)
        $settingsKey = strtolower(str_replace(['-', '_', ' '], '', $slug));

        // Gateway ID (lowercase_with_underscores_enable)
        $gatewayId = $isGateway ? str_replace('-', '_', $slug) . '_enable' : '';

        // Title (clean name for display, e.g., "Heylight" without "Payment Gateway")
        $title = $cleanName;

        return [
            'slug'         => $slug,                    // e.g., "paystack" or "trip-difficulty-level"
            'full_slug'    => $fullSlug,                // e.g., "wptravelengine-paystack-payment"
            'function_slug' => $functionSlug,           // e.g., "paystack" or "trip_difficulty_level"
            'namespace'    => $namespace,               // e.g., "WPTravelEnginePaystack" or "WPTravelEngineTripDifficultyLevel"
            'constant'     => $constant,                // e.g., "PAYSTACK" or "TRIP_DIFFICULTY_LEVEL"
            'settings_key' => $settingsKey,             // e.g., "paystack" or "difficultylevel"
            'gateway_id'   => $gatewayId,               // e.g., "paystack_enable" or ""
            'title'        => $title,                   // e.g., "Heylight" or "Trip Difficulty Level"
        ];
    }

    /**
     * Generate addon files and directory structure
     *
     * @param array $data Addon configuration data
     * @param OutputInterface $output Console output
     */
    private function generateAddonFiles(array $data, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $addonDir = getcwd() . '/' . $data['names']['full_slug'];

        if ($filesystem->exists($addonDir)) {
            $output->writeln("<error>❌ Directory already exists: {$addonDir}</error>");
            return;
        }

        $stubsPath = __DIR__ . '/../../../stubs';

        // Determine which stub type to use
        $stubType = $data['is_gateway'] ? 'payment-gateway' : 'basic-addon';

        // Create base directories
        $filesystem->mkdir($addonDir);
        $filesystem->mkdir("$addonDir/includes");

        // Generate main plugin file
        $this->generateMainPluginFile($addonDir, $stubsPath, $stubType, $data);

        // Generate Plugin class
        $this->generatePluginClass($addonDir, $stubsPath, $stubType, $data);

        // Generate settings-related files
        if ($data['is_gateway']) {
            $this->generatePaymentGatewayFiles($addonDir, $stubsPath, $data);
        } else {
            $this->generateBasicAddonFiles($addonDir, $stubsPath, $data);
        }

        // Generate configuration files
        $this->generateConfigFiles($addonDir, $stubsPath, $data);

        // Generate source files if webpack is enabled
        if ($data['use_webpack']) {
            $this->generateWebpackFiles($addonDir, $stubsPath, $data);
        }

        $output->writeln("\n<info>🎉 Addon scaffold created successfully!</info>");
        $output->writeln("<comment>Location:</comment> {$addonDir}");
        $output->writeln("\n<info>Next steps:</info>");
        $output->writeln("  1. cd {$data['names']['full_slug']}");
        $output->writeln("  2. composer install");
        if ($data['use_webpack']) {
            $output->writeln("  3. yarn install");
            $output->writeln("  4. yarn run");
        }
    }

    /**
     * Generate main plugin file
     */
    private function generateMainPluginFile(string $addonDir, string $stubsPath, string $stubType, array $data)
    {
        $mainStub = file_get_contents("$stubsPath/$stubType/main-plugin.stub");

        // Replace pro-compatible block
        if ($data['requires_pro']) {
            $proBlock = $this->getProCompatibleBlock($data);
        } else {
            $proBlock = $this->getStandaloneBlock($data);
        }

        $mainStub = str_replace('{{PRO_COMPATIBLE_BLOCK}}', $proBlock, $mainStub);

        $mainContent = $this->replacePlaceholders($mainStub, $data);
        file_put_contents("$addonDir/{$data['names']['full_slug']}.php", $mainContent);
    }

    /**
     * Generate Plugin class file
     */
    private function generatePluginClass(string $addonDir, string $stubsPath, string $stubType, array $data)
    {
        if ($data['is_gateway']) {
            // Payment gateway uses simple Plugin.stub
            $pluginStub = file_get_contents("$stubsPath/$stubType/includes/Plugin.stub");
            $pluginContent = $this->replacePlaceholders($pluginStub, $data);
            file_put_contents("$addonDir/includes/Plugin.php", $pluginContent);
        } else {
            // Basic addon uses Plugin.stub with conditionals
            $pluginStub = file_get_contents("$stubsPath/$stubType/includes/Plugin.stub");
            $pluginContent = $this->buildBasicAddonPluginClass($pluginStub, $data);
            file_put_contents("$addonDir/includes/Plugin.php", $pluginContent);
        }
    }

    /**
     * Build the Plugin class for basic addons with conditional sections
     */
    private function buildBasicAddonPluginClass(string $stub, array $data): string
    {
        $settingsType = $data['settings_type'];
        $useWebpack = $data['use_webpack'];

        // Imports
        $imports = '';
        if ($settingsType !== 'none') {
            $imports .= "use {$data['names']['namespace']}\\Backend\\API;\n";
        }

        $stub = str_replace('{{BACKEND_API_IMPORT}}', $imports, $stub);

        // Hooks
        $hooks = [];

        if ($useWebpack) {
            $hooks[] = "\t\tadd_action( 'admin_enqueue_scripts', array( \$this, 'enqueue_admin_assets' ) );";
        }

        if ($settingsType === 'global' || $settingsType === 'both') {
            $hooks[] = "\t\tadd_filter( 'wptravelengine_settings:tabs:extensions', array( \$this, 'add_global_settings' ) );";
        }

        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $hooks[] = "\t\tadd_filter( 'wp_travel_engine_admin_trip_meta_tabs', array( \$this, 'add_trip_meta_tabs' ) );";
        }

        if ($settingsType !== 'none') {
            $hooks[] = "\n\t\tAPI::register_hooks();";
        }

        $hooksStr = count($hooks) > 0 ? "\n" . implode("\n", $hooks) : '';
        $stub = str_replace('{{ADMIN_ENQUEUE_HOOK}}', '', $stub);
        $stub = str_replace('{{GLOBAL_SETTINGS_HOOK}}', '', $stub);
        $stub = str_replace('{{TRIP_SETTINGS_HOOK}}', '', $stub);
        $stub = str_replace('{{API_REGISTER_CALL}}', $hooksStr, $stub);

        // Enqueue admin assets method
        if ($useWebpack) {
            $enqueueMethod = "\n\t/**\n\t * Enqueue admin script.\n\t *\n\t * @return void\n\t */\n\tpublic function enqueue_admin_assets() {\n\t\t\$admin_script_path = WPTRAVELENGINE_{$data['names']['constant']}_DIR_PATH . 'dist/admin.asset.php';\n\t\t\$screen            = get_current_screen();\n\t\tif ( file_exists( \$admin_script_path ) && ( \$screen->post_type === 'trip' || \$screen->id === 'booking_page_class-wp-travel-engine-admin' ) ) {
\t\t\t\$asset = require \$admin_script_path;\n\t\t\twp_enqueue_script(\n\t\t\t\t'{$data['names']['full_slug']}-admin',\n\t\t\t\tWPTRAVELENGINE_{$data['names']['constant']}_DIR_URL . 'dist/admin.js',\n\t\t\t\tarray_merge( \$asset['dependencies'], array( 'wp-hooks', 'wptravelengine-exports' ) ),\n\t\t\t\t\$asset['version'],\n\t\t\t\ttrue\n\t\t\t);\n\t\t}\n\t}\n";
            $stub = str_replace('{{ENQUEUE_ADMIN_ASSETS_METHOD}}', $enqueueMethod, $stub);
        } else {
            $stub = str_replace('{{ENQUEUE_ADMIN_ASSETS_METHOD}}', '', $stub);
        }

        // Add global settings method
        if ($settingsType === 'global' || $settingsType === 'both') {
            $globalMethod = "\n\t/**\n\t * Add global settings.\n\t *\n\t * @param array \$tab_settings Tab settings.\n\t *\n\t * @return array\n\t */\n\tpublic function add_global_settings( array \$tab_settings ): array {\n\t\t\$tab_settings['sub_tabs'][] = require_once WPTRAVELENGINE_{$data['names']['constant']}_DIR_PATH . 'includes/Builders/global-settings.php';\n\t\treturn \$tab_settings;\n\t}\n";
            $stub = str_replace('{{ADD_GLOBAL_SETTINGS_METHOD}}', $globalMethod, $stub);
        } else {
            $stub = str_replace('{{ADD_GLOBAL_SETTINGS_METHOD}}', '', $stub);
        }

        // Add trip meta method
        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $tripMethod = "\n\t/**\n\t * Add trip meta tab.\n\t *\n\t * @param array \$tabs Trip meta tabs.\n\t *\n\t * @return array\n\t */\n\tpublic function add_trip_meta_tabs( array \$tabs ): array {\n\t\t\$tabs['{$data['names']['slug']}'] = require_once WPTRAVELENGINE_{$data['names']['constant']}_DIR_PATH . 'includes/Builders/trip-meta.php';\n\t\treturn \$tabs;\n\t}\n";
            $stub = str_replace('{{ADD_TRIP_META_METHOD}}', $tripMethod, $stub);
        } else {
            $stub = str_replace('{{ADD_TRIP_META_METHOD}}', '', $stub);
        }

        return $this->replacePlaceholders($stub, $data);
    }

    /**
     * Generate payment gateway specific files
     */
    private function generatePaymentGatewayFiles(string $addonDir, string $stubsPath, array $data)
    {
        $filesystem = new Filesystem();

        // Create Builders directory
        $filesystem->mkdir("$addonDir/includes/Builders");

        // Generate Payment.php
        $paymentStub = file_get_contents("$stubsPath/payment-gateway/includes/Payment.stub");
        $paymentContent = $this->replacePlaceholders($paymentStub, $data);
        file_put_contents("$addonDir/includes/Payment.php", $paymentContent);

        // Generate Builders/API.php
        $apiStub = file_get_contents("$stubsPath/payment-gateway/includes/Builders/API.stub");
        $apiContent = $this->replacePlaceholders($apiStub, $data);
        file_put_contents("$addonDir/includes/Builders/API.php", $apiContent);

        // Generate Builders/global-settings.php
        $settingsStub = file_get_contents("$stubsPath/payment-gateway/includes/Builders/global-settings.stub");
        $settingsContent = $this->replacePlaceholders($settingsStub, $data);
        file_put_contents("$addonDir/includes/Builders/global-settings.php", $settingsContent);
    }

    /**
     * Generate basic addon specific files
     */
    private function generateBasicAddonFiles(string $addonDir, string $stubsPath, array $data)
    {
        $filesystem = new Filesystem();
        $settingsType = $data['settings_type'];

        // Create directories based on settings type
        if ($settingsType !== 'none') {
            $filesystem->mkdir("$addonDir/includes/Backend");
            $filesystem->mkdir("$addonDir/includes/Settings");
            $filesystem->mkdir("$addonDir/includes/Builders");

            // Generate API.php
            $apiStub = file_get_contents("$stubsPath/basic-addon/includes/Backend/API.stub");
            $apiContent = $this->buildAPIClass($apiStub, $data);
            file_put_contents("$addonDir/includes/Backend/API.php", $apiContent);
        }

        // Generate Settings classes
        if ($settingsType === 'global' || $settingsType === 'both') {
            $globalsStub = file_get_contents("$stubsPath/basic-addon/includes/Settings/Globals.stub");
            $globalsContent = $this->replacePlaceholders($globalsStub, $data);
            file_put_contents("$addonDir/includes/Settings/Globals.php", $globalsContent);

            $globalSettingsStub = file_get_contents("$stubsPath/basic-addon/includes/Builders/global-settings.stub");
            $globalSettingsContent = $this->replacePlaceholders($globalSettingsStub, $data);
            file_put_contents("$addonDir/includes/Builders/global-settings.php", $globalSettingsContent);
        }

        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $tripEditsStub = file_get_contents("$stubsPath/basic-addon/includes/Settings/TripEdits.stub");
            $tripEditsContent = $this->replacePlaceholders($tripEditsStub, $data);
            file_put_contents("$addonDir/includes/Settings/TripEdits.php", $tripEditsContent);

            $tripMetaStub = file_get_contents("$stubsPath/basic-addon/includes/Builders/trip-meta.stub");
            $tripMetaContent = $this->replacePlaceholders($tripMetaStub, $data);
            file_put_contents("$addonDir/includes/Builders/trip-meta.php", $tripMetaContent);
        }
    }

    /**
     * Build API class with conditional methods
     */
    private function buildAPIClass(string $stub, array $data): string
    {
        $settingsType = $data['settings_type'];

        // Imports
        $globalImports = '';
        $tripImports = '';

        if ($settingsType === 'global' || $settingsType === 'both') {
            $globalImports = "\nuse WPTravelEngine\\Core\\Controllers\\RestAPI\\V2\\Settings;\nuse {$data['names']['namespace']}\\Settings\\Globals as MyGlobals;";
        }

        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $tripImports = "\nuse WPTravelEngine\\Core\\Controllers\\RestAPI\\V2\\Trip;\nuse {$data['names']['namespace']}\\Settings\\TripEdits as MyTripEdits;";
        }

        $stub = str_replace('{{GLOBAL_IMPORTS}}', $globalImports, $stub);
        $stub = str_replace('{{TRIP_IMPORTS}}', $tripImports, $stub);

        // Hooks
        $globalHooks = '';
        $tripHooks = '';

        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $tripHooks = "\n\t\tadd_filter( 'wptravelengine_trip_api_schema', array( \$instance, 'trip_edit_schema' ), 10, 2 );\n\t\tadd_filter( 'wptravelengine_rest_prepare_trip', array( \$instance, 'prepare_trip_meta' ), 10, 3 );\n\t\tadd_action( 'wptravelengine_api_update_trip', array( \$instance, 'update_trip_meta' ), 10, 2 );";
        }

        if ($settingsType === 'global' || $settingsType === 'both') {
            $globalHooks = "\n\t\tadd_filter( 'wptravelengine_settings_api_schema', array( \$instance, 'global_schema' ), 10, 2 );\n\t\tadd_filter( 'wptravelengine_rest_prepare_settings', array( \$instance, 'prepare_settings' ), 10, 3 );\n\t\tadd_action( 'wptravelengine_api_update_settings', array( \$instance, 'update_settings' ), 10, 2 );";
        }

        $stub = str_replace('{{TRIP_HOOKS}}', $tripHooks, $stub);
        $stub = str_replace('{{GLOBAL_HOOKS}}', $globalHooks, $stub);

        // Methods
        $globalMethods = '';
        $tripMethods = '';

        if ($settingsType === 'global' || $settingsType === 'both') {
            $globalMethods = "\n\t/**\n\t * Add Global Settings Schema.\n\t *\n\t * @param array    \$schema Schema.\n\t * @param Settings \$instance Instance of the Settings class.\n\t *\n\t * @return array\n\t */\n\tpublic function global_schema( array \$schema, Settings \$instance ): array {\n\t\t\$schema['{$data['names']['settings_key']}'] = MyGlobals::get_api_schema();\n\t\treturn \$schema;\n\t}\n\n\t/**\n\t * Prepare Global Settings.\n\t *\n\t * @param array           \$settings Settings.\n\t * @param WP_REST_Request \$request Request.\n\t * @param Settings        \$settings_controller Instance of the Settings class.\n\t *\n\t * @return array\n\t */\n\tpublic function prepare_settings( array \$settings, WP_REST_Request \$request, Settings \$settings_controller ): array {\n\t\t\$settings['{$data['names']['settings_key']}'] = MyGlobals::prepare_api_datas( \$settings_controller );\n\t\treturn \$settings;\n\t}\n\n\t/**\n\t * Update Global Settings.\n\t *\n\t * @param WP_REST_Request \$request Request.\n\t * @param Settings        \$settings_controller Instance of the Settings class.\n\t *\n\t * @return void\n\t */\n\tpublic function update_settings( WP_REST_Request \$request, Settings \$settings_controller ): void {\n\t\tif ( is_array( \$request['{$data['names']['settings_key']}'] ?? null ) ) {\n\t\t\tMyGlobals::update_api_datas( \$request['{$data['names']['settings_key']}'], \$settings_controller );\n\t\t}\n\t}\n";
        }

        if ($settingsType === 'trip-edit' || $settingsType === 'both') {
            $tripMethods = "\n\t/**\n\t * Add Trip Meta Schema.\n\t *\n\t * @param array \$properties Properties.\n\t *\n\t * @return array\n\t */\n\tpublic function trip_edit_schema( array \$properties ): array {\n\t\t\$properties['{$data['names']['settings_key']}'] = MyTripEdits::get_api_schema();\n\t\treturn \$properties;\n\t}\n\n\t/**\n\t * Prepare Trip Meta.\n\t *\n\t * @param array           \$data Data.\n\t * @param WP_REST_Request \$request Request.\n\t * @param Trip            \$controller Instance of the Trip class.\n\t *\n\t * @return array\n\t */\n\tpublic function prepare_trip_meta( array \$data, WP_REST_Request \$request, Trip \$controller ): array {\n\t\t\$data['{$data['names']['settings_key']}'] = MyTripEdits::prepare_api_datas( \$data, \$controller );\n\t\treturn \$data;\n\t}\n\n\t/**\n\t * Update Trip Meta.\n\t *\n\t * @param WP_REST_Request \$request Request.\n\t * @param Trip            \$controller Instance of the Trip class.\n\t *\n\t * @return void\n\t */\n\tpublic function update_trip_meta( WP_REST_Request \$request, Trip \$controller ): void {\n\t\tif ( is_array( \$request['{$data['names']['settings_key']}'] ?? null ) ) {\n\t\t\tMyTripEdits::update_api_datas( \$controller, \$request['{$data['names']['settings_key']}'] );\n\t\t}\n\t}\n";
        }

        $stub = str_replace('{{GLOBAL_METHODS}}', $globalMethods, $stub);
        $stub = str_replace('{{TRIP_METHODS}}', $tripMethods, $stub);

        return $this->replacePlaceholders($stub, $data);
    }

    /**
     * Generate configuration files
     */
    private function generateConfigFiles(string $addonDir, string $stubsPath, array $data)
    {
        // composer.json
        $composerStub = file_get_contents("$stubsPath/config/composer.json.stub");
        if ($data['requires_pro']) {
            $composerStub = str_replace('{{PRO_CONFIG_DEPENDENCY}}', ',
        "codewing-solutions/wptravelengine-pro-config": "dev-main"', $composerStub);
        } else {
            $composerStub = str_replace('{{PRO_CONFIG_DEPENDENCY}}', '', $composerStub);
        }
        $composerContent = $this->replacePlaceholders($composerStub, $data);
        file_put_contents("$addonDir/composer.json", $composerContent);

        // package.json
        $packageStub = file_get_contents("$stubsPath/config/package.json.stub");
        if ($data['use_webpack']) {
            $packageStub = str_replace('{{WEBPACK_SCRIPTS}}', "\n        \"start\": \"npx wp-scripts start --mode development\",\n        \"build\": \"npx wp-scripts build --mode production\",", $packageStub);
            $packageStub = str_replace('{{WEBPACK_BUILD}}', " && npm run build", $packageStub);
            $packageStub = str_replace('{{WEBPACK_DEV_DEPENDENCIES}}', "\n        \"@wordpress/scripts\": \"^30.5.1\",\n        \"lodash\": \"^4.17.21\",\n        \"react-hook-form\": \"~7.54.2\",", $packageStub);
            $packageStub = str_replace('{{WEBPACK_DEPENDENCIES}}', ',
    "dependencies": {
        "@emotion/react": "^11.14.0",
        "@emotion/styled": "^11.14.1",
        "@wordpress/block-editor": "^14.2.0",
        "@wordpress/blocks": "^14.2.0",
        "react-query": "^3.39.3",
        "react-toastify": "^10.0.6",
        "styled-components": "^6.1.13"
    }', $packageStub);
        } else {
            $packageStub = str_replace('{{WEBPACK_SCRIPTS}}', '', $packageStub);
            $packageStub = str_replace('{{WEBPACK_BUILD}}', '', $packageStub);
            $packageStub = str_replace('{{WEBPACK_DEV_DEPENDENCIES}}', '', $packageStub);
            $packageStub = str_replace('{{WEBPACK_DEPENDENCIES}}', '', $packageStub);
        }
        $packageContent = $this->replacePlaceholders($packageStub, $data);
        file_put_contents("$addonDir/package.json", $packageContent);

        // Gruntfile.js
        $gruntStub = file_get_contents("$stubsPath/config/Gruntfile.js.stub");
        if ($data['use_webpack']) {
            $gruntStub = str_replace('{{FILE_LIST}}', "\n        'dist/**',", $gruntStub);
        } else {
            $gruntStub = str_replace('{{FILE_LIST}}', '', $gruntStub);
        }
        $gruntContent = $this->replacePlaceholders($gruntStub, $data);
        file_put_contents("$addonDir/Gruntfile.js", $gruntContent);

        // phpcs.xml
        $phpcsStub = file_get_contents("$stubsPath/config/phpcs.xml.stub");
        $phpcsContent = $this->replacePlaceholders($phpcsStub, $data);
        file_put_contents("$addonDir/phpcs.xml", $phpcsContent);

        // readme.txt
        $readmeStub = file_get_contents("$stubsPath/config/readme.txt.stub");
        $readmeContent = $this->replacePlaceholders($readmeStub, $data);
        file_put_contents("$addonDir/readme.txt", $readmeContent);

        // .gitignore
        $gitignoreStub = file_get_contents("$stubsPath/config/.gitignore.stub");
        file_put_contents("$addonDir/.gitignore", $gitignoreStub);
    }

    /**
     * Generate webpack source files
     */
    private function generateWebpackFiles(string $addonDir, string $stubsPath, array $data)
    {
        $filesystem = new Filesystem();

        // Create src directories
        $filesystem->mkdir("$addonDir/src/admin/js", 0755, true);
        $filesystem->mkdir("$addonDir/src/public/js", 0755, true);
        $filesystem->mkdir("$addonDir/src/public/scss");

        // Copy webpack config
        $webpackStub = file_get_contents("$stubsPath/config/webpack.config.js.stub");
        file_put_contents("$addonDir/webpack.config.js", $webpackStub);

        // Generate source files
        $adminJsStub = file_get_contents("$stubsPath/basic-addon/src/admin/js/index.stub");
        $adminJsContent = $this->replacePlaceholders($adminJsStub, $data);
        file_put_contents("$addonDir/src/admin/js/index.js", $adminJsContent);

        $publicJsStub = file_get_contents("$stubsPath/basic-addon/src/public/js/index.stub");
        $publicJsContent = $this->replacePlaceholders($publicJsStub, $data);
        file_put_contents("$addonDir/src/public/js/index.js", $publicJsContent);

        $publicScssStub = file_get_contents("$stubsPath/basic-addon/src/public/scss/index.stub");
        $publicScssContent = $this->replacePlaceholders($publicScssStub, $data);
        file_put_contents("$addonDir/src/public/scss/index.scss", $publicScssContent);
    }

    /**
     * Get pro-compatible initialization block
     */
    private function getProCompatibleBlock(array $data): string
    {
        $namespace = $data['names']['namespace'];
        $slug = $data['names']['full_slug'];
        $constant = $data['names']['constant'];

        return "add_action( 'plugins_loaded', function () {
\twptravelengine_pro_config( __FILE__, array(
\t\t'id'           => 557,
\t\t'slug'         => '{$slug}',
\t\t'plugin_name'  => '{$data['addon_name']}',
\t\t'file_path'    => __FILE__,
\t\t'version'      => WPTRAVELENGINE_{$constant}_VERSION,
\t\t'dependencies' => [],
\t\t'execute'      => '{$namespace}\\Plugin',
\t) );
}, 9 );";
    }

    /**
     * Get standalone initialization block
     */
    private function getStandaloneBlock(array $data): string
    {
        $namespace = $data['names']['namespace'];

        return "/**
 * Load plugin after checking dependencies.
 */
function wptravelengine_{$data['names']['function_slug']}_init() {
\trequire_once __DIR__ . '/vendor/autoload.php';

\t// Initialize the plugin
\tif ( class_exists( '{$namespace}\\Plugin' ) ) {
\t\t{$namespace}\\Plugin::execute();
\t}
}
add_action( 'plugins_loaded', 'wptravelengine_{$data['names']['function_slug']}_init', 9 );";
    }

    /**
     * Replace placeholders in content
     */
    private function replacePlaceholders(string $content, array $data): string
    {
        $replacements = [
            '{{ADDON_NAME}}'    => $data['addon_name'],
            '{{DESCRIPTION}}'   => $data['description'],
            '{{SLUG}}'          => $data['names']['slug'],
            '{{FUNCTION_SLUG}}' => $data['names']['function_slug'],
            '{{FULL_SLUG}}'     => $data['names']['full_slug'],
            '{{NAMESPACE}}'     => $data['names']['namespace'],
            '{{CONSTANT}}'      => $data['names']['constant'],
            '{{SETTINGS_KEY}}'  => $data['names']['settings_key'],
            '{{GATEWAY_ID}}'    => $data['names']['gateway_id'],
            '{{TITLE}}'         => $data['names']['title'],
        ];

        return strtr($content, $replacements);
    }
}
