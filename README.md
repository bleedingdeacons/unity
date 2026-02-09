# Unity - WordPress Intergroup Management Plugin

**Version:** 1.2.4  
**Author:** The Bleeding Deacons  
**License:** MIT (Modified - No Resale)  
**Requires WordPress:** 6.0+  
**Requires PHP:** 8.0+

## Overview

Unity is a comprehensive WordPress plugin designed for managing intergroups,  It provides a robust framework for managing groups, meetings, members, positions, locations, and contacts with a clean, object-oriented architecture.

## Features

### Core Functionality

- **Group Management**: Create and manage multiple groups with detailed information including contact details and organizational structure
- **Meeting Management**: Track regular meetings with scheduling, location information, online meeting support, and meeting types
- **Member Management**: Maintain member databases with contact information and role assignments
- **Position Management**: Define and track organizational positions and responsibilities
- **Location Management**: Store and manage physical meeting locations with full address details
- **Contact Management**: Centralized contact information for groups, meetings, and members
- **Intergroup Meetings**: Support for inter-group coordination and special meetings
- **Change Tracking**: Built-in change tracking for groups, members, and positions
- **Caching**: WordPress-integrated caching for optimal performance

### Technical Features

- **Dependency Injection Container**: Modern IoC container for service management
- **Interface-Driven Design**: Fully interface-based architecture for extensibility
- **PSR-4 Autoloading**: Standards-compliant autoloading
- **Repository Pattern**: Clean data access layer
- **Factory Pattern**: Flexible object creation
- **WordPress Integration**: Native WordPress hooks and actions
- **Advanced Custom Fields (ACF) Support**: JSON configuration files included

## Installation

### Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Advanced Custom Fields (ACF) plugin (recommended)

### Basic Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the WordPress 'Plugins' menu
5. Import ACF field configurations from `/setup/Unity_ACF.json` (if using ACF)

### Composer Installation

```bash
composer require bleeding-deacons/unity
```

## Architecture

### Directory Structure

```
Unity/
├── src/
│   ├── Configuration/      # Plugin configuration and field definitions
│   ├── Contact/           # Contact management
│   ├── Core/              # Core services (DI container, cache, service provider)
│   ├── Groups/            # Group management and repositories
│   ├── IntergroupMeetings/ # Inter-group meeting coordination
│   ├── Locations/         # Location management
│   ├── Meetings/          # Meeting management and scheduling
│   ├── Members/           # Member management
│   └── Positions/         # Position/role management
├── setup/                 # ACF field configurations
├── tests/                 # PHPUnit tests
├── Unity.php             # Main plugin file
├── build.php             # Build script
└── composer.json         # Composer configuration
```

### Design Patterns

**Dependency Injection Container**
```php
// Access the container
$container = unity();

// Resolve services
$meetingRepo = $container->get(MeetingRepository::class);
```

**Repository Pattern**
```php
// All data access goes through repositories
$groups = $groupRepository->findAll();
$meeting = $meetingRepository->findById($id);
```

**Factory Pattern**
```php
// Object creation through factories
$group = $groupFactory->create($data);
$member = $memberFactory->create($memberData);
```

## Configuration

### WordPress Hooks

Unity provides several hooks for extensibility:

**`unity_register_services`**
Fires after Unity's container is created but before services are resolved. Use this to register custom service implementations.

```php
add_action('unity_register_services', function($container) {
    $container->register(MeetingFactory::class, function($c) {
        return new CustomMeetingFactory(
            $c->get(ContactFactoryInterface::class),
            $c->get(LocationRepository::class)
        );
    });
});
```

**`unity_loaded`**
Fires after Unity is fully loaded and all services are initialized.

```php
add_action('unity_loaded', function($container) {
    // Your code here - all Unity services are available
    $groups = $container->get(GroupRepository::class)->findAll();
});
```

### Custom Service Registration

