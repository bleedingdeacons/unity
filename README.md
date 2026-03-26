# Unity

A WordPress plugin for managing intergroup — groups, meetings, members, positions, locations, and contacts — built with a clean, interface-driven architecture.

**Version:** 1.10.5
**Requires:** WordPress 6.0+ · PHP 8.0+
**License:** MIT (Modified — No Resale)

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
- [Architecture](#architecture)
- [Service Registration](#service-registration)
- [Usage Examples](#usage-examples)
- [ACF Integration](#acf-integration)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Unity provides a robust framework for intergroup coordination within WordPress. It manages the full lifecycle of groups, their meetings, members, officer positions, physical locations, and contact information — including support for intergroup-level meetings and attendance tracking.

The plugin ships as a **headless service layer**: it defines all domain interfaces and provides the dependency injection container, caching, configuration, and change-tracking infrastructure. Concrete implementations of repositories and factories are registered by the consuming site or companion plugin via WordPress hooks.

---

## Features

**Domain Management**
- Groups with contact details, digital contribution options (Venmo, PayPal, Square), district assignments, and notes
- Meetings with scheduling (day/time), online meeting support, location binding, and type categorisation
- Members with anonymous name support, profile visibility controls, GSR status, and intergroup positions
- Positions with change tracking and view rendering
- Locations with full address details
- Contacts shared across groups, meetings, and members
- Intergroup meetings with separate group and officer attendance tracking

**Technical**
- PSR-11 compatible dependency injection container
- Interface-driven design — every domain entity, repository, and factory is defined by an interface
- Repository and factory patterns for clean data access and object creation
- Built-in change tracking for groups, members, and positions
- WordPress-native caching layer
- PSR-4 autoloading
- Cross-platform build script for packaging production and development archives

---

## Requirements

- PHP 8.0 or higher (8.1+ recommended)
- WordPress 6.0 or higher
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) (recommended — field configuration JSON files are included)

---

## Installation

### Manual

1. Download the plugin archive.
2. Extract it into `wp-content/plugins/unity/`.
3. Run `composer install --no-dev` from the plugin directory.
4. Activate **Unity** in the WordPress admin under **Plugins**.

### Composer

```bash
composer require bleeding-deacons/unity
```

### ACF Fields

If you are using Advanced Custom Fields, import the bundled field configuration:

1. Navigate to **ACF → Tools → Import**.
2. Select `setup/Unity_ACF.json` (development) or `setup/unity-prod-acf.json` (production).
3. Click **Import**.

---

## Getting Started

Unity's container is booted automatically on `plugins_loaded`. Before services can be resolved, you must register your concrete implementations via the `unity/register_services` hook.

A minimal setup in your theme's `functions.php` or a companion plugin:

```php
add_action('unity/register_services', function (\Unity\Core\Interfaces\Container $container) {
    // Register a meeting factory
    $container->register(
        \Unity\Meetings\Interfaces\MeetingFactory::class,
        fn($c) => new MyMeetingFactory(
            $c->get(\Unity\Contacts\Interfaces\ContactFactory::class),
            $c->get(\Unity\Locations\Interfaces\LocationRepository::class)
        )
    );

    // Register a meeting repository
    $container->register(
        \Unity\Meetings\Interfaces\MeetingRepository::class,
        fn($c) => new MyMeetingRepository(
            $c->get(\Unity\Meetings\Interfaces\MeetingFactory::class),
            $c->get(\Unity\Core\Interfaces\Cache::class)
        )
    );

    // ... register remaining factories and repositories
});
```

Once services are registered, access them anywhere via the global helper:

```php
$groups = unity()->get(\Unity\Groups\Interfaces\GroupRepository::class)->findAll();
```

---

## Architecture

### Directory Structure

```
unity/
├── Unity.php                  # Main plugin bootstrap
├── src/
│   ├── Plugin.php             # Plugin singleton & container lifecycle
│   ├── Core/
│   │   ├── DependencyContainer.php
│   │   ├── DependencyNotRegisteredException.php
│   │   ├── UnityConfiguration.php
│   │   ├── UnityServiceProvider.php
│   │   ├── WordPressCache.php
│   │   └── Interfaces/       # Cache, Configuration, Container
│   ├── Contacts/Interfaces/
│   ├── Groups/Interfaces/
│   ├── IntergroupMeetings/Interfaces/
│   ├── Locations/Interfaces/
│   ├── Meetings/Interfaces/
│   ├── Members/Interfaces/
│   └── Positions/Interfaces/
├── setup/                     # ACF field configuration JSON
├── tests/                     # PHPUnit test suite
├── build.php                  # Cross-platform build script
├── composer.json
├── phpunit.xml
└── phpstan.neon
```

### Hooks

| Hook | Timing | Purpose |
|---|---|---|
| `unity/register_services` | After container creation, before service resolution | Register your factory and repository implementations |
| `unity/loaded` | After all services are initialised | Safe to consume Unity services |

### Plugin Lifecycle

1. `Unity.php` loads on `plugins_loaded` (priority 10).
2. The `DependencyContainer` is created and `UnityServiceProvider` registers core services (cache, configuration).
3. The `unity/register_services` action fires — your code registers domain services here.
4. Core tracker services (`GroupChangeTracker`, `MemberChangeTracker`, `PositionChangeTracker`) are eagerly resolved.
5. The `unity/loaded` action fires — Unity is fully operational.

---

## Service Registration

The following interfaces **must** be registered by the consuming site via `unity/register_services`:

**Factories:** `ContactFactory`, `GroupFactory`, `LocationFactory`, `MeetingFactory`, `MemberFactory`, `PositionFactory`, `IntergroupMeetingFactory`, `IntergroupMeetingGroupAttendanceFactory`, `IntergroupMeetingOfficerAttendanceFactory`

**Repositories:** `GroupRepository`, `LocationRepository`, `MeetingRepository`, `MemberRepository`, `PositionRepository`, `IntergroupMeetingRepository`, `IntergroupMeetingGroupAttendanceRepository`, `IntergroupMeetingOfficerAttendanceRepository`

**Change Trackers:** `GroupChangeTracker`, `MemberChangeTracker`, `PositionChangeTracker`

**View Factories:** `GroupViewFactory`, `MeetingViewFactory`, `PositionViewFactory`

The core services (`Cache`, `Configuration`) are pre-registered by Unity.

---

## Usage Examples

### Groups

```php
$repo  = unity()->get(\Unity\Groups\Interfaces\GroupRepository::class);
$group = $repo->findById(123);

echo $group->getTitle();
echo $group->getEmail();
echo $group->getWebsite();

if ($group->hasContributionOptions()) {
    echo $group->getVenmo();   // e.g. @GroupName
    echo $group->getPaypal();
    echo $group->getSquare();  // e.g. $GroupName
}

$meetings = $group->getMeetings();
$contacts = $group->getContacts();
```

### Meetings

```php
$repo    = unity()->get(\Unity\Meetings\Interfaces\MeetingRepository::class);
$meeting = $repo->findById(456);

echo $meeting->getName();
echo $meeting->getDayOfWeek() . ' ' . $meeting->getTime();

if ($meeting->isOnline()) {
    echo $meeting->getOnlineLink();
}

$location = $meeting->getLocation();
```

### Members

```php
$repo   = unity()->get(\Unity\Members\Interfaces\MemberRepository::class);
$member = $repo->findById(789);

echo $member->getAnonymousName();
echo $member->getPersonalEmail();
echo $member->getMobileNumber();
```

---

## ACF Integration

Unity ships with pre-built ACF field group configurations in the `setup/` directory:

- `unity_v1.json` — Custom Types and Field Groups (Required for Tsml-for-Unity & Unity)

- `Manual Install required`

Import via **ACF → Tools → Import**.

---

## Development

### Setup

```bash
git clone <repository-url>
cd unity
composer install
```

### Commands

| Command | Description |
|---|---|
| `composer test` | Run the full PHPUnit test suite |
| `composer test:unit` | Run unit tests only |
| `composer test:integration` | Run integration tests only |
| `composer test:coverage` | Generate an HTML coverage report |
| `composer stan` | Run PHPStan static analysis (level 5) |
| `composer cs` | Check WordPress coding standards |
| `composer cs:fix` | Auto-fix coding standard violations |
| `composer check` | Run CS + PHPStan + tests in sequence |

### Build

```bash
composer build:production   # Package for distribution (excludes tests/dev files)
composer build:dev          # Package with dev files included
composer build:clean        # Remove build artifacts
```

### Testing Stack

- **PHPUnit** 9/10 for unit and integration tests
- **WP_Mock** for WordPress function mocking
- **Mockery** for general mocking
- **PHPStan** (level 5) with the WordPress extension for static analysis
- **PHP_CodeSniffer** with the WordPress standard

---

## Troubleshooting

**"Services not registered" admin notice**
No code is listening on `unity/register_services`. Make sure your theme or companion plugin hooks into this action and registers all required factories, repositories, and trackers.

**`DependencyNotRegisteredException`**
You are resolving a service that hasn't been registered. Check that the interface class name matches exactly when calling `$container->register(...)`.

**Plugin fails to activate**
Verify PHP ≥ 8.0 and WordPress ≥ 6.0. Enable `WP_DEBUG` and `WP_DEBUG_LOG` and check `wp-content/debug.log` for details.

---

## Contributing

1. Fork the repository and create a feature branch.
2. Write tests for any new functionality.
3. Ensure `composer check` passes (coding standards, static analysis, and tests).
4. Submit a pull request with a clear description of the change.

All public APIs must be defined by interfaces. PHPStan level 5 compliance is required.

---

## License

MIT License (Modified) — Copyright © 2025 The Bleeding Deacons

You may use, modify, and distribute this software freely **except** that you may not sell it, alone or as part of an aggregate distribution. See [LICENSE](LICENSE) for full terms.

---

## Support

- **Email:** thebleedingdeacons@gmail.com
