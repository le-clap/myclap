<?php

namespace Tests\Feature;

use App\Models\Billboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillboardTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Billboard::query()->delete();
        $this->manager = User::factory()->withPermissions('manager.design')->create();
    }

    private function fields(array $overrides = []): array
    {
        return [
            'title' => 'Soirée JT au foyer',
            'button' => 'Voir',
            'url' => 'https://le-clap.fr',
            'icon' => 'fa-star',
            'color' => 'gradient-dark-red',
            ...$overrides,
        ];
    }

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
        $response = $this->actingAs($this->manager)->post('/manager/presentation', $this->fields(['url' => $url]));

        if ($accepted) {
            $response->assertSessionHasNoErrors();
            $this->assertSame($url, Billboard::sole()->url);
        } else {
            $response->assertSessionHasErrors('url');
            $this->assertSame(0, Billboard::count());
        }
    }

    public function test_update_and_delete(): void
    {
        $billboard = Billboard::create($this->fields());

        $this->actingAs($this->manager)
            ->put("/manager/presentation/{$billboard->id}", $this->fields(['title' => 'Nouveau titre', 'icon' => '']))
            ->assertRedirect('/manager/presentation');
        $this->assertSame('Nouveau titre', $billboard->fresh()->title);
        $this->assertNull($billboard->fresh()->icon);

        $this->delete("/manager/presentation/{$billboard->id}")->assertRedirect('/manager/presentation');
        $this->assertSame(0, Billboard::count());
    }

    public function test_at_most_five_billboards(): void
    {
        foreach (range(1, 5) as $i) {
            Billboard::create($this->fields());
        }

        $this->actingAs($this->manager)->post('/manager/presentation', $this->fields())->assertSessionHasErrors('error');
        $this->get('/manager/presentation/ajouter')->assertRedirect('/manager/presentation');
        $this->assertSame(5, Billboard::count());
    }

    public function test_home_lists_billboards_in_creation_order(): void
    {
        Billboard::create($this->fields(['title' => 'Premier']));
        Billboard::create($this->fields(['title' => 'Second']));

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('billboards.0.title', 'Premier')
            ->where('billboards.1.title', 'Second'));
    }

    public function test_migration_imports_the_legacy_json_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('billboard.json', json_encode([
            ['identifier' => 'abc', 'title' => 'Ancien', 'button' => 'Go', 'url' => '/a', 'icon' => '', 'color' => 'gradient-sexy-blue'],
            ['identifier' => 'def', 'title' => 'Ancien 2', 'button' => 'Go', 'url' => '/b', 'icon' => 'fa-star', 'color' => 'gradient-dark-red'],
        ]));
        $migration = require database_path('migrations/2026_11_02_100000_create_billboard_table.php');

        $migration->down();
        $migration->up();

        $this->assertSame(['Ancien', 'Ancien 2'], Billboard::orderBy('id')->pluck('title')->all());
        $this->assertNull(Billboard::where('title', 'Ancien')->value('icon'));
    }
}