Unity uses a dependency injection container that requires certain services to be registered by the implementing site. The following services must be registered via the `unity_register_services` hook:

- `MeetingFactory`
- `MeetingRepository`
- `GroupFactory`
- `GroupRepository`
- `LocationFactory`
- `LocationRepository`
- `MemberFactory`
- `MemberRepository`
- `PositionFactory`
- `PositionRepository`
- `IntergroupMeetingFactory`
- `IntergroupMeetingRepository`

Example implementation:

```php
add_action('unity_register_services', function($container) {
    // Register Meeting Factory
    $container->register(MeetingFactory::class, function($c) {
        return new MeetingFactory(
            $c->get(ContactFactoryInterface::class),
            $c->get(LocationRepository::class)
        );
    });
    
    // Register Meeting Repository
    $container->register(MeetingRepository::class, function($c) {
        return new MeetingRepository(
            $c->get(MeetingFactory::class),
            $c->get(Cache::class)
        );
    });
    
    // ... register other required services
});
```

## Usage

### Basic Usage Examples

#### Working with Groups

```php
// Get the container
$container = unity();

// Get the group repository
$groupRepo = $container->get(GroupRepository::class);

// Find all groups
$groups = $groupRepo->findAll();

// Get a specific group
$group = $groupRepo->findById(123);

// Access group data
echo $group->getTitle();
echo $group->getEmail();
echo $group->getWebsite();
$meetings = $group->getMeetings();
$contacts = $group->getContacts();

// Check for payment options
if ($group->hasContributionOptions()) {
    echo $group->getVenmo();
    echo $group->getPaypal();
    echo $group->getSquare();
}
```

#### Working with Meetings

```php
$meetingRepo = $container->get(MeetingRepository::class);

$meeting = $meetingRepo->findById(456);

// Access meeting details
echo $meeting->getName();
echo $meeting->getDayOfWeek();
echo $meeting->getTime();
echo $meeting->getEndTime();

// Check if online
if ($meeting->isOnline()) {
    echo $meeting->getOnlineLink();
    echo $meeting->getOnlineNotes();
}

// Get location
$location = $meeting->getLocation();
if ($location) {
    echo $location->getAddress();
    echo $location->getCity();
}

// Get meeting types
$types = $meeting->getTypes();
```

#### Working with Members

```php
$memberRepo = $container->get(MemberRepository::class);

$member = $memberRepo->findById(789);

echo $member->getName();
echo $member->getEmail();
echo $member->getPhone();

// Get member's groups
$groups = $member->getGroups();

// Get member's positions
$positions = $member->getPositions();
```

#### Change Tracking

```php
// Track group changes
$groupTracker = $container->get(GroupChangeTracker::class);
// Implement change tracking logic

// Track member changes
$memberTracker = $container->get(MemberChangeTracker::class);
// Implement change tracking logic

// Track position changes
$positionTracker = $container->get(PositionChangeTracker::class);
// Implement change tracking logic
```

### Advanced Custom Fields (ACF) Integration

Unity includes pre-configured ACF field groups for managing all data types. Import the JSON files from the `/setup/` directory:

1. Go to **ACF → Tools → Import**
2. Import `Unity_ACF.json` for development
3. Or import `unity-prod-acf.json` for production

## Development

### Setting Up Development Environment

```bash
# Clone the repository
git clone [repository-url]

# Install dependencies
composer install

# Run tests
composer test

# Run PHPStan static analysis
composer stan

# Check code style
composer cs

# Fix code style
composer cs:fix
```

### Build Process

```bash
# Production build
composer build:production

# Development build
composer build:dev

# Clean build artifacts
composer build:clean
```

### Testing

Unity uses PHPUnit for testing:

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration

# Generate coverage report
composer test:coverage
```

### Code Quality

```bash
# Run all quality checks
composer check

