<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_news_only_shows_posts_that_have_been_published(): void
    {
        $published = Post::query()->create($this->postData('Bài đã đăng', 'bai-da-dang'));
        Post::query()->create($this->postData('Bản nháp', 'ban-nhap', Post::STATUS_DRAFT));
        Post::query()->create(array_merge(
            $this->postData('Bài hẹn giờ', 'bai-hen-gio'),
            ['published_at' => now()->addDay()],
        ));

        $this->get(route('customer.posts.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Bản nháp')
            ->assertDontSee('Bài hẹn giờ');
        $this->get(route('customer.posts.show', $published))->assertOk();
        $this->get('/tin-tuc/ban-nhap')->assertNotFound();
    }

    public function test_manager_can_create_update_and_delete_a_post_with_an_image(): void
    {
        Storage::fake('public');
        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('admin.posts.store'), $this->formData() + [
                'featured_image' => UploadedFile::fake()->image('event.jpg'),
            ])
            ->assertRedirect();

        $post = Post::query()->sole();
        $this->assertSame('dem-nhac-cuoi-tuan', $post->slug);
        $this->assertSame($manager->id, $post->author_user_id);
        $this->assertNotNull($post->published_at);
        Storage::disk('public')->assertExists($post->featured_image_path);

        $this->put(route('admin.posts.update', $post), $this->formData('Nội dung mới') + [
            'slug' => 'noi-dung-moi',
            'status' => Post::STATUS_DRAFT,
            'remove_featured_image' => '1',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('Nội dung mới', $post->title);
        $this->assertNull($post->featured_image_path);

        $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_staff_cannot_manage_news_posts(): void
    {
        $staff = User::factory()->forRole(Role::query()->where('code', 'staff')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $staff->id,
            'employee_code' => 'NEWS-STAFF',
            'name' => 'News staff',
            'status' => EmployeeStatus::Active,
        ]);

        $this->actingAs($staff)->get(route('admin.posts.index'))->assertForbidden();
    }

    public function test_custom_categories_and_rich_content_are_supported(): void
    {
        $this->actingAs($this->manager());
        $this->post(route('admin.post-categories.store'), ['name' => 'Món mới'])->assertRedirect();
        $category = PostCategory::where('slug', 'mon-moi')->firstOrFail();
        $this->post(route('admin.posts.store'), array_merge($this->formData(), [
            'category' => $category->slug,
            'content_format' => 'html',
            'content' => '<h2>Món mới</h2><p><strong>Nội dung</strong></p><img src="/storage/posts/content/test.jpg" alt="Món ăn" onerror="alert(1)"><script>alert(2)</script>',
        ]))->assertRedirect();
        $post = Post::query()->sole();
        $this->assertStringContainsString('<strong>Nội dung</strong>', $post->content);
        $this->assertStringNotContainsString('onerror', $post->content);
        $this->assertStringNotContainsString('<script', $post->content);
        $this->get(route('customer.posts.show', $post))->assertOk()->assertSee('<h2>Món mới</h2>', false);
        $this->get(route('customer.posts.index', ['category' => 'mon-moi']))->assertOk()->assertSee($post->title);
        $this->get('/')->assertOk();
        $this->delete(route('admin.post-categories.destroy', $category))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('post_categories', ['id' => $category->id]);
    }

    public function test_inline_images_require_authorization_and_image_validation(): void
    {
        Storage::fake('public');
        $this->postJson(route('admin.post-images.store'), [])->assertUnauthorized();
        $this->actingAs($this->manager());
        $response = $this->postJson(route('admin.post-images.store'), ['file' => UploadedFile::fake()->image('photo.jpg')])
            ->assertOk()->assertJsonStructure(['location']);
        $this->assertStringContainsString('/storage/posts/content/', $response->json('location'));
        $this->postJson(route('admin.post-images.store'), ['file' => UploadedFile::fake()->create('bad.html', 1, 'text/html')])
            ->assertUnprocessable();
    }

    public function test_category_order_and_slug_changes_preserve_articles(): void
    {
        $this->actingAs($this->manager());
        $category = PostCategory::where('slug', 'event')->firstOrFail();
        $post = Post::create($this->postData('Bài sự kiện', 'bai-su-kien'));
        $this->get(route('admin.post-categories.create'))->assertOk();
        $this->get(route('admin.post-categories.edit', $category))->assertOk()->assertSee('Thứ tự hiển thị');
        $this->put(route('admin.post-categories.update', $category), [
            'name' => 'Sự kiện mới', 'slug' => 'su-kien-moi', 'sort_order' => 0,
        ])->assertRedirect(route('admin.post-categories.index'));
        $this->assertSame('su-kien-moi', $post->fresh()->category);
        $this->assertSame('su-kien-moi', array_key_first(PostCategory::labels()));
        $this->get(route('customer.posts.index'))->assertOk()->assertSeeInOrder(['Sự kiện mới', 'Ẩm thực']);
        $this->get(route('customer.posts.show', $post))->assertOk();
        $this->put(route('admin.post-categories.update', $category), [
            'name' => 'Không hợp lệ', 'slug' => 'food', 'sort_order' => -1,
        ])->assertSessionHasErrors(['slug', 'sort_order']);
        $this->assertSame('su-kien-moi', $category->fresh()->slug);
    }

    /** @return array<string, mixed> */
    private function postData(string $title, string $slug, string $status = Post::STATUS_PUBLISHED): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'category' => Post::CATEGORY_EVENT,
            'excerpt' => 'Thông tin ngắn gọn về bài viết.',
            'content' => "Đoạn nội dung đầu tiên.\n\nĐoạn nội dung tiếp theo.",
            'status' => $status,
            'is_featured' => false,
            'published_at' => now()->subMinute(),
        ];
    }

    /** @return array<string, mixed> */
    private function formData(string $title = 'Đêm nhạc cuối tuần'): array
    {
        return [
            'title' => $title,
            'slug' => '',
            'category' => Post::CATEGORY_EVENT,
            'excerpt' => 'Một buổi tối nhiều âm nhạc và món ngon.',
            'content' => "Thông tin sự kiện.\n\nHẹn gặp bạn tại 89 Beer Garden.",
            'status' => Post::STATUS_PUBLISHED,
            'is_featured' => '1',
            'published_at' => '',
        ];
    }

    private function manager(): User
    {
        $manager = User::factory()
            ->forRole(Role::query()->where('code', 'manager')->firstOrFail())
            ->create();
        Employee::query()->forceCreate([
            'user_id' => $manager->id,
            'employee_code' => 'NEWS-MANAGER',
            'name' => 'News manager',
            'status' => EmployeeStatus::Active,
        ]);

        return $manager;
    }
}
