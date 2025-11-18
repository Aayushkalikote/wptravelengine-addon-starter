# WP Travel Engine Addon Starter

A powerful Composer package to quickly scaffold WP Travel Engine addon plugins with customizable options.

## Features

- 🚀 **Quick Scaffolding** - Generate complete addon structure in seconds
- 💳 **Payment Gateway Support** - Pre-configured payment gateway templates
- ⚙️ **Flexible Settings** - Global settings, trip-edit settings, or both
- 📦 **Webpack Ready** - Optional webpack configuration
- 🎯 **Two Installation Modes** - WP-CLI or standalone Composer CLI
- ✨ **Interactive Prompts** - User-friendly guided setup
- 🔧 **Pro Compatible** - Optional WP Travel Engine Pro integration

## Requirements

- PHP 7.4 or higher (PHP 8.0+ recommended)
- Composer 2.0 or higher
- WP Travel Engine 6.0.0 or higher (for generated addons)

**Note**: While this package supports PHP 7.4+, PHP 8.0 or higher is recommended for better performance and compatibility.

## Installation

### Method 1: WP-CLI Package (Recommended for WordPress Developers)

Install as a WP-CLI package to create addons directly in your WordPress plugins directory:

```bash
wp package install wptravelengine/addonstarter
```

### Method 2: Global Composer Command (Recommended for Package Developers)

Install globally to use anywhere:

```bash
composer global require wptravelengine/addonstarter

# Add Composer's global bin to your PATH
export PATH="$HOME/.config/composer/vendor/bin:$PATH"
```

## Usage

### Using WP-CLI

Navigate to your WordPress installation and run:

```bash
cd /path/to/wordpress
wp wptravelengine-addon-starter scaffold
```

The addon will be created in `wp-content/plugins/` directory.

**With flags (non-interactive):**
```bash
wp wptravelengine-addon-starter scaffold \
  --name="Stripe Payment Gateway" \
  --type=payment-gateway \
  --pro
```

### Using Standalone Composer CLI

Run from any directory:

```bash
wptravelengine-addon-starter make:addon
```

The addon will be created in your current directory.

## Examples

### Creating a Payment Gateway

```bash
wptravelengine-addon-starter make:addon

# Follow the prompts:
Addon Name: PayStack Payment Gateway
Description: PayStack payment gateway for WP Travel Engine
Is this a payment gateway? yes
Pro Compatible? no
```

**Generated structure:**
```
wptravelengine-paystack-payment/
├── wptravelengine-paystack-payment.php  # Main plugin file
├── composer.json                         # Composer config
├── package.json                          # NPM config
├── phpcs.xml                            # Coding standards
└── includes/
    ├── Plugin.php                       # Main plugin class
    ├── Payment.php                      # Payment gateway class
    └── Builders/
        ├── API.php                      # REST API handler
        └── global-settings.php          # Settings configuration
```

### Creating a Basic Addon

```bash
wptravelengine-addon-starter make:addon

# Follow the prompts:
Addon Name: Trip Difficulty Level
Description: Add difficulty levels to trips
Is this a payment gateway? no
Pro Compatible? no
Settings type? both
Webpack? yes
```

**Generated structure:**
```
wptravelengine-trip-difficulty-level/
├── wptravelengine-trip-difficulty-level.php
├── composer.json
├── package.json
├── phpcs.xml
├── webpack.config.js
├── includes/
│   ├── Plugin.php
│   ├── Backend/
│   │   └── API.php
│   ├── Settings/
│   │   ├── Globals.php
│   │   └── TripEdits.php
│   └── Builders/
│       ├── global-settings.php
│       └── trip-meta.php
└── src/
    ├── admin/
    └── public/
```

## Naming Conventions

The scaffolder automatically handles naming:

| Input | Type | Output Directory |
|-------|------|------------------|
| "PayStack Gateway" | Payment | `wptravelengine-paystack-payment/` |
| "Stripe Payment" | Payment | `wptravelengine-stripe-payment/` |
| "Trip Notes" | Basic | `wptravelengine-trip-notes/` |

## After Scaffolding

Once your addon is created:

```bash
cd wptravelengine-your-addon

# Install PHP dependencies
composer install

# If using webpack
npm install
npm run build
```