# This runs:
# - Code sniffer (cs)
# - PHPStan static analysis (stan)
# - PHPUnit tests (test)
```

## API Reference

### Core Interfaces

#### Group

```php
interface Group
{
    public function getId(): int;
    public function getTitle(): string;
    public function getEmail(): string;
    public function getMeetings(): array;
    public function getLink(): string;
    public function isValid(): bool;
    public function getGroupNotes(): string;
    public function getWebsite(): string;
    public function getPhone(): string;
    public function getVenmo(): string;
    public function getPaypal(): string;
    public function getSquare(): string;
    public function getDistrictId(): ?int;
    public function getLastContact(): ?string;
    public function getContacts(): array;
    public function hasContributionOptions(): bool;
}
```

#### Meeting

```php
interface Meeting
{
    public function getId(): int;
    public function getName(): string;
    public function getSlug(): string;
    public function getLocation(): ?Location;
    public function getUrl(): string;
    public function getDay(): int;
    public function getDayOfWeek(): string;
    public function getTime(): string;
    public function getEndTime(): string;
    public function getTypes(): array;
    public function getState(): string;
    public function isOnline(): bool;
    public function getContacts(): array;
    public function getMeta(): array;
    public function getOnlineLink(): string;
    public function getOnlineNotes(): string;
}
```

#### Member

```php
interface Member
{
    public function getId(): int;
    public function getName(): string;
    public function getEmail(): string;
    public function getPhone(): string;
    // Additional methods as implemented
}
```

#### ContactInterface

```php
interface ContactInterface
{
    public function getId(): int;
    public function getName(): string;
    public function getEmail(): string;
    public function getPhone(): string;
    // Additional contact methods
}
```

### Repository Interfaces

All repositories extend the base repository pattern:

```php
interface RepositoryInterface
{
    public function findById(int $id): ?EntityInterface;
    public function findAll(): array;
    public function save(EntityInterface $entity): bool;
    public function delete(int $id): bool;
}
```

### Container Access

```php
// Global function to access container
function unity(): DependencyContainer;

// Usage
$container = unity();
$service = $container->get(ServiceInterface::class);
```

## Error Handling

Unity includes comprehensive error handling:

- **Initialization Errors**: Logged to WordPress error log and displayed in admin notices
- **Service Registration Errors**: Throws `DependencyNotRegisteredException` if required services aren't registered
- **Autoloader Errors**: Gracefully handled with error logging
- **Fatal Errors**: Caught and logged with stack traces

Check WordPress debug logs for detailed error information:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Troubleshooting

### Plugin Won't Activate

1. Check PHP version (must be 8.0+)
2. Check WordPress version (must be 6.0+)
3. Review error logs for specific messages

### Services Not Registered Error

This means required services haven't been registered. Add the `unity_register_services` hook to register all required services (see Configuration section).

### Missing Dependencies

Ensure all required interfaces are implemented:
```php
add_action('unity_register_services', function($container) {
    // Register all required factories and repositories
});
```

## Contributing

### Coding Standards

- Follow WordPress PHP Coding Standards
- Use PSR-4 autoloading conventions
- All code must pass PHPStan level 5 analysis
- Maintain 100% interface coverage for public APIs

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Run code quality checks
6. Submit pull request with detailed description

## License

MIT License (Modified)

Copyright (c) 2025 The Bleeding Deacons

Permission is granted to use, modify, and distribute this software, with the following modification:

**The licensee may NOT sell this software, alone or as part of an aggregate software distribution.**

See the [LICENSE](LICENSE) file for full terms.

## Support

For issues, questions, or contributions:
- Email: thebleedingdeacons@gmail.com
- Report bugs through issue tracker
- Review documentation before requesting support

## Changelog

### Version 1.2.4
- Current stable release
- Enhanced dependency injection
- Improved error handling
- Added comprehensive hooks

### Version 1.0.1
- Initial composer package release
- Basic intergroup management functionality

## Credits

Developed by **The Bleeding Deacons**

Built with modern PHP practices and WordPress best practices in mind.
