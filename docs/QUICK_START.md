# Unity Plugin - Quick Start Guide

Get Unity up and running in your WordPress site in minutes.

## Installation (5 minutes)

### Step 1: Upload Plugin

1. Download the Unity plugin zip file
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the zip file and click **Install Now**
5. Click **Activate**

### Step 2: Install Advanced Custom Fields (Optional but Recommended)

1. Go to **Plugins → Add New**
2. Search for "Advanced Custom Fields"
3. Install and activate **Advanced Custom Fields** or **Advanced Custom Fields PRO**

### Step 3: Import Field Configurations

If using ACF:

1. Go to **ACF → Tools**
2. Click **Import Field Groups**
3. Navigate to `wp-content/plugins/unity/setup/Unity_ACF.json`
4. Click **Import**

## Basic Setup (10 minutes)

### Step 1: Create Your Implementation

Create a new file in your theme: `unity-setup.php`

```php
<?php
/**
 * Unity Plugin Setup
 */

// Register all required services
add_action('unity_register_services', 'my_register_unity_services');

function my_register_unity_services($container) {
    
    // Register Location services
    $container->register(
        Unity\Locations\Interfaces\LocationFactory::class,
        function() {
            return new My_Unity_Location_Factory();
        }
    );
    
    $container->register(
        Unity\Locations\Interfaces\LocationRepository::class,
        function($c) {
            return new My_Unity_Location_Repository(
                $c->get(Unity\Locations\Interfaces\LocationFactory::class)
            );
        }
    );

    // Register Meeting services
    $container->register(
        Unity\Meetings\Interfaces\MeetingFactory::class,
        function($c) {
            return new My_Unity_Meeting_Factory(
                $c->get(Unity\Contact\Interfaces\ContactFactory::class),
                $c->get(Unity\Locations\Interfaces\LocationRepository::class)
            );
        }
    );
    
    $container->register(
        Unity\Meetings\Interfaces\MeetingRepository::class,
        function($c) {
            return new My_Unity_Meeting_Repository(
                $c->get(Unity\Meetings\Interfaces\MeetingFactory::class),
                $c->get(Unity\Core\Interfaces\Cache::class)
            );
        }
    );

    // Register Group services
    $container->register(
        Unity\Groups\Interfaces\GroupFactory::class,
        function($c) {
            return new My_Unity_Group_Factory(
                $c->get(Unity\Contact\Interfaces\ContactFactory::class),
                $c->get(Unity\Meetings\Interfaces\MeetingRepository::class)
            );
        }
    );
    
    $container->register(
        Unity\Groups\Interfaces\GroupRepository::class,
        function($c) {
            return new My_Unity_Group_Repository(
                $c->get(Unity\Groups\Interfaces\GroupFactory::class)
            );
        }
    );

    // Register Member services
    $container->register(
        Unity\Members\Interfaces\MemberFactory::class,
        function() {
            return new My_Unity_Member_Factory();
        }
    );
    
    $container->register(
        Unity\Members\Interfaces\MemberRepository::class,
        function($c) {
            return new My_Unity_Member_Repository(
                $c->get(Unity\Members\Interfaces\MemberFactory::class)
            );
        }
    );

    // Register Position services
    $container->register(
        Unity\Positions\Interfaces\PositionFactory::class,
        function() {
            return new My_Unity_Position_Factory();
        }
    );
    
    $container->register(
        Unity\Positions\Interfaces\PositionRepository::class,
        function($c) {
            return new My_Unity_Position_Repository(
                $c->get(Unity\Positions\Interfaces\PositionFactory::class)
            );
        }
    );

    // Register Intergroup Meeting services
    $container->register(
        Unity\IntergroupMeetings\Interfaces\IntergroupMeetingFactory::class,
        function() {
            return new My_Unity_Intergroup_Meeting_Factory();
        }
    );
    
    $container->register(
        Unity\IntergroupMeetings\Interfaces\IntergroupMeetingRepository::class,
        function($c) {
            return new My_Unity_Intergroup_Meeting_Repository(
                $c->get(Unity\IntergroupMeetings\Interfaces\IntergroupMeetingFactory::class)
            );
        }
    );
}
```

