<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillboardTest extends TestCase
{
    use RefreshDatabase;

    public static function urls(): array
    {
        return [
            'https' => ['https://le-clap.fr', true],
            'relative' => ['/playlists/jt', true],
            'javascript' => ['javascript:alert(1)', false],
            'uppercase javascript' => ['JavaScript:alert(1)', false],
            'protocol-less' => ['le-clap.fr', false],
        ];
    }

    #[DataProvider('urls')]
    public function test_only_http_or_relative_urls_are_saved(string $url, bool $accepted): void
    {
        Storage::fake('local');
        $user = User::factory()->withPermissions('manager.design')->create();

        $response = $this->actingAs($user)->post('/manager/presentation', [
            'title' => 'Soirée projection',
            'button' => 'Voir',
            'url' => $url,
            'color' => 'gradient-dark-red',
        ]);

        if ($accepted) {
            $response->assertSessionHasNoErrors();
            $this->assertSame($url, json_decode(Storage::disk('local')->get('billboard.json'), true)[0]['url']);
        } else {
            $response->assertSessionHasErrors('url');
            Storage::disk('local')->assertMissing('billboard.json');
        }
    }
}
