<?php

declare(strict_types=1);

use App\Enums\CapacityUnit;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\ResourceSeeder;
use Database\Seeders\ResourceTypeSeeder;

use function Pest\Laravel\seed;

test('resource seeder sets parallel slot capacities for rooms and equipment', function (): void {
    User::factory()->count(6)->create();

    seed(ResourceTypeSeeder::class);
    seed(ResourceSeeder::class);

    $conferenceRoom = Resource::query()->where('name', 'Conference Room A')->first();
    $workshopBay = Resource::query()->where('name', 'Workshop Bay')->first();
    $meetingRoom = Resource::query()->where('name', 'Meeting Room B')->first();
    $printers = Resource::query()->where('name', '3D Printers')->first();

    expect($conferenceRoom)->not->toBeNull();
    expect($workshopBay)->not->toBeNull();
    expect($meetingRoom)->not->toBeNull();
    expect($printers)->not->toBeNull();

    expect((float) $conferenceRoom->capacity_value)->toBe(1.0);
    expect((float) $workshopBay->capacity_value)->toBe(1.0);
    expect((float) $meetingRoom->capacity_value)->toBe(1.0);
    expect((float) $printers->capacity_value)->toBe(3.0);

    expect($conferenceRoom->capacity_unit)->toBe(CapacityUnit::Slots);
    expect($printers->capacity_unit)->toBe(CapacityUnit::Slots);
});
