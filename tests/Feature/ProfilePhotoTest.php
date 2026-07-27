<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_photo_that_is_compressed_under_500kb(): void
    {
        Storage::fake('public');
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Foto Profil']);

        $this->actingAs($student)->get(route('account.profile-photo.edit'))
            ->assertOk()
            ->assertSee('Foto profil saya');

        $this->actingAs($student)->put(route('account.profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('profil-besar.png', 2400, 2400)->size(6000),
        ])->assertSessionHas('success');

        $student->refresh();
        $this->assertNotNull($student->profile_photo_path);
        Storage::disk('public')->assertExists($student->profile_photo_path);
        $this->assertLessThanOrEqual(512000, Storage::disk('public')->size($student->profile_photo_path));

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('storage/'.$student->profile_photo_path);
    }

    public function test_staff_can_replace_and_delete_own_profile_photo(): void
    {
        Storage::fake('public');
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)->put(route('account.profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('awal.jpg', 900, 900),
        ])->assertSessionHas('success');
        $firstPath = $teacher->fresh()->profile_photo_path;

        $this->actingAs($teacher)->put(route('account.profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('baru.webp', 600, 600),
        ])->assertSessionHas('success');
        $secondPath = $teacher->fresh()->profile_photo_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($teacher)->delete(route('account.profile-photo.destroy'))
            ->assertSessionHas('success');

        $this->assertNull($teacher->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($secondPath);
    }
}