### Step 2: Include in Theme

Add to your theme's `functions.php`:

```php
require_once get_template_directory() . '/unity-setup.php';
require_once get_template_directory() . '/unity-implementations.php';
```

### Step 3: Create Implementation Classes

Create `unity-implementations.php` with your custom implementations (see examples below).

## Minimal Implementation Example

Here's a simple example to get you started:

```php
<?php
/**
 * Unity Implementation Classes
 */

// Location Factory
class My_Unity_Location_Factory implements Unity\Locations\Interfaces\LocationFactory {
    public function create(array $data): Unity\Locations\Interfaces\Location {
        // Simple implementation - extend as needed
        return new Unity\Locations\Location(
            $data['id'] ?? 0,
            $data['name'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['zip'] ?? ''
        );
    }
}

// Location Repository
class My_Unity_Location_Repository implements Unity\Locations\Interfaces\LocationRepository {
    private $factory;
    
    public function __construct(Unity\Locations\Interfaces\LocationFactory $factory) {
        $this->factory = $factory;
    }
    
    public function findById(int $id): ?Unity\Locations\Interfaces\Location {
        $post = get_post($id);
        if (!$post || $post->post_type !== 'location') {
            return null;
        }
        
        return $this->factory->create([
            'id' => $post->ID,
            'name' => $post->post_title,
            'address' => get_post_meta($post->ID, 'address', true),
            'city' => get_post_meta($post->ID, 'city', true),
            'state' => get_post_meta($post->ID, 'state', true),
            'zip' => get_post_meta($post->ID, 'zip', true)
        ]);
    }
    
    public function findAll(): array {
        $posts = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1
        ]);
        
        $locations = [];
        foreach ($posts as $post) {
            $locations[] = $this->findById($post->ID);
        }
        
        return $locations;
    }
    
    public function save(Unity\Locations\Interfaces\Location $location): bool {
        // Implement save logic
        return true;
    }
    
    public function delete(int $id): bool {
        return (bool) wp_delete_post($id, true);
    }
}

// Repeat similar patterns for:
// - My_Unity_Meeting_Factory
// - My_Unity_Meeting_Repository
// - My_Unity_Group_Factory
// - My_Unity_Group_Repository
// - My_Unity_Member_Factory
// - My_Unity_Member_Repository
// - My_Unity_Position_Factory
// - My_Unity_Position_Repository
// - My_Unity_Intergroup_Meeting_Factory
// - My_Unity_Intergroup_Meeting_Repository
```

## Using Unity in Your Templates

### Display All Groups

```php
<?php
$container = unity();
$groupRepo = $container->get(Unity\Groups\Interfaces\GroupRepository::class);
$groups = $groupRepo->findAll();

foreach ($groups as $group) {
    echo '<div class="group">';
    echo '<h2>' . esc_html($group->getTitle()) . '</h2>';
    echo '<p>Email: ' . esc_html($group->getEmail()) . '</p>';
    echo '<p>Website: <a href="' . esc_url($group->getWebsite()) . '">' . esc_html($group->getWebsite()) . '</a></p>';
    
    if ($group->hasContributionOptions()) {
        echo '<div class="contributions">';
        if ($group->getVenmo()) {
            echo '<p>Venmo: ' . esc_html($group->getVenmo()) . '</p>';
        }
        if ($group->getPaypal()) {
            echo '<p>PayPal: ' . esc_html($group->getPaypal()) . '</p>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}
?>
```

### Display Meeting Schedule

```php
<?php
$container = unity();
$meetingRepo = $container->get(Unity\Meetings\Interfaces\MeetingRepository::class);

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($days as $dayNum => $dayName) {
    $meetings = $meetingRepo->findByDay($dayNum);
    
    if (empty($meetings)) {
        continue;
    }
    
    echo '<h2>' . esc_html($dayName) . '</h2>';
    echo '<ul class="meetings">';
    
    foreach ($meetings as $meeting) {
        echo '<li>';
        echo '<strong>' . esc_html($meeting->getName()) . '</strong><br>';
        echo 'Time: ' . esc_html($meeting->getTime());
        
        if ($meeting->isOnline()) {
            echo ' (Online)<br>';
            echo '<a href="' . esc_url($meeting->getOnlineLink()) . '">Join Meeting</a>';
        } else {
            $location = $meeting->getLocation();
            if ($location) {
                echo '<br>Location: ' . esc_html($location->getName());
                echo '<br>' . esc_html($location->getAddress());
            }
        }
        
        echo '</li>';
    }
    
    echo '</ul>';
}
?>
```

