# farm_piknik

Connect your farm to [Piknik](https://piknik.spot), a local food marketplace that helps farmers sell surplus food directly to buyers in their community.

## About Piknik

Piknik is a platform for local food commerce that connects farmers, processors, markets, restaurants, and food buyers. It features:

- **Marketplace listings** for surplus food, wanted-to-buy ads, and for-sale items
- **Local food directory** of farms, markets, and food businesses
- **Automated listings** from harvest records (harvest-triggered offers)
- **Connection management** with OAuth-based authentication
- **Listing status tracking** and buyer interest visibility

## Features

This farmOS module provides:

- **OAuth connection setup** - Securely connect your farm to Piknik via OAuth 2.0 with PKCE
- **Harvest-triggered listings** - Automatically create marketplace listings from harvest records
- **Board integration** - View and manage harvest-triggered offers on the farmOS board
- **Connection test** - Verify your Piknik connection is working
- **Listing status display** - Track listing status (active, completed, cancelled) and buyer interest
- **Clean disconnect** - Revoke credentials on Piknik and clear them locally when disconnecting
- **Place management** - Link your farm's place to Piknik

## Requirements

- farmOS 4.x (Drupal 11)
- `farm_harvest` module (for harvest-triggered listings)
- `farm_oauth_client` module (included as a submodule)

## Installation

### Method 1: Composer (recommended when published on drupal.org)

```bash
cd /path/to/farmos
composer require drupal/farm_piknik
```

Note: This requires the module to be published as a project on drupal.org. Once published, it will be automatically available on packages.drupal.org under the `drupal/` namespace. Until then, use manual installation below.

To publish on drupal.org:
1. Create a project at https://www.drupal.org/node/add/project-project
2. Select "Module" as the project type
3. Use `farm_piknik` as the project machine name
4. The module will then be available via Composer as `drupal/farm_piknik`

### Method 2: Manual installation

If you cannot use Composer, you can install the module manually:

1. Download or clone this repository
2. Copy the entire `farm_piknik` directory to your farmOS modules directory:
   - Docker: `/opt/drupal/web/modules/farm/farm_piknik`
   - Standard: `/var/www/html/web/modules/farm/farm_piknik`
3. Ensure the `farm_oauth_client` submodule is present in `modules/farm/farm_oauth_client/`

The module should now be available for installation.

## Enabling the module

Once installed, enable the module through the farmOS interface:

1. Navigate to `/admin/modules` or use the Extend menu
2. Find "Piknik" in the module list
3. Check the box to enable it
4. Click "Install"

Alternatively, use Drush:

```bash
drush en farm_piknik
```

## Setup

After enabling the module:

1. Navigate to the Piknik settings page (typically under `/admin/config/services/piknik`)
2. Click "Connect to Piknik" to initiate the OAuth flow
3. You will be redirected to Piknik to authorize the connection
4. After authorization, you will be returned to farmOS with an active connection
5. Use the "Test connection" button to verify the connection is working

## Configuration

Configure harvest-triggered listings:

1. Go to the Piknik settings page
2. Set up automatic listing rules for harvest records
3. Configure default pricing, quantities, and fulfillment options
4. Manage trigger rules on the farmOS board

## Disconnecting

To disconnect from Piknik:

1. Navigate to the Piknik settings page
2. Click "Disconnect"
3. This will revoke your credentials on Piknik and clear them locally
4. Your farm will be in a clean signed-out state

## License

GPL-2.0

## Maintainers

- Geoff Whittington

## Links

- [Piknik](https://piknik.spot)
- [farmOS](https://farmos.org)
- [farmOS documentation](https://farmos.org/documentation)
