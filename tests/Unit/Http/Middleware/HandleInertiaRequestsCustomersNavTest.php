<?php

declare(strict_types=1);

use App\Enums\AuthGuardEnum;
use App\Enums\PermissionsEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Navigation\Navigation;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

/**
 * @return array<int, array<string, mixed>>
 */
function inertiaNavigationTree(User $user): array
{
    Auth::login($user);

    $middleware = app(HandleInertiaRequests::class);
    $method = new ReflectionMethod(HandleInertiaRequests::class, 'buildNavigation');
    $method->setAccessible(true);
    /** @var Navigation $nav */
    $nav = $method->invoke($middleware);

    return $nav->tree();
}

/**
 * @param  array<int, array<string, mixed>>  $nodes
 * @return array<string, mixed>|null
 */
function findNavSectionByTitle(array $nodes, string $title): ?array
{
    foreach ($nodes as $node) {
        if (($node['title'] ?? '') === $title) {
            return $node;
        }
        $found = findNavSectionByTitle($node['children'] ?? [], $title);
        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

/**
 * @param  array<string, mixed>  $section
 * @return array<int, string>
 */
function customerNavChildTitles(array $section): array
{
    return collect($section['children'] ?? [])->pluck('title')->values()->all();
}

function ensureCustomerNavPermissionsExist(): void
{
    foreach ([
        PermissionsEnum::CustomersList,
        PermissionsEnum::CustomersUpload,
        PermissionsEnum::LEADS_BY_EMAIL,
    ] as $permission) {
        Permission::findOrCreate($permission, AuthGuardEnum::Web->value);
    }
}

it('does not add Customers nav section when user has none of customers-list, customers-upload, or leads-by-email', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);

    $tree = inertiaNavigationTree($user);

    expect(findNavSectionByTitle($tree, 'Customers'))->toBeNull();
});

it('adds Customers section with Search and Leads by Email when user has only customers-list', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $user->givePermissionTo(PermissionsEnum::CustomersList);

    $section = findNavSectionByTitle(inertiaNavigationTree($user), 'Customers');

    expect($section)->not->toBeNull()
        ->and(customerNavChildTitles($section))->toBe([
            'Search',
            'Leads by Email',
        ]);
});

it('adds Customers section with Uploads when user has only customers-upload', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $user->givePermissionTo(PermissionsEnum::CustomersUpload);

    $section = findNavSectionByTitle(inertiaNavigationTree($user), 'Customers');

    expect($section)->not->toBeNull()
        ->and(customerNavChildTitles($section))->toBe(['Uploads']);
});

it('adds Customers section with Leads by Email when user has only leads-by-email', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $user->givePermissionTo(PermissionsEnum::LEADS_BY_EMAIL);

    $section = findNavSectionByTitle(inertiaNavigationTree($user), 'Customers');

    expect($section)->not->toBeNull()
        ->and(customerNavChildTitles($section))->toBe(['Leads by Email']);
});

it('adds Customers section with all three links when user has customers-list, customers-upload, and leads-by-email', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $user->givePermissionTo([
        PermissionsEnum::CustomersList,
        PermissionsEnum::CustomersUpload,
        PermissionsEnum::LEADS_BY_EMAIL,
    ]);

    $section = findNavSectionByTitle(inertiaNavigationTree($user), 'Customers');

    expect($section)->not->toBeNull()
        ->and(customerNavChildTitles($section))->toBe([
            'Search',
            'Uploads',
            'Leads by Email',
        ]);
});

it('maps Customers children to the expected routes', function () {
    ensureCustomerNavPermissionsExist();
    $user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $user->givePermissionTo([
        PermissionsEnum::CustomersList,
        PermissionsEnum::CustomersUpload,
        PermissionsEnum::LEADS_BY_EMAIL,
    ]);

    $section = findNavSectionByTitle(inertiaNavigationTree($user), 'Customers');
    expect($section)->not->toBeNull();

    $byTitle = collect($section['children'])->keyBy('title');

    expect($byTitle['Search']['url'])->toBe(route('customers-list'))
        ->and($byTitle['Uploads']['url'])->toBe(route('customer.upload'))
        ->and($byTitle['Leads by Email']['url'])->toBe(route('leads-by-email'));
});