### Activate in WordPress

```bash
wp plugin activate wptravelengine-your-addon

# Or via WordPress admin
# Plugins → Installed Plugins → Activate
```

## Uninstallation

### Remove WP-CLI Package

If installed via WP-CLI, remove it using:

```bash
wp package uninstall wptravelengine/addonstarter
```

**Verify removal:**
```bash
wp package list
```

### Remove Global Composer Package

If installed globally via Composer, remove it using:

```bash
composer global remove wptravelengine/addonstarter
```

**Verify removal:**
```bash
composer global show | grep wptravelengine
```

### Remove Generated Addons

To remove a generated addon from your WordPress site:

**Using WP-CLI:**
```bash
# Deactivate first
wp plugin deactivate wptravelengine-your-addon

# Then delete
wp plugin delete wptravelengine-your-addon
```

**Manually:**
```bash
# Navigate to plugins directory
cd /path/to/wordpress/wp-content/plugins

# Remove the addon directory
rm -rf wptravelengine-your-addon
```

**Via WordPress Admin:**
1. Go to **Plugins → Installed Plugins**
2. Deactivate the addon
3. Click **Delete** once it's deactivated

## Options

### Available Flags (Non-Interactive Mode)

| Flag | Description | Values |
|------|-------------|--------|
| `--name` | Addon name | Any string |
| `--description` | Addon description | Any string |
| `--type` | Addon type | `payment-gateway`, `basic` |
| `--pro` | Requires WP Travel Engine Pro | Flag (no value) |
| `--settings` | Settings type (basic addons only) | `none`, `global`, `trip-edit`, `both` |
| `--webpack` | Include webpack setup | Flag (no value) |

### Examples

**Payment gateway with Pro:**
```bash
wptravelengine-addon-starter make:addon \
  --name="Razorpay Payment" \
  --description="Razorpay gateway for WPE" \
  --type=payment-gateway \
  --pro
```

**Basic addon with global settings:**
```bash
wptravelengine-addon-starter make:addon \
  --name="Extra Services" \
  --settings=global
```

## What Gets Generated

### Payment Gateway Addons
- Main plugin file with WordPress headers
- Plugin class with singleton pattern
- Payment gateway class extending `WPTravelEngine\PaymentGateways\BaseGateway`
- REST API handler for settings
- Global settings configuration
- Composer and NPM configurations
- PHPCS configuration
- README template

### Basic Addons
- Main plugin file
- Plugin class
- Conditional settings (global/trip-edit/both)
- REST API handlers (if settings enabled)
- Settings controllers
- Optional webpack configuration
- All configuration files

## Troubleshooting

### Command not found after global install

Add Composer's global bin directory to your PATH:

**Linux/macOS (Bash):**
```bash
echo 'export PATH="$HOME/.config/composer/vendor/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

**Linux/macOS (Zsh):**
```bash
echo 'export PATH="$HOME/.config/composer/vendor/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

**Linux/macOS (Fish):**
```bash
set -Ux fish_user_paths $HOME/.config/composer/vendor/bin $fish_user_paths
```

### WP-CLI command not registered

Ensure the package is installed:
```bash
wp package list
```

If not listed, reinstall:
```bash
wp package install wptravelengine/addonstarter
```

### Permission denied

Make the binary executable:
```bash
chmod +x ~/.config/composer/vendor/bin/wptravelengine-addon-starter
```

## Documentation

- [Getting Started Guide](GETTING-STARTED.md) - Complete setup guide
- [Publishing Guide](PUBLISHING.md) - How to publish your own packages
- [Workflow Guide](WORKFLOW.md) - Distribution workflow
- [Testing Guide](TESTING-LOCAL.md) - Local testing instructions

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- **Issues**: [GitHub Issues](https://github.com/wptravelengine/wptravelengine-addon-starter/issues)
- **Documentation**: [GitHub Wiki](https://github.com/wptravelengine/wptravelengine-addon-starter/wiki)

## License

GPL-3.0-or-later

## Author

**WP Travel Engine**
- Website: [wptravelengine.com](https://wptravelengine.com)
- Email: info@wptravelengine.com

---

**Made with ❤️ by WP Travel Engine Team**
# wptravelengine-addon-starter