### Create a Shortcode

```php
// Add to functions.php
function unity_meetings_shortcode($atts) {
    $atts = shortcode_atts([
        'day' => null,
        'limit' => -1
    ], $atts);
    
    $container = unity();
    $meetingRepo = $container->get(Unity\Meetings\Interfaces\MeetingRepository::class);
    
    if ($atts['day'] !== null) {
        $meetings = $meetingRepo->findByDay((int)$atts['day']);
    } else {
        $meetings = $meetingRepo->findAll();
    }
    
    if ($atts['limit'] > 0) {
        $meetings = array_slice($meetings, 0, $atts['limit']);
    }
    
    ob_start();
    ?>
    <div class="unity-meetings">
        <?php foreach ($meetings as $meeting): ?>
            <div class="meeting">
                <h3><?php echo esc_html($meeting->getName()); ?></h3>
                <p><strong><?php echo esc_html($meeting->getDayOfWeek()); ?></strong> at <?php echo esc_html($meeting->getTime()); ?></p>
                <?php if ($meeting->isOnline()): ?>
                    <a href="<?php echo esc_url($meeting->getOnlineLink()); ?>" class="btn">Join Online</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('unity_meetings', 'unity_meetings_shortcode');
```

Use in posts/pages:
```
[unity_meetings day="1" limit="5"]
```

## Common Tasks

### Register Custom Post Types

```php
function register_unity_post_types() {
    register_post_type('meeting', [
        'labels' => [
            'name' => 'Meetings',
            'singular_name' => 'Meeting'
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'custom-fields']
    ]);
    
    register_post_type('location', [
        'labels' => [
            'name' => 'Locations',
            'singular_name' => 'Location'
        ],
        'public' => true,
        'supports' => ['title', 'custom-fields']
    ]);
}
add_action('init', 'register_unity_post_types');
```

### Enable Caching

Unity includes WordPress caching by default:

```php
add_action('unity_loaded', function($container) {
    $cache = $container->get(Unity\Core\Interfaces\Cache::class);
    
    // Cache is automatically used by repositories
    // You can also use it directly:
    $cache->set('my_key', $data, 3600);
    $data = $cache->get('my_key');
});
```

### Add Custom Hooks

```php
// Listen for group changes
add_action('save_post_group', function($post_id) {
    $container = unity();
    $groupRepo = $container->get(Unity\Groups\Interfaces\GroupRepository::class);
    
    // Clear cache
    $cache = $container->get(Unity\Core\Interfaces\Cache::class);
    $cache->delete("group_{$post_id}");
}, 10, 1);
```

## Troubleshooting

### Error: Services not registered

Make sure you've added the `unity_register_services` hook with all required services.

### Error: Container not initialized

Access Unity only after WordPress is loaded:
```php
add_action('wp', function() {
    $container = unity();
    // Use container here
});
```

### Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `wp-content/debug.log` for errors.

## Next Steps

1. **Read the Developer Guide** - Learn advanced patterns and best practices
2. **Review API Reference** - Understand all available interfaces and methods
3. **Customize Your Implementation** - Extend factories and repositories for your needs
4. **Add Custom Features** - Use Unity's hooks to extend functionality

## Getting Help

- **Documentation**: See README.md, DEVELOPER_GUIDE.md, and API_REFERENCE.md
- **Email**: thebleedingdeacons@gmail.com
- **Issues**: Check the issue tracker on your repository

## Resources

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [Advanced Custom Fields](https://www.advancedcustomfields.com/)
- [PHP 8.0 Documentation](https://www.php.net/manual/en/)

---

**You're ready to go!** Start building your intergroup management system with Unity.
