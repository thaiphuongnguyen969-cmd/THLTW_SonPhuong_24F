<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('role', 20)->default('customer');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('excerpt', 1000)->nullable();
            $table->longText('content');
            $table->string('thumbnail', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['posts', 'personal_access_tokens', 'users'] as $table) Schema::dropIfExists($table);
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    public function test_only_active_admins_can_create_update_and_soft_delete_posts(): void
    {
        $admin = $this->createUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'New Article', 'slug' => 'new-article', 'content' => 'Plain text body', 'status' => 'draft',
            'user_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertDatabaseCount('posts', 0);

        $response = $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'New Article', 'slug' => 'new-article', 'content' => 'Plain text body', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.author.id', $admin->id)
            ->assertJsonPath('data.status', 'draft')->assertJsonMissingPath('data.password');
        $this->assertSame($admin->id, $response->json('data.user_id'));

        $this->withToken($token)->patchJson('/api/admin/posts/new-article', [
            'title' => 'Published Article', 'status' => 'published', 'content' => 'Updated body',
        ])->assertOk()->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.title', 'Published Article');
        $this->assertNotNull(Post::where('slug', 'new-article')->firstOrFail()->published_at);

        $this->withToken($token)->deleteJson('/api/admin/posts/new-article')->assertOk();
        $this->assertSoftDeleted('posts', ['slug' => 'new-article']);
        $this->getJson('/api/posts/new-article')->assertNotFound();

        $customer = $this->createUser('customer@example.test', 'customer');
        $this->app['auth']->forgetGuards();
        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/admin/posts')->assertForbidden();
        $inactiveAdmin = $this->createUser('inactive-admin@example.test', 'admin', 'inactive');
        $this->app['auth']->forgetGuards();
        $this->withToken($inactiveAdmin->createToken('inactive-admin')->plainTextToken)
            ->getJson('/api/admin/posts')->assertUnauthorized();
    }

    public function test_admin_post_list_search_paginates_and_validates_slug_and_status(): void
    {
        $admin = $this->createUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;
        $first = $this->createPost('Matching Article', 'matching-article', 'draft');
        $newest = $this->createPost('Other Article', 'other-article', 'published');

        $this->withToken($token)->getJson('/api/admin/posts?search=Matching&per_page=1')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $first->id);
        $this->withToken($token)->getJson('/api/admin/posts?per_page=1&page=1')
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $newest->id);
        $this->withToken($token)->getJson('/api/admin/posts?status=unknown')
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Bad Post', 'slug' => 'bad--slug', 'content' => 'Body', 'status' => 'published',
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Bad Status', 'slug' => 'bad-status', 'content' => 'Body', 'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $deleted = $this->createPost('Retired Article', 'retired-article');
        $deleted->delete();
        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Slug Reuse', 'slug' => 'retired-article', 'content' => 'Body', 'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_public_api_only_returns_published_posts_whose_publish_time_has_arrived(): void
    {
        $published = $this->createPost('Public Article', 'public-article', 'published', now()->subMinute());
        $this->createPost('Draft Article', 'draft-article', 'draft', now()->subMinute());
        $this->createPost('Scheduled Article', 'scheduled-article', 'published', now()->addDay());
        $deleted = $this->createPost('Deleted Article', 'deleted-article', 'published', now()->subMinute());
        $deleted->delete();

        $this->getJson('/api/posts?per_page=1')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', $published->slug)
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.user_id');
        $this->getJson('/api/posts/draft-article')->assertNotFound();
        $this->getJson('/api/posts/scheduled-article')->assertNotFound();
        $this->getJson('/api/posts/deleted-article')->assertNotFound();
        $this->getJson('/api/posts/public-article')->assertOk()
            ->assertJsonPath('data.content', $published->content)
            ->assertJsonMissingPath('data.deleted_at');
    }

    public function test_thumbnail_upload_is_validated_stored_and_exposed_only_as_a_public_url(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l6sAAAAASUVORK5CYII=');

        $response = $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/posts', [
            'title' => 'Image Article', 'slug' => 'image-article', 'content' => 'Body', 'status' => 'draft',
            'thumbnail' => UploadedFile::fake()->createWithContent('cover.png', $png),
        ])->assertCreated();

        $storedPath = Post::where('slug', 'image-article')->value('thumbnail');
        $this->assertNotEmpty($storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertStringContainsString('/storage/posts/', $response->json('data.thumbnail'));

        $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/posts', [
            'title' => 'Invalid Image', 'slug' => 'invalid-image', 'content' => 'Body', 'status' => 'draft',
            'thumbnail' => UploadedFile::fake()->create('malware.svg', 10, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors('thumbnail');
    }

    private function createUser(string $email, string $role, string $status = 'active'): User
    {
        $user = User::create(['name' => 'Test User', 'email' => $email, 'password' => Hash::make('secret123')]);
        $user->forceFill(['role' => $role, 'status' => $status])->save();
        return $user;
    }

    private function createPost(string $title, string $slug, string $status = 'draft', mixed $publishedAt = null): Post
    {
        return Post::create([
            'title' => $title, 'slug' => $slug, 'content' => 'Plain text body',
            'status' => $status, 'published_at' => $publishedAt,
        ]);
    }
}
