<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class Blog89Seeder extends Seeder
{
    public function run(): void
    {
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
                'title' => 'Bí quyết gọi món cho bàn nhậu đông người: đủ vị, dễ chia và không bị ngán',
                'slug' => 'bi-quyet-goi-mon-cho-ban-nhau-dong-nguoi',
                'category' => Post::CATEGORY_FOOD,
                'excerpt' => 'Một bàn tiệc vui không cần gọi thật nhiều, chỉ cần kết hợp món khai vị, món đậm vị, rau và món nóng theo đúng nhịp.',
                'content' => <<<'HTML'
<p>Đi đông vui thật, nhưng chọn món cho một bàn nhiều khẩu vị lại không hề đơn giản. Người thích món nướng, người chuộng món thanh, có người chỉ muốn lai rai và cũng có người đang chờ một món chắc bụng. Cách dễ nhất là đừng chọn từng món riêng lẻ, hãy nghĩ về cả bàn ăn như một hành trình có mở đầu, cao trào và đoạn kết.</p>
<h2>Mở đầu bằng món dễ chia</h2>
<p>Lạc luộc, ngô chiên, nem chua hay đậu phụ tẩm hành là những lựa chọn giúp cả bàn có món dùng ngay trong lúc trò chuyện. Các món này vừa miệng, dễ chia sẻ và không làm mọi người no quá sớm.</p>
<h2>Chọn hai món chủ lực khác cách chế biến</h2>
<p>Một món nướng thơm lửa kết hợp với một món xào hoặc hấp sẽ tạo cảm giác phong phú hơn nhiều so với việc gọi liên tiếp các món cùng vị. Với bàn từ sáu người, bạn có thể chọn một món thịt, một món hải sản rồi bổ sung rau xào hoặc canh để cân bằng.</p>
<h2>Để món nóng xuất hiện đúng lúc</h2>
<p>Lẩu nên được gọi khi cuộc vui đã vào nhịp. Nước dùng nóng, rau và các phần ăn kèm giúp cả bàn ngồi lâu mà vẫn thấy dễ chịu. Nếu chưa chắc khẩu phần, hãy hỏi nhân viên trước khi gọi thêm; một bàn vừa đủ luôn ngon hơn một bàn quá nhiều món bị nguội.</p>
<p>Tại 89 Beer Garden, đội ngũ phục vụ có thể gợi ý lượng món theo số khách và sở thích của nhóm. Bạn chỉ cần cho biết bàn có trẻ em, người không ăn cay hoặc món đặc biệt muốn thử, phần còn lại hãy để chúng tôi giúp bạn sắp xếp.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/menu/89-lau-thap-cam.webp',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => true,
                'published_at' => now()->subDays(1)->setTime(18, 30),
            ],
            [
                'title' => 'Một góc vườn, một bàn quen và câu chuyện kéo dài đến tối',
                'slug' => 'mot-goc-vuon-mot-ban-quen-va-cau-chuyen-keo-dai-den-toi',
                'category' => Post::CATEGORY_STORY,
                'excerpt' => 'Có những cuộc hẹn chẳng cần lý do lớn lao: chỉ cần đủ người, một chiếc bàn thoáng và vài món nóng vừa lên.',
                'content' => <<<'HTML'
<p>Thành phố càng bận, những cuộc hẹn tưởng như đơn giản lại càng đáng quý. Một tin nhắn “tối nay gặp nhé”, vài cái gật đầu và thế là chiếc bàn quen lại có người ngồi đủ. Ở đó, điện thoại dần được đặt xuống, câu chuyện được nối dài và thời gian bỗng trôi chậm hơn.</p>
<h2>Không gian cũng là một phần của bữa ăn</h2>
<p>89 Beer Garden chọn không gian mở với nhiều khoảng xanh và ánh đèn ấm. Tiếng nói chuyện có thể rộn ràng nhưng mỗi bàn vẫn giữ được cảm giác riêng. Khi gió tối đi qua khu vườn, một món vừa ra bếp và một ly bia mát thường là tất cả những gì cần thiết cho một buổi gặp mặt dễ chịu.</p>
<h2>Những chiếc bàn lưu giữ kỷ niệm</h2>
<p>Có bàn là buổi liên hoan sau một dự án dài. Có bàn là cuộc hội ngộ của nhóm bạn lâu ngày mới đủ mặt. Cũng có những gia đình chỉ muốn đổi không khí cho bữa tối cuối tuần. Mỗi nhóm đến với một câu chuyện khác nhau, rồi rời đi với thêm một kỷ niệm chung.</p>
<p>Chúng tôi tin rằng quán ăn đáng nhớ không chỉ bởi món ngon. Điều còn lại lâu hơn là cảm giác được ngồi thoải mái, được phục vụ chân thành và được vui trọn vẹn bên những người mình quý.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/brand/atmosphere-evening.jpg',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(3)->setTime(20, 0),
            ],
            [
                'title' => 'Ba chỉ rang: món quen nhưng luôn khiến cả bàn gắp thêm',
                'slug' => 'ba-chi-rang-mon-quen-nhung-luon-khien-ca-ban-gap-them',
                'category' => Post::CATEGORY_FOOD,
                'excerpt' => 'Thịt ba chỉ cháy cạnh, vị mặn ngọt vừa đủ và mùi thơm bắt cơm tạo nên sức hút của một món rất đỗi thân quen.',
                'content' => <<<'HTML'
<p>Ba chỉ rang không phải món cầu kỳ, nhưng để ngon lại cần sự chính xác. Miếng thịt phải có tỷ lệ nạc mỡ cân đối, được đảo trên lửa đủ lớn để phần cạnh xém nhẹ mà bên trong vẫn mềm. Khi gia vị bám đều và mỡ trong vừa tới, mùi thơm quen thuộc có thể khiến cả bàn lập tức thấy đói.</p>
<h2>Điểm ngon nằm ở độ cháy cạnh</h2>
<p>Rang quá nhanh, thịt chưa kịp săn và hương vị còn nhạt. Rang quá lâu, phần nạc dễ khô. Khoảnh khắc đẹp nhất là khi mặt thịt ngả màu nâu óng, phần mỡ trong lại và nước sốt chỉ còn một lớp mỏng bao quanh từng miếng.</p>
<h2>Món nhắm cũng là món ăn cùng cơm rất hợp</h2>
<p>Ba chỉ rang có vị đậm vừa phải nên dùng cùng bia mát rất “vào”, nhưng khi ăn với cơm trắng nóng lại mang một nét ngon khác. Thêm đĩa rau luộc hoặc rau xào là bàn ăn đã có đủ vị béo, mặn, thơm và thanh.</p>
<p>Đây là một trong những món phù hợp cho cả nhóm bạn lẫn bữa ăn gia đình. Quen thuộc, dễ ăn và gần như luôn là chiếc đĩa hết sớm nhất trên bàn.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/menu/89-ba-chi-rang.webp',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(5)->setTime(11, 15),
            ],
            [
                'title' => 'Hẹn đội mình tại 89: gợi ý tổ chức sinh nhật và liên hoan thật gọn',
                'slug' => 'goi-y-to-chuc-sinh-nhat-va-lien-hoan-tai-89',
                'category' => Post::CATEGORY_EVENT,
                'excerpt' => 'Chuẩn bị trước số khách, thời gian và khẩu vị giúp buổi gặp mặt đông người diễn ra nhẹ nhàng hơn rất nhiều.',
                'content' => <<<'HTML'
<p>Một buổi sinh nhật hay liên hoan đáng nhớ không nhất thiết phải chuẩn bị quá phức tạp. Điều quan trọng nhất là mọi người có thể ngồi gần nhau, món ăn được lên đều và người tổ chức cũng có thời gian tận hưởng cuộc vui thay vì liên tục xử lý những việc nhỏ.</p>
<h2>Chốt số người và đặt bàn sớm</h2>
<p>Hãy ước lượng số khách sát nhất có thể và báo trước nếu nhóm cần bàn liền, vị trí thoáng hoặc không gian thuận tiện cho gia đình có trẻ nhỏ. Với tối cuối tuần, đặt sớm giúp quán chủ động giữ khu vực phù hợp và hạn chế thời gian chờ.</p>
<h2>Thống nhất trước một phần thực đơn</h2>
<p>Bạn không cần chốt mọi món từ đầu. Chỉ cần chọn trước vài món khai vị và món chủ lực để bàn có đồ ăn ngay khi đủ khách. Các món gọi thêm có thể điều chỉnh theo tốc độ dùng và khẩu vị thực tế của cả nhóm.</p>
<h2>Dành chỗ cho khoảnh khắc chính</h2>
<p>Nếu có bánh sinh nhật hoặc một phần bất ngờ, hãy ghi chú khi đặt bàn để nhân viên hỗ trợ thời điểm mang ra phù hợp. Một chút phối hợp trước giúp khoảnh khắc chúc mừng diễn ra tự nhiên hơn và không làm gián đoạn bữa ăn.</p>
<p>Bạn có thể gửi yêu cầu đặt bàn trực tiếp trên website. Đội ngũ 89 sẽ liên hệ xác nhận và cùng bạn chuẩn bị một buổi gặp mặt thật gọn, thật vui.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/brand/beer-cheers.jpg',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(7)->setTime(17, 45),
            ],
            [
                'title' => 'Lẩu ngon hơn khi ăn đúng nhịp: từ nước dùng đến lượt rau cuối cùng',
                'slug' => 'lau-ngon-hon-khi-an-dung-nhip',
                'category' => Post::CATEGORY_FOOD,
                'excerpt' => 'Một nồi lẩu trọn vị bắt đầu từ cách nếm nước dùng, thả nguyên liệu theo thứ tự và giữ lửa vừa trong suốt bữa ăn.',
                'content' => <<<'HTML'
<p>Lẩu luôn có cách kéo mọi người lại gần nhau. Cùng một nồi nước dùng, mỗi người góp một tay, chờ món chín rồi chia nhau khi còn nóng. Nhưng để nồi lẩu giữ vị ngon từ đầu đến cuối, một vài thói quen nhỏ tạo ra khác biệt rất rõ.</p>
<h2>Nếm nước dùng trước khi thêm nguyên liệu</h2>
<p>Khi lẩu vừa sôi, hãy nếm một chút nước dùng nguyên bản. Đây là lúc vị được cân chỉnh tốt nhất. Đừng vội cho toàn bộ rau và đồ nhúng vào cùng lúc vì nước sẽ hạ nhiệt, nguyên liệu dễ chín không đều và hương vị ban đầu bị loãng nhanh.</p>
<h2>Thả món lâu chín trước, rau xanh sau</h2>
<p>Các phần cần thời gian như xương, thịt dày hoặc nấm nên được cho vào trước. Rau lá và mì chỉ nên thêm theo từng lượt nhỏ, vừa đủ dùng. Cách này giúp rau giữ màu, đồ nhúng không bị dai và nước lẩu luôn nóng.</p>
<h2>Giữ lại một khoảng bụng cho đoạn cuối</h2>
<p>Phần ngon thú vị nhất thường đến khi nước dùng đã quyện vị của nhiều nguyên liệu. Một ít bún hoặc mì ở cuối bữa vừa đủ ấm bụng mà không quá nặng. Nếu đi đông, gọi lượng vừa phải rồi bổ sung sẽ giúp mọi thứ luôn tươi và nóng.</p>
<p>Ở 89 có nhiều lựa chọn từ lẩu gà, lẩu ếch măng cay đến lẩu cá chép om dưa. Mỗi nồi mang một sắc thái riêng, nhưng đều ngon nhất khi cả bàn thong thả thưởng thức cùng nhau.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/menu/89-lau-ca-chep-om-dua.webp',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(9)->setTime(12, 0),
            ],
            [
                'title' => 'Đi bốn người gọi gì ở 89 để vừa ngon vừa gọn?',
                'slug' => 'di-bon-nguoi-goi-gi-o-89-de-vua-ngon-vua-gon',
                'category' => Post::CATEGORY_PROMOTION,
                'excerpt' => 'Gợi ý một bàn ăn cân bằng cho nhóm bốn người với món mở đầu, món chính, rau và lựa chọn gọi thêm linh hoạt.',
                'content' => <<<'HTML'
<p>Nhóm bốn người là số lượng rất đẹp cho một cuộc hẹn: đủ đông để gọi nhiều vị, nhưng vẫn dễ trò chuyện và chia món. Thay vì gọi theo cảm hứng rồi nhanh no, bạn có thể bắt đầu bằng một công thức đơn giản gồm món mở đầu, hai món chính và một món cân bằng.</p>
<h2>Một món vui miệng để bắt đầu</h2>
<p>Đậu phụ tẩm hành, nem Bùi hoặc ngô chiên là những lựa chọn vừa đủ cho lúc chờ món nóng. Nếu nhóm thích vị tươi và thanh hơn, một đĩa nộm cũng là cách mở đầu hợp lý.</p>
<h2>Hai món chính, hai sắc thái</h2>
<p>Hãy chọn một món thơm lửa như mực nướng hoặc cá chỉ vàng nướng, sau đó ghép với ba chỉ rang, thịt trâu xào hoặc một món hấp. Sự khác nhau về cách chế biến khiến bàn ăn thú vị mà không bị trùng vị.</p>
<h2>Thêm rau, rồi mới quyết định món cuối</h2>
<p>Một đĩa rau muống xào hoặc rau bí xào giúp cân bằng bàn ăn. Sau đó, nếu cả nhóm vẫn muốn ngồi lâu, hãy gọi thêm cơm rang hoặc một nồi lẩu nhỏ. Cách gọi theo hai nhịp giúp món luôn nóng và hạn chế dư thừa.</p>
<p>Thực đơn trực tuyến của 89 hiển thị tình trạng món theo thời điểm hiện tại. Bạn có thể xem trước, lưu lại vài lựa chọn và chốt thật nhanh khi cả nhóm đã ngồi đủ.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/menu/89-muc-nuong.webp',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(12)->setTime(10, 30),
            ],
            [
                'title' => 'Từ gian bếp đến bàn ăn: vì sao món nóng nên được thưởng thức ngay?',
                'slug' => 'tu-gian-bep-den-ban-an-vi-sao-mon-nong-nen-thuong-thuc-ngay',
                'category' => Post::CATEGORY_STORY,
                'excerpt' => 'Nhiệt độ không chỉ giữ món ấm; nó quyết định mùi thơm, kết cấu và khoảnh khắc món ăn đạt trạng thái ngon nhất.',
                'content' => <<<'HTML'
<p>Trong bếp, mỗi món có một khoảnh khắc đẹp nhất. Đó có thể là lúc lớp vỏ vừa giòn, mặt thịt vừa xém cạnh hay hơi nước còn mang theo mùi thơm của gừng, sả và lá chanh. Từ bếp ra bàn chỉ vài phút, nhưng chính vài phút ấy quyết định phần lớn trải nghiệm của người ăn.</p>
<h2>Nhiệt làm hương thơm rõ hơn</h2>
<p>Khi món còn nóng, các phân tử hương lan tỏa mạnh và vị giác cảm nhận món ăn trọn vẹn hơn. Với đồ nướng, phần mỡ vừa tan giúp bề mặt bóng, mềm và thơm. Với món chiên, nhiệt độ phù hợp giữ lớp ngoài giòn trong khi bên trong chưa bị khô.</p>
<h2>Phục vụ theo nhịp thay vì lên cùng lúc</h2>
<p>Một bàn đông không nhất thiết phải kín món ngay từ đầu. Lên món theo nhịp giúp mỗi đĩa được dùng ở trạng thái ngon nhất, đồng thời tạo khoảng nghỉ để cả bàn trò chuyện và cảm nhận rõ từng hương vị.</p>
<h2>Sự phối hợp thầm lặng</h2>
<p>Đằng sau một món đến bàn đúng lúc là sự phối hợp giữa người nhận món, gian bếp và nhân viên phục vụ. Từ thứ tự chế biến đến vị trí bàn đều cần được truyền đạt chính xác. Đó là công việc ít khi được nhìn thấy, nhưng là điều đội ngũ 89 chú trọng trong mỗi ca phục vụ.</p>
<p>Vì vậy, khi món vừa được đặt xuống, đừng chờ quá lâu. Gắp một miếng khi còn nóng chính là cách đơn giản nhất để thưởng thức đúng điều người đầu bếp muốn gửi tới bạn.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/menu/89-ech-rang-muoi.webp',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(15)->setTime(19, 15),
            ],
            [
                'title' => 'Cuối tuần không cần đi xa: một buổi tối thư thả ngay giữa Bắc Ninh',
                'slug' => 'cuoi-tuan-khong-can-di-xa-mot-buoi-toi-thu-tha-tai-bac-ninh',
                'category' => Post::CATEGORY_EVENT,
                'excerpt' => 'Đổi không khí bằng một buổi tối ngoài trời, món ngon và cuộc trò chuyện không vội vàng cùng những người thân quen.',
                'content' => <<<'HTML'
<p>Sau một tuần kín lịch, đôi khi điều chúng ta cần không phải là một chuyến đi xa. Chỉ cần rời màn hình sớm hơn, hẹn vài người thân quen và chọn một nơi đủ thoáng để ngồi tới khi câu chuyện tự nhiên chậm lại.</p>
<h2>Bắt đầu buổi tối trước giờ đông khách</h2>
<p>Nếu thích không khí nhẹ nhàng, bạn có thể đến sớm hơn một chút để chọn món và ngắm khu vườn lên đèn. Đây cũng là khoảng thời gian phù hợp cho gia đình có trẻ nhỏ hoặc nhóm muốn dùng bữa trước khi bắt đầu cuộc vui.</p>
<h2>Chọn món theo thời tiết</h2>
<p>Ngày mát hợp với món nướng thơm lửa và một nồi lẩu nóng. Tối oi hơn, hãy mở đầu bằng nộm, rau luộc hoặc hải sản rồi gọi thêm món đậm vị sau. Ăn theo thời tiết khiến cơ thể dễ chịu và bữa tối cũng có nhịp riêng.</p>
<h2>Đừng xếp lịch quá sát</h2>
<p>Một cuộc hẹn vui thường kéo dài hơn dự kiến. Hãy để lịch trình có một khoảng rộng, để không ai phải nhìn đồng hồ khi câu chuyện đang hay. Nếu đi nhóm đông, đặt bàn trước là cách đơn giản để buổi tối bắt đầu suôn sẻ.</p>
<p>89 Beer Garden mong trở thành một điểm hẹn gần gũi như thế: không cần dịp đặc biệt, chỉ cần bạn muốn gặp nhau và dành cho nhau một buổi tối thật trọn vẹn.</p>
HTML,
                'content_format' => 'html',
                'featured_image_path' => '/images/beer-garden-hero.png',
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => false,
                'published_at' => now()->subDays(18)->setTime(16, 45),
            ],
        ];
    }
}
