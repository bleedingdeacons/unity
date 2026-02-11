# Unity Plugin - Developer Guide

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Environment](#development-environment)
3. [Architecture Overview](#architecture-overview)
4. [Creating Custom Implementations](#creating-custom-implementations)
5. [Testing](#testing)
6. [Code Quality](#code-quality)
7. [Build Process](#build-process)
8. [Extending Unity](#extending-unity)
9. [Troubleshooting](#troubleshooting)
10. [Contributing](#contributing)

---

## Getting Started

### Prerequisites

- PHP 8.0 or higher
- Composer
- WordPress 6.0+
- Git
- A local WordPress development environment

### Initial Setup

1. **Clone the Repository**
```bash
git clone [repository-url] unity
cd unity
```

2. **Install Dependencies**
```bash
composer install
```

3. **Link to WordPress**
```bash
# Create symlink in WordPress plugins directory
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/unity
```

4. **Activate Plugin**
Navigate to WordPress admin → Plugins → Activate Unity

---

## Development Environment

### Recommended Tools

- **IDE:** PhpStorm, VS Code with PHP extensions
- **Local Server:** Local WP, MAMP, Docker, or similar
- **Debugging:** Xdebug
- **Version Control:** Git

### IDE Configuration

#### PhpStorm

1. Enable PHP 8.0+ support
2. Configure WordPress coding standards
3. Set up Xdebug for debugging
4. Configure Composer autoloading

#### VS Code

Install these extensions:
- PHP Intelephense
- PHP Debug
- WordPress Snippets
- PHP CS Fixer

### WordPress Development Setup

**wp-config.php** debugging configuration:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

---

## Architecture Overview

### Design Philosophy

Unity follows these principles:

1. **Dependency Injection** - All dependencies injected via constructor
2. **Interface Segregation** - Small, focused interfaces
3. **Single Responsibility** - Each class has one job
4. **Repository Pattern** - Abstracted data access
5. **Factory Pattern** - Controlled object creation

### Core Components

```
Unity Architecture
│
├── Plugin (Entry Point)
│   └── Initializes Container & Services
│
├── Core
│   ├── DependencyContainer (IoC Container)
│   ├── UnityServiceProvider (Service Registration)
│   └── WordPressCache (Caching)
│
├── Domain Models
│   ├── Groups
│   ├── Meetings
│   ├── Members
│   ├── Positions
│   ├── Locations
│   └── Contacts
│
└── Infrastructure
    ├── Repositories (Data Access)
    ├── Factories (Object Creation)
    └── Change Trackers (Monitoring)
```

### Service Container Flow

```
WordPress loads plugin
    ↓
Unity.php initializes
    ↓
Plugin::initContainer() creates DependencyContainer
    ↓
UnityServiceProvider registers default services
    ↓
WordPress fires 'unity/register_services' hook
    ↓
Custom implementations register their services
    ↓
Plugin::initServices() resolves core services
    ↓
WordPress fires 'unity/loaded' hook
    ↓
Plugin ready for use
```

---

## Creating Custom Implementations

### Step 1: Implement Required Interfaces

Unity requires implementations of these interfaces:

```php
// Example: Custom Meeting Factory
namespace YourPlugin\Unity;

use Unity\Meetings\Interfaces\MeetingFactory;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Meeting;
use Unity\Contact\Interfaces\ContactFactory;
use Unity\Locations\Interfaces\LocationRepository;

class CustomMeetingFactory implements MeetingFactory
{
    private ContactFactory $contactFactory;
    private LocationRepository $locationRepository;

    public function __construct(
        ContactFactory $contactFactory,
        LocationRepository $locationRepository
    ) {
        $this->contactFactory = $contactFactory;
        $this->locationRepository = $locationRepository;
    }

    public function create(array $data): Meeting
    {
        // Custom creation logic
        $location = null;
        if (!empty($data['location_id'])) {
            $location = $this->locationRepository->findById(
                $data['location_id']
            );
        }

        $contacts = [];
        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contactData) {
                $contacts[] = $this->contactFactory->create($contactData);
            }
        }

        return new Meeting(
            $data['id'] ?? 0,
            $data['name'] ?? '',
            $data['slug'] ?? '',
            $location,
            $data['url'] ?? '',
            $data['day'] ?? 0,
            $data['day_of_week'] ?? '',
            $data['time'] ?? '',
            $data['end_time'] ?? '',
            $data['types'] ?? [],
            $data['state'] ?? '',
            $data['online'] ?? false,
            $contacts,
            $data['meta'] ?? [],
            $data['online_link'] ?? '',
            $data['online_notes'] ?? ''
        );
    }
}
```

### Step 2: Implement Repository

```php
namespace YourPlugin\Unity;

use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Interfaces\MeetingFactory;
use Unity\Core\Interfaces\Cache;

class CustomMeetingRepository implements MeetingRepository
{
    private MeetingFactory $factory;
    private Cache $cache;
    private string $postType = 'meeting';

    public function __construct(
        MeetingFactory $factory,
        Cache $cache
    ) {
        $this->factory = $factory;
        $this->cache = $cache;
    }

    public function findById(int $id): ?Meeting
    {
        $cacheKey = "meeting_{$id}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $post = get_post($id);
        if (!$post || $post->post_type !== $this->postType) {
            return null;
        }

        $meeting = $this->createFromPost($post);
        $this->cache->set($cacheKey, $meeting, 3600);
        
        return $meeting;
    }

    public function findAll(): array
    {
        $posts = get_posts([
            'post_type' => $this->postType,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $meetings = [];
        foreach ($posts as $post) {
            $meetings[] = $this->createFromPost($post);
        }

        return $meetings;
    }

    public function findByDay(int $day): array
    {
        $posts = get_posts([
            'post_type' => $this->postType,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'meeting_day',
                    'value' => $day,
                    'compare' => '='
                ]
            ]
        ]);

        $meetings = [];
        foreach ($posts as $post) {
            $meetings[] = $this->createFromPost($post);
        }

        return $meetings;
    }

    public function save(Meeting $meeting): bool
    {
        $postData = [
            'ID' => $meeting->getId(),
            'post_title' => $meeting->getName(),
            'post_name' => $meeting->getSlug(),
            'post_type' => $this->postType,
            'post_status' => 'publish'
        ];

        $id = wp_insert_post($postData);
        
        if (is_wp_error($id)) {
            return false;
        }

        // Save meta data
        update_post_meta($id, 'meeting_day', $meeting->getDay());
        update_post_meta($id, 'meeting_time', $meeting->getTime());
        update_post_meta($id, 'meeting_end_time', $meeting->getEndTime());
        // ... save other fields

        // Clear cache
        $this->cache->delete("meeting_{$id}");

        return true;
    }

    public function delete(int $id): bool
    {
        $result = wp_delete_post($id, true);
        
        if ($result) {
            $this->cache->delete("meeting_{$id}");
            return true;
        }
        
        return false;
    }

    private function createFromPost(\WP_Post $post): Meeting
    {
        $data = [
            'id' => $post->ID,
            'name' => $post->post_title,
            'slug' => $post->post_name,
            'day' => (int) get_post_meta($post->ID, 'meeting_day', true),
            'time' => get_post_meta($post->ID, 'meeting_time', true),
            'end_time' => get_post_meta($post->ID, 'meeting_end_time', true),
            // ... get other fields
        ];

        return $this->factory->create($data);
    }
}
```

### Step 3: Register Your Implementations

```php
// In your theme's functions.php or custom plugin

add_action('unity/register_services', function($container) {
    // Register Meeting Factory
    $container->register(
        \Unity\Meetings\Interfaces\MeetingFactory::class,
        function($c) {
            return new \YourPlugin\Unity\CustomMeetingFactory(
                $c->get(\Unity\Contact\Interfaces\ContactFactory::class),
                $c->get(\Unity\Locations\Interfaces\LocationRepository::class)
            );
        }
    );

    // Register Meeting Repository
    $container->register(
        \Unity\Meetings\Interfaces\MeetingRepository::class,
        function($c) {
            return new \YourPlugin\Unity\CustomMeetingRepository(
                $c->get(\Unity\Meetings\Interfaces\MeetingFactory::class),
                $c->get(\Unity\Core\Interfaces\Cache::class)
            );
        }
    );

    // Register all other required services...
}, 10);
```

### Complete Example: All Required Services

```php
add_action('unity/register_services', function($container) {
    
    // Locations
    $container->register(LocationFactory::class, function() {
        return new CustomLocationFactory();
    });
    
    $container->register(LocationRepository::class, function($c) {
        return new CustomLocationRepository(
            $c->get(LocationFactory::class)
        );
    });

    // Meetings
    $container->register(MeetingFactory::class, function($c) {
        return new CustomMeetingFactory(
            $c->get(ContactFactoryInterface::class),
            $c->get(LocationRepository::class)
        );
    });
    
    $container->register(MeetingRepository::class, function($c) {
        return new CustomMeetingRepository(
            $c->get(MeetingFactory::class),
            $c->get(Cache::class)
        );
    });

    // Groups
    $container->register(GroupFactory::class, function($c) {
        return new CustomGroupFactory(
            $c->get(ContactFactoryInterface::class),
            $c->get(MeetingRepository::class)
        );
    });
    
    $container->register(GroupRepository::class, function($c) {
        return new CustomGroupRepository(
            $c->get(GroupFactory::class)
        );
    });

    // Members
    $container->register(MemberFactory::class, function() {
        return new CustomMemberFactory();
    });
    
    $container->register(MemberRepository::class, function($c) {
        return new CustomMemberRepository(
            $c->get(MemberFactory::class)
        );
    });

    // Positions
    $container->register(PositionFactory::class, function() {
        return new CustomPositionFactory();
    });
    
    $container->register(PositionRepository::class, function($c) {
        return new CustomPositionRepository(
            $c->get(PositionFactory::class)
        );
    });

    // Intergroup Meetings
    $container->register(IntergroupMeetingFactory::class, function() {
        return new CustomIntergroupMeetingFactory();
    });
    
    $container->register(IntergroupMeetingRepository::class, function($c) {
        return new CustomIntergroupMeetingRepository(
            $c->get(IntergroupMeetingFactory::class)
        );
    });
    
}, 10);
```

---

## Testing

### Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# With coverage
composer test:coverage
```

### Writing Unit Tests

```php
namespace Unity\Tests\Unit\Groups;

use Unity\Tests\TestCase;
use Unity\Groups\Group;

class GroupTest extends TestCase
{
    public function testGroupCreation(): void
    {
        $group = new Group(
            id: 123,
            title: 'Test Group',
            email: 'test@example.com'
        );

        $this->assertEquals(123, $group->getId());
        $this->assertEquals('Test Group', $group->getTitle());
        $this->assertEquals('test@example.com', $group->getEmail());
    }

    public function testGroupValidation(): void
    {
        $validGroup = new Group(1, 'Valid');
        $this->assertTrue($validGroup->isValid());

        $invalidGroup = new Group(0, '');
        $this->assertFalse($invalidGroup->isValid());
    }

    public function testContributionOptions(): void
    {
        $groupWithPayment = new Group(
            id: 1,
            title: 'Test',
            venmo: '@testgroup'
        );
        $this->assertTrue($groupWithPayment->hasContributionOptions());

        $groupWithoutPayment = new Group(1, 'Test');
        $this->assertFalse($groupWithoutPayment->hasContributionOptions());
    }
}
```

### Testing with WordPress Functions

```php
namespace Unity\Tests\Unit\Meetings;

use Unity\Tests\TestCase;
use Unity\Meetings\MeetingRepository;
use WP_Mock;

class MeetingRepositoryTest extends TestCase
{
    public function testFindById(): void
    {
        $mockFactory = $this->createMock(MeetingFactory::class);
        $mockCache = $this->createMock(Cache::class);
        
        $repository = new MeetingRepository($mockFactory, $mockCache);

        WP_Mock::userFunction('get_post')
            ->once()
            ->with(123)
            ->andReturn($this->createMockPost());

        $meeting = $repository->findById(123);
        
        $this->assertNotNull($meeting);
        $this->assertEquals(123, $meeting->getId());
    }

    private function createMockPost(): \stdClass
    {
        $post = new \stdClass();
        $post->ID = 123;
        $post->post_title = 'Test Meeting';
        $post->post_type = 'meeting';
        return $post;
    }
}
```

---

## Code Quality

### PHP CodeSniffer

Check coding standards:
```bash
composer cs
```

Fix automatically:
```bash
composer cs:fix
```

### PHPStan Static Analysis

Run static analysis:
```bash
composer stan
```

### Complete Quality Check

Run all checks:
```bash
composer check
```

This runs:
1. Code style check
2. Static analysis
3. All tests

---

## Build Process

### Build Commands

```bash
# Production build
composer build:production

# Development build
composer build:dev

# Clean build artifacts
composer build:clean
```

### Build Script

The `build.php` script handles:
- Creating production-ready zip
- Removing development files
- Optimizing autoloader
- Generating documentation

### Manual Build

```bash
php build.php build:production
```

Output: `unity-production.zip`

---

## Extending Unity

### Adding Custom Services

1. **Create Interface**
```php
namespace YourPlugin\Unity\Interfaces;

interface CustomServiceInterface
{
    public function doSomething(): void;
}
```

2. **Implement Service**
```php
namespace YourPlugin\Unity;

use YourPlugin\Unity\Interfaces\CustomServiceInterface;

class CustomService implements CustomServiceInterface
{
    public function doSomething(): void
    {
        // Implementation
    }
}
```

3. **Register Service**
```php
add_action('unity/register_services', function($container) {
    $container->register(
        CustomServiceInterface::class,
        function() {
            return new CustomService();
        }
    );
});
```

4. **Use Service**
```php
add_action('unity/loaded', function($container) {
    $service = $container->get(CustomServiceInterface::class);
    $service->doSomething();
});
```

### Creating Change Listeners

Monitor entity changes:

```php
add_action('unity/loaded', function($container) {
    $tracker = $container->get(GroupChangeTracker::class);
    
    // Add custom change handling
    add_action('unity_unity/group_changing', function($groupId, $changes) {
        error_log("Group {$groupId} changed: " . print_r($changes, true));
    }, 10, 2);
});
```

### Custom Repositories

Extend base functionality:

```php
class EnhancedMeetingRepository extends CustomMeetingRepository
{
    public function findUpcoming(int $limit = 10): array
    {
        // Custom query logic
        $now = current_time('timestamp');
        
        return array_filter(
            $this->findAll(),
            function($meeting) use ($now) {
                // Filter logic
                return strtotime($meeting->getTime()) > $now;
            }
        );
    }
}
```

---

## Troubleshooting

### Common Issues

#### Services Not Registered

**Error:**
```
Unity Plugin Error: Services not registered
```

**Solution:**
Ensure you've registered all required services via `unity/register_services` hook.

#### Class Not Found

**Error:**
```
Class 'Unity\SomeClass' not found
```

**Solution:**
1. Check file exists in correct location
2. Verify PSR-4 autoloading
3. Clear opcache if applicable

#### Container Not Initialized

**Error:**
```
RuntimeException: Plugin not initialized
```

**Solution:**
Access container only after `plugins_loaded` hook:

```php
add_action('plugins_loaded', function() {
    $container = unity();
}, 20); // Priority > 10
```

### Debugging

Enable WordPress debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs:
```bash
tail -f wp-content/debug.log
```

Xdebug breakpoints in key locations:
- `Unity.php` line 64 (plugin initialization)
- `Plugin::initContainer()`
- `UnityServiceProvider::register()`

---

## Contributing

### Code Style

Follow WordPress PHP Coding Standards:
- 4 spaces for indentation
- Single quotes for strings
- Comprehensive PHPDoc blocks
- Type declarations on all methods

### Pull Request Process

1. Fork repository
2. Create feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Run code quality checks
6. Update documentation
7. Submit PR with detailed description

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes and commit
git add .
git commit -m "Add new feature"

# Run tests
composer check

# Push changes
git push origin feature/my-feature

# Create pull request on GitHub
```

### Commit Messages

Follow conventional commits:
```
feat: add new meeting type filter
fix: resolve caching issue in groups
docs: update API reference
test: add unit tests for positions
refactor: improve factory pattern implementation
```

### Documentation Updates

When adding features:
1. Update README.md
2. Update API_REFERENCE.md
3. Add code examples
4. Update CHANGELOG.md

---

## Additional Resources

### WordPress Development
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress APIs](https://codex.wordpress.org/)

### PHP Development
- [PHP 8.0 Documentation](https://www.php.net/manual/en/)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Composer Documentation](https://getcomposer.org/doc/)

### Testing
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WP_Mock](https://github.com/10up/wp_mock)

### Tools
- [PHPStan](https://phpstan.org/)
- [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)

---

## Version History

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## License

MIT License (Modified - No Resale)

See [LICENSE](LICENSE) for full terms.
