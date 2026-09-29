<?php

namespace Tests\Feature\Restaurant;

use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsImagesTest extends TestCase
{
    use RefreshDatabase;

    private function restaurantOwner(): array
    {
        Role::findOrCreate('RESTAURANT_OWNER', 'web');

        $owner = User::factory()->create([
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);
        $owner->assignRole('RESTAURANT_OWNER');

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم الاختبار',
            'slug' => 'test-restaurant-images',
            'phone' => '01001112233',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'minimum_order_amount' => 20,
            'delivery_fee' => 10,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        return [$owner, $restaurant];
    }

    public function test_restaurant_owner_can_upload_cover_and_logo_images(): void
    {
        Storage::fake('public');

        [$owner, $restaurant] = $this->restaurantOwner();

        $cover = UploadedFile::fake()->image('cover.jpg', 800, 400);
        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($owner)
            ->post('/restaurant/settings', [
                'name' => $restaurant->name,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'opening_time' => '08:00',
                'closing_time' => '23:00',
                'cover_image' => $cover,
                'logo' => $logo,
            ])
            ->assertRedirect();

        $restaurant->refresh();

        $this->assertNotNull($restaurant->cover_image);
        $this->assertNotNull($restaurant->logo);
        $this->assertStringStartsWith("restaurants/{$restaurant->id}/", $restaurant->cover_image);
        $this->assertStringStartsWith("restaurants/{$restaurant->id}/", $restaurant->logo);
        Storage::disk('public')->assertExists($restaurant->cover_image);
        Storage::disk('public')->assertExists($restaurant->logo);
    }

    public function test_restaurant_owner_replacing_cover_deletes_previous_file(): void
    {
        Storage::fake('public');

        [$owner, $restaurant] = $this->restaurantOwner();

        $oldPath = "restaurants/{$restaurant->id}/old-cover.jpg";
        Storage::disk('public')->put($oldPath, 'old');
        $restaurant->update(['cover_image' => $oldPath]);

        $this->actingAs($owner)
            ->post('/restaurant/settings', [
                'name' => $restaurant->name,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'opening_time' => '08:00',
                'closing_time' => '23:00',
                'cover_image' => UploadedFile::fake()->image('new-cover.jpg'),
            ])
            ->assertRedirect();

        $restaurant->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($restaurant->cover_image);
    }
}
