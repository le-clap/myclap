<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/manager/videos')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_plain_student_cannot_open_manager(): void
    {
        $this->actingAs(User::factory()->create())->get('/manager')->assertForbidden();
    }

    public function test_manager_without_video_permission_cannot_manage_videos(): void
    {
        $user = User::factory()->withPermissions('manager.category')->create();

        $this->actingAs($user)->get('/manager/categories')->assertOk();
        $this->actingAs($user)->get('/manager/videos')->assertForbidden();
        $this->actingAs($user)->postJson('/manager/videos', [])->assertForbidden();
    }

    public function test_video_uploader_can_manage_videos_but_not_admin(): void
    {
        $user = User::factory()->withPermissions('manager.video.upload')->create();

        $this->actingAs($user)->get('/manager/videos')->assertOk();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_everything(): void
    {
        $admin = User::factory()->withPermissions('admin')->create();

        $this->actingAs($admin)->get('/manager/videos')->assertOk();
        $this->actingAs($admin)->get('/manager/stats')->assertOk();
        $this->actingAs($admin)->get('/admin/permissions')->assertOk();
    }
}
