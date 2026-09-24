<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class NewsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('NewsDemoSeeder may only run in the local or testing environment.');
        }

        $authorId = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('code', ['admin', 'manager']))
            ->value('id');

        foreach ($this->posts() as $post) {
            Post::withTrashed()->updateOrCreate(
                ['slug' => $post['slug']],
                $post + ['author_user_id' => $authorId, 'deleted_at' => null],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function posts(): array
    {
        return [
            [
                'title' => 'Hẹn nhau cuối tuần tại vườn bia 89',
                'slug' => 'hen-nhau-cuoi-tuan-tai-vuon-bia-89',
                'category' => Post::CATEGORY_EVENT,
                'excerpt' => 'Không gian thoáng, món nóng vừa ra bếp và một buổi tối đủ vui để cả nhóm ngồi lâu hơn.',
                'content' => "Cuối tuần là lúc cả nhóm tạm gác công việc để ngồi lại bên nhau. 89 Beer Garden chuẩn bị không gian ngoài trời thoáng đãng, bàn nhóm linh hoạt và thực đơn phù hợp cho những buổi gặp mặt đông người.\n\nCác món nướng và món nhắm được phục vụ liên tục trong buổi tối. Bạn có thể đặt bàn trước để nhà hàng chủ động sắp xếp vị trí phù hợp với số lượng khách.\n\nNếu đi theo nhóm lớn, hãy để lại ghi chú khi đặt bàn. Nhân viên sẽ liên hệ xác nhận và hỗ trợ chuẩn bị trước khi bạn đến.",
                'featured_image_path' => null,
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => true,
                'published_at' => now()->subDays(1)->setTime(18, 0),
            ],
            [
                'title' => 'Chả giò hải sản: món mở đầu dễ gọi cho bàn đông',
                'slug' => 'cha-gio-hai-san-mon-mo-dau-cho-ban-dong',
                'category' => Post::CATEGORY_FOOD,
                'excerpt' => 'Lớp vỏ giòn, phần nhân vừa vị và cách dùng ngon nhất khi món còn nóng.',
                'content' => "Chả giò hải sản là lựa chọn dễ chia sẻ khi cả bàn đang chờ những món chính. Món được chiên khi có yêu cầu để giữ lớp vỏ giòn và phần nhân nóng.\n\nKhi dùng tại bàn, bạn nên thưởng thức ngay sau khi món được phục vụ. Rau ăn kèm và nước chấm giúp cân bằng vị, phù hợp cả với khách không dùng đồ uống có cồn.\n\nĐây cũng là món phù hợp để gọi thêm cho bàn đông hoặc những buổi gặp mặt gia đình.",
                'featured_image_path' => null,
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(4)->setTime(11, 30),
            ],
            [
                'title' => 'Đặt bàn sớm, giữ chỗ đẹp cho buổi gặp mặt',
                'slug' => 'dat-ban-som-giu-cho-dep-cho-buoi-gap-mat',
                'category' => Post::CATEGORY_PROMOTION,
                'excerpt' => 'Gửi yêu cầu trước để nhà hàng chuẩn bị bàn phù hợp và giảm thời gian chờ khi đến quán.',
                'content' => "Vào các buổi tối cuối tuần, khu vực ngoài trời thường được khách lựa chọn sớm. Đặt bàn trước giúp nhà hàng chuẩn bị vị trí phù hợp với số khách và thời gian bạn dự kiến đến.\n\nSau khi gửi yêu cầu trên website, bạn sẽ nhận được mã đặt bàn ngắn gọn. Nhà hàng sẽ liên hệ qua số điện thoại để xác nhận thông tin.\n\nViệc gửi yêu cầu không mất phí. Nếu kế hoạch thay đổi, bạn chỉ cần báo lại để nhân viên hỗ trợ điều chỉnh.",
                'featured_image_path' => null,
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(7)->setTime(9, 0),
            ],
        ];
    }
}
