# SCraper Project Guidelines

This document provides guidelines and best practices for developers working on the Scraper project. It's designed to help experienced Laravel developers quickly understand the project's architecture, patterns, and conventions.

## Table of Contents

- [Tech Stack Overview](#tech-stack-overview)
- [Architecture](#architecture)
- [Laravel Patterns](#laravel-patterns)
- [Frontend Development](#frontend-development)
- [Testing](#testing)
- [Performance Best Practices](#performance-best-practices)
- [Development Setup](#development-setup)
- [CI/CD](#cicd)

## Tech Stack Overview

The TruckScanner project uses the following technologies:

- **PHP 8.3** - Taking advantage of modern PHP features like typed properties, constructor property promotion, and enums
- **Laravel 11** - The latest version of the Laravel framework
- **Livewire 3** - For reactive frontend components without writing JavaScript
- **Filament 3** - Admin panel framework built on top of TALL stack
- **Tailwind CSS** - Utility-first CSS framework
- **Laravel Pint** - Code style fixer with default Laravel configuration (PSR-12 + Laravel custom rules)
- **Pest** - Testing framework built on top of PHPUnit
- **MySQL** - Primary database

## Architecture

### Directory Structure

The project follows an extended Laravel directory structure with additional directories for specific concerns:

```
app/
├── Actions/         # Single-purpose business logic classes
├── Console/         # Console commands
├── DTO/             # Data Transfer Objects
├── Enums/           # PHP 8.1+ enums
├── Events/          # Event classes
├── Exceptions/      # Custom exceptions
├── Filament/        # Filament admin panel customizations
├── Http/            # Controllers, middleware, etc.
├── Interfaces/      # Additional interfaces
├── Listeners/       # Event listeners
├── Livewire/        # Livewire components
├── Models/          # Eloquent models
├── Notifications/   # Notification classes
├── Observers/       # Model observers
├── Policies/        # Authorization policies
├── Providers/       # Service providers
├── Query/           # Custom query builders
├── Settings/        # Application settings (using spatie/laravel-settings)
├── Support/         # Helper classes
└── View/            # View-related classes
```

### Key Architectural Decisions

1. **Domain-Driven Design Influence**: The codebase is organized around business domains rather than technical concerns.
2. **Single Responsibility Principle**: Classes, especially Actions, have a single, well-defined responsibility.
3. **Type Safety**: Extensive use of type hints, return types, and enums for improved code safety.
4. **Immutable Data**: DTOs are designed to be immutable, promoting safer data handling.
5. **Multi-tenancy**: The application supports multi-tenancy through Filament's tenant features.

## Laravel Patterns

### Models

Models in this project follow these conventions:

1. **No Fillable Properties**: The project avoids using `$fillable` or `$guarded` properties, instead relying on explicit data handling through DTOs and Actions.

2. **Method-Based Casts**: Models use the `casts()` method instead of the `$casts` property:

```php
protected function casts(): array
{
    return [
        'valid_to' => 'datetime',
        'validated_at' => 'datetime',
    ];
}
```

3. **Custom Query Builders**: Models often use custom query builders for complex queries:

```php
public function newEloquentBuilder($query): CustomQuery
{
    return new CustomQuery($query);
}
```

4. **Relationship Type Hints**: Relationships include return type hints:

```php
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
```

### Data Transfer Objects (DTOs)

DTOs use Spatie's Laravel Data package with these patterns:

1. **Constructor Property Promotion**: For concise property definitions:

```php
public function __construct(
    public ?StandardGood $type = null,
    public ?string $containerType = null,
    public int $quantity = 1,
    public bool $stackable = false,
)
```

2. **Snake Case Mapping**: Using attributes for consistent data transformation:

```php
#[MapName(SnakeCaseMapper::class)]
class SomeDataObject extends Data
```

3. **Type Safety**: Strong typing for all properties, including nullable types and enums.

### Actions

The project uses Laravel Actions (lorisleiva/laravel-actions) extensively:

1. **Single Responsibility**: Each action performs one specific task.

2. **AsAction Trait**: Actions use the `AsAction` trait:

```php
class CalculateRoute
{
    use AsAction;
    
    public function handle(array $waypoints, VehicleType $vehicleType): ?Result
    {
        // Implementation
    }
}
```

3. **Dependency Injection**: Actions use constructor dependency injection for services:

```php
public function __construct(private readonly SomeService $service) {}
```

4. **Typed Parameters and Return Values**: For improved code safety.

5. **Domain Organization**: Actions are organized in subdirectories by domain.

## Frontend Development

### Livewire Components

Livewire components follow these patterns:

1. **Form Handling**: Complex forms are broken down into smaller, reusable components.

2. **URL State Persistence**: Using `#[Url]` attribute for state that should be persisted in the URL:

```php
#[Url]
public int $step = 0;

#[Url(as: 'goods')]
public ?array $goodFormData = [];
```

3. **Computed Properties**: Using `#[Computed]` attribute for derived data:

```php
#[Computed]
public function totalWeight(): int
{
    return $this->goodData->reduce(fn (int $total, Item $item) => $total + $item->weight, 0);
}
```

4. **Event Dispatching**: For communication between components:

```php
$this->dispatch('update-map', route: $route);
```

5. **Form State Management**: Clear separation of form state and business logic.

### Filament

Filament resources and pages follow these conventions:

1. **Resource Organization**: Resources are organized by entity with clear separation of concerns.

2. **Custom Components**: Custom components for specialized UI elements:

```php
PtvMapEntry::make('ptv-map')
    ->pickupAddress(fn (Request $record) => $record->pickupAddress?->getAddressDTO())
    ->deliveryAddress(fn (Request $record) => $record->deliveryAddress?->getAddressDTO())
```

3. **Responsive Layouts**: Using responsive column definitions:

```php
->columns([
    'default' => 1,
    'md' => 2,
    'lg' => 3,
])
```

4. **Translation Support**: Using the `__()` function for all user-facing strings.

5. **Tenant Awareness**: Resources are scoped to tenants when appropriate:

```php
protected static bool $isScopedToTenant = true;
```

### Tailwind CSS

The project uses Tailwind CSS with these conventions:

1. **Utility-First Approach**: Favoring utility classes over custom CSS.

2. **Component Classes**: Using consistent class combinations for similar UI elements.

3. **Responsive Design**: Using Tailwind's responsive prefixes (`md:`, `lg:`, etc.) for responsive layouts.

4. **Custom Colors**: Using the project's color palette defined in `tailwind.config.js`.

## Testing

The project uses Pest for testing with these patterns:

1. **Feature Tests**: Testing complete workflows rather than isolated units:

```php
test('after creating a vehicle, data is filled from ACI', function () {
    // Test implementation
});
```

2. **Livewire Testing**: Using Pest's Livewire plugin:

```php
livewire(ListVehicles::class)
    ->callAction('create', [
        'name' => fake()->word(),
        'plate' => $plateDetailDTO->plateNumber,
    ]);
```

3. **Factories**: Using Laravel's factory system for test data generation.

4. **Mocking External Services**: Using fake implementations for external services:

```php
SearchPlate::fake($plateDetailDTO);
```

5. **Expressive Assertions**: Using Pest's expressive assertion syntax:

```php
expect($vehicle->plate)->toBe($plateDetailDTO->plateNumber)
    ->and($vehicle->status)->toBe(VehicleStatus::FILLED);
```

## Performance Best Practices

1. **Eager Loading**: Always eager load relationships to avoid N+1 query problems:

```php
$query->with('pickupAddress', 'deliveryAddress')
```

2. **Query Optimization**: Using custom query builders for complex queries.

3. **Caching**: Implementing caching for expensive operations:

```php
if ($route = Cache::get($this->getRouteCalculationKey())) {
    return $route;
}

// Expensive operation...

Cache::put($this->getRouteCalculationKey(), $route, now()->addHours(12));
```

4. **Lazy Collections**: Using lazy collections for memory-efficient processing of large datasets.

5. **Database Transactions**: Using transactions for operations that modify multiple records:

```php
DB::transaction(function () {
    // Multiple database operations
});
```

## Development Setup

To set up the project for local development:

1. Clone the repository
2. Install dependencies:
   ```
   composer install
   npm install
   ```
3. Configure environment variables in `.env`
4. Generate application key:
   ```
   php artisan key:generate
   ```
5. Run migrations:
   ```
   php artisan migrate
   ```
6. Build frontend assets:
   ```
   npm run build
   ```

**Note**: Use MySQL as the database driver.

## CI/CD

The project uses GitHub Actions for CI/CD with these workflows:

1. **Code Style**: Automatically checks and fixes code style using Laravel Pint:
   ```yaml
   - name: "laravel-pint"
     uses: aglipanci/laravel-pint-action@2.5
     with:
       preset: laravel
   ```

2. **Tests**: Runs the test suite on pull requests and pushes to main.

3. **Dependabot**: Automatically updates dependencies and merges non-breaking changes.

---

This document is intended as a high-level guide. For more detailed information on specific components or patterns, please refer to the codebase or ask the team.
