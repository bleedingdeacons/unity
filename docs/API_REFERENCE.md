# Unity Plugin - API Reference

## Table of Contents

1. [Core Services](#core-services)
2. [Groups](#groups)
3. [Meetings](#meetings)
4. [Members](#members)
5. [Positions](#positions)
6. [Locations](#locations)
7. [Contacts](#contacts)
8. [Intergroup Meetings](#intergroup-meetings)
9. [Change Tracking](#change-tracking)
10. [Dependency Container](#dependency-container)

---

## Core Services

### DependencyContainer

The dependency injection container manages all services.

#### Methods

**`register(string $interface, callable $resolver): void`**
Register a service in the container.

```php
$container->register(ServiceInterface::class, function($c) {
    return new ServiceImplementation();
});
```

**`get(string $interface): mixed`**
Resolve and retrieve a service.

```php
$service = $container->get(ServiceInterface::class);
```

**`has(string $interface): bool`**
Check if a service is registered.

```php
if ($container->has(ServiceInterface::class)) {
    // Service is registered
}
```

### CacheInterface

WordPress caching integration.

#### Methods

**`get(string $key): mixed`**
Retrieve cached value.

**`set(string $key, mixed $value, int $expiration = 0): bool`**
Store value in cache.

**`delete(string $key): bool`**
Remove cached value.

**`flush(): bool`**
Clear all cached values.

---

## Groups

### GroupInterface

Represents a group entity.

#### Properties

- `int $id` - Group ID
- `string $title` - Group name/title
- `string $email` - Group contact email
- `array $meetings` - Array of MeetingInterface objects
- `string $link` - Group website or information link
- `string $groupNotes` - Internal notes about the group
- `string $website` - Group website URL
- `string $phone` - Group contact phone
- `string $venmo` - Venmo handle for contributions
- `string $paypal` - PayPal username
- `string $square` - Square Cash App cashtag
- `?int $districtId` - District identifier
- `?string $lastContact` - Last contact timestamp
- `array $contacts` - Array of ContactInterface objects

#### Methods

**`getId(): int`**
Get the group ID.

**`getTitle(): string`**
Get the group title.

**`getEmail(): string`**
Get the group email address.

**`getMeetings(): array`**
Get array of meeting objects associated with this group.

**`getLink(): string`**
Get the group's information link.

**`isValid(): bool`**
Check if group has required data (ID and title).

**`getGroupNotes(): string`**
Get internal notes about the group.

**`getWebsite(): string`**
Get the group's website URL.

**`getPhone(): string`**
Get the group's contact phone number.

**`getVenmo(): string`**
Get Venmo handle for contributions.

**`getPaypal(): string`**
Get PayPal username.

**`getSquare(): string`**
Get Square Cash App cashtag.

**`getDistrictId(): ?int`**
Get district ID if applicable.

**`getLastContact(): ?string`**
Get timestamp of last contact.

**`getContacts(): array`**
Get array of contact objects for this group.

**`hasContributionOptions(): bool`**
Check if group has any payment methods configured.

### GroupFactoryInterface

Factory for creating group objects.

#### Methods

**`create(array $data): GroupInterface`**
Create a new group object from data array.

```php
$group = $groupFactory->create([
    'id' => 123,
    'title' => 'Example Group',
    'email' => 'contact@example.com',
    'website' => 'https://example.com',
    // ... other fields
]);
```

### GroupRepositoryInterface

Repository for group data access.

#### Methods

**`findById(int $id): ?GroupInterface`**
Find a group by ID.

**`findAll(): array`**
Get all groups.

**`findByDistrict(int $districtId): array`**
Find groups in a specific district.

**`save(GroupInterface $group): bool`**
Save group data.

**`delete(int $id): bool`**
Delete a group.

### GroupChangeTracker

Tracks changes to group data.

#### Constructor

```php
public function __construct(GroupRepositoryInterface $repository)
```

#### Usage

```php
$tracker = $container->get(GroupChangeTracker::class);
// Implement change detection and logging
```

---

## Meetings

### MeetingInterface

Represents a meeting entity.

#### Properties

- `int $id` - Meeting ID
- `string $name` - Meeting name
- `string $slug` - URL-friendly identifier
- `?LocationInterface $location` - Meeting location
- `string $url` - Meeting information URL
- `int $day` - Day of week (0-6)
- `string $dayOfWeek` - Day name
- `string $time` - Start time
- `string $endTime` - End time
- `array $types` - Meeting types/categories
- `string $state` - Meeting state (active, inactive, etc.)
- `bool $online` - Whether meeting is online
- `array $contacts` - Contact information
- `array $meta` - Additional metadata
- `string $onlineLink` - Virtual meeting link
- `string $onlineNotes` - Notes about online access

#### Methods

**`getId(): int`**
Get meeting ID.

**`getName(): string`**
Get meeting name.

**`getSlug(): string`**
Get URL slug.

**`getLocation(): ?LocationInterface`**
Get location object (null for online-only meetings).

**`getUrl(): string`**
Get meeting information URL.

**`getDay(): int`**
Get day of week as integer (0=Sunday, 6=Saturday).

**`getDayOfWeek(): string`**
Get day of week as string.

**`getTime(): string`**
Get start time.

**`getEndTime(): string`**
Get end time.

**`getTypes(): array`**
Get meeting types/categories.

**`getState(): string`**
Get current meeting state.

**`isOnline(): bool`**
Check if meeting is online.

**`getContacts(): array`**
Get contact information.

**`getMeta(): array`**
Get metadata array.

**`getOnlineLink(): string`**
Get virtual meeting link.

**`getOnlineNotes(): string`**
Get online meeting notes.

### MeetingFactoryInterface

Factory for creating meeting objects.

#### Methods

**`create(array $data): MeetingInterface`**
Create meeting from data array.

```php
$meeting = $meetingFactory->create([
    'id' => 456,
    'name' => 'Monday Night Meeting',
    'day' => 1,
    'time' => '19:00',
    'endTime' => '20:00',
    'online' => true,
    'onlineLink' => 'https://zoom.us/j/123456789',
    // ... other fields
]);
```

### MeetingRepositoryInterface

Repository for meeting data access.

#### Methods

**`findById(int $id): ?MeetingInterface`**
Find meeting by ID.

**`findAll(): array`**
Get all meetings.

**`findByDay(int $day): array`**
Find meetings on specific day of week.

**`findByGroup(int $groupId): array`**
Find meetings for a specific group.

**`findOnlineMeetings(): array`**
Find all online meetings.

**`save(MeetingInterface $meeting): bool`**
Save meeting data.

**`delete(int $id): bool`**
Delete a meeting.

---

## Members

### MemberInterface

Represents a member entity.

#### Methods

**`getId(): int`**
Get member ID.

**`getName(): string`**
Get member name.

**`getEmail(): string`**
Get member email.

**`getPhone(): string`**
Get member phone number.

**`getGroups(): array`**
Get groups this member belongs to.

**`getPositions(): array`**
Get positions held by this member.

### MemberFactoryInterface

Factory for creating member objects.

#### Methods

**`create(array $data): MemberInterface`**
Create member from data.

### MemberRepositoryInterface

Repository for member data access.

#### Methods

**`findById(int $id): ?MemberInterface`**
Find member by ID.

**`findAll(): array`**
Get all members.

**`findByGroup(int $groupId): array`**
Find members of a group.

**`findByPosition(int $positionId): array`**
Find members with specific position.

**`save(MemberInterface $member): bool`**
Save member data.

**`delete(int $id): bool`**
Delete a member.

### MemberChangeTracker

Tracks changes to member data.

#### Constructor

```php
public function __construct(MemberRepositoryInterface $repository)
```

---

## Positions

### PositionInterface

Represents an organizational position.

#### Methods

**`getId(): int`**
Get position ID.

**`getTitle(): string`**
Get position title.

**`getDescription(): string`**
Get position description.

**`getResponsibilities(): array`**
Get list of responsibilities.

**`getRequirements(): array`**
Get position requirements.

### PositionFactoryInterface

Factory for creating position objects.

#### Methods

**`create(array $data): PositionInterface`**
Create position from data.

### PositionRepositoryInterface

Repository for position data access.

#### Methods

**`findById(int $id): ?PositionInterface`**
Find position by ID.

**`findAll(): array`**
Get all positions.

**`save(PositionInterface $position): bool`**
Save position data.

**`delete(int $id): bool`**
Delete a position.

### PositionViewInterface

Represents a view-specific position representation.

### PositionViewFactoryInterface

Factory for creating position view objects.

#### Methods

**`create(PositionInterface $position): PositionViewInterface`**
Create view representation from position.

### PositionChangeTracker

Tracks changes to position data.

#### Constructor

```php
public function __construct(PositionRepositoryInterface $repository)
```

---

## Locations

### LocationInterface

Represents a physical location.

#### Methods

**`getId(): int`**
Get location ID.

**`getName(): string`**
Get location name.

**`getAddress(): string`**
Get street address.

**`getCity(): string`**
Get city.

**`getState(): string`**
Get state/province.

**`getZipCode(): string`**
Get postal code.

**`getCountry(): string`**
Get country.

**`getLatitude(): ?float`**
Get latitude coordinate.

**`getLongitude(): ?float`**
Get longitude coordinate.

**`getAccessibility(): string`**
Get accessibility information.

**`getNotes(): string`**
Get location notes.

### LocationFactoryInterface

Factory for creating location objects.

#### Methods

**`create(array $data): LocationInterface`**
Create location from data.

### LocationRepositoryInterface

Repository for location data access.

#### Methods

**`findById(int $id): ?LocationInterface`**
Find location by ID.

**`findAll(): array`**
Get all locations.

**`findByCity(string $city): array`**
Find locations in a city.

**`save(LocationInterface $location): bool`**
Save location data.

**`delete(int $id): bool`**
Delete a location.

---

## Contacts

### ContactInterface

Represents contact information.

#### Methods

**`getId(): int`**
Get contact ID.

**`getName(): string`**
Get contact name.

**`getEmail(): string`**
Get email address.

**`getPhone(): string`**
Get phone number.

**`getRole(): string`**
Get contact's role.

**`isPrimary(): bool`**
Check if this is a primary contact.

### ContactFactoryInterface

Factory for creating contact objects.

#### Methods

**`create(array $data): ContactInterface`**
Create contact from data.

```php
$contact = $contactFactory->create([
    'id' => 789,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '555-1234',
    'role' => 'Secretary',
    'primary' => true
]);
```

---

## Intergroup Meetings

### IntergroupMeetingInterface

Represents meetings between multiple groups.

#### Methods

**`getId(): int`**
Get intergroup meeting ID.

**`getTitle(): string`**
Get meeting title.

**`getDate(): string`**
Get meeting date.

**`getParticipatingGroups(): array`**
Get array of participating group IDs.

**`getAgenda(): string`**
Get meeting agenda.

**`getNotes(): string`**
Get meeting notes.

### IntergroupMeetingFactoryInterface

Factory for creating intergroup meeting objects.

### IntergroupMeetingRepositoryInterface

Repository for intergroup meeting data access.

---

## Change Tracking

Change tracking services monitor and log changes to entities.

### GroupChangeTracker

Tracks group modifications.

```php
$tracker = $container->get(GroupChangeTracker::class);
// Monitors changes to group data
```

### MemberChangeTracker

Tracks member modifications.

```php
$tracker = $container->get(MemberChangeTracker::class);
// Monitors changes to member data
```

### PositionChangeTracker

Tracks position modifications.

```php
$tracker = $container->get(PositionChangeTracker::class);
// Monitors changes to position data
```

---

## Dependency Container

### Accessing the Container

**Global Function**
```php
$container = unity();
```

**From Plugin Class**
```php
$container = Unity\Plugin::getContainer();
```

### Registering Services

Services must be registered using the `unity_register_services` hook:

```php
add_action('unity_register_services', function($container) {
    $container->register(MyServiceInterface::class, function($c) {
        return new MyService(
            $c->get(DependencyInterface::class)
        );
    });
});
```

### Resolving Services

```php
// Get a service
$service = $container->get(ServiceInterface::class);

// Check if service exists
if ($container->has(ServiceInterface::class)) {
    $service = $container->get(ServiceInterface::class);
}
```

### Service Dependencies

Services can depend on other services:

```php
$container->register(ServiceAInterface::class, function($c) {
    return new ServiceA(
        $c->get(ServiceBInterface::class),
        $c->get(ServiceCInterface::class)
    );
});
```

---

## WordPress Integration

### Actions

**`unity_register_services`**
- **When:** After container creation, before service resolution
- **Parameters:** `DependencyContainer $container`
- **Use:** Register custom service implementations

**`unity_loaded`**
- **When:** After all services initialized
- **Parameters:** `DependencyContainer $container`
- **Use:** Execute code that depends on Unity services

### Example Integration

```php
// Register services
add_action('unity_register_services', function($container) {
    $container->register(MeetingFactoryInterface::class, function($c) {
        return new CustomMeetingFactory();
    });
});

// Use services after initialization
add_action('unity_loaded', function($container) {
    $meetings = $container
        ->get(MeetingRepositoryInterface::class)
        ->findAll();
    
    foreach ($meetings as $meeting) {
        // Do something with meetings
    }
});
```

---

## Error Handling

### DependencyNotRegisteredException

Thrown when attempting to resolve an unregistered service.

```php
try {
    $service = $container->get(UnregisteredInterface::class);
} catch (DependencyNotRegisteredException $e) {
    error_log('Service not registered: ' . $e->getMessage());
}
```

### Common Exceptions

- `RuntimeException` - Plugin initialization failures
- `DependencyNotRegisteredException` - Missing service registration
- `InvalidArgumentException` - Invalid data passed to methods

---

## Best Practices

### 1. Always Use Interfaces

```php
// Good
$repository = $container->get(MeetingRepositoryInterface::class);

// Bad
$repository = new MeetingRepository();
```

### 2. Register Services Early

```php
add_action('unity_register_services', function($container) {
    // Register all services here
}, 10);
```

### 3. Check Service Availability

```php
if ($container->has(ServiceInterface::class)) {
    $service = $container->get(ServiceInterface::class);
} else {
    // Fallback logic
}
```

### 4. Use Type Hints

```php
function processGroup(GroupInterface $group): void {
    // Type safety ensured
}
```

### 5. Leverage Caching

```php
$cache = $container->get(CacheInterface::class);
$groups = $cache->get('all_groups');

if ($groups === false) {
    $groups = $repository->findAll();
    $cache->set('all_groups', $groups, 3600);
}
```

---

## Version Compatibility

This API reference is for Unity version 1.2.4 and later.

For older versions, consult the version-specific documentation or check the changelog for API changes.
