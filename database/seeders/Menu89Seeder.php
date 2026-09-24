<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Menu89Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tags = collect([
                ['name' => 'HOT', 'slug' => 'hot', 'icon_class' => 'ti ti-flame', 'icon_color' => '#a33a11', 'background_color' => '#fff0e6', 'sort_order' => 10],
                ['name' => '10P', 'slug' => '10p', 'icon_class' => 'ti ti-clock-10', 'icon_color' => '#155b91', 'background_color' => '#eaf6ff', 'sort_order' => 20],
                ['name' => '15-20P', 'slug' => '15-20p', 'icon_class' => 'ti ti-clock', 'icon_color' => '#8b5a00', 'background_color' => '#fff7df', 'sort_order' => 30],
                ['name' => '30-40P', 'slug' => '30-40p', 'icon_class' => 'ti ti-clock-hour-4', 'icon_color' => '#65489a', 'background_color' => '#f5efff', 'sort_order' => 40],
                ['name' => 'Có Ngay', 'slug' => 'co-ngay', 'icon_class' => 'ti ti-bolt', 'icon_color' => '#17633f', 'background_color' => '#eaf7ef', 'sort_order' => 50],
            ])->mapWithKeys(fn (array $data) => [$data['slug'] => Tag::query()->updateOrCreate(['slug' => $data['slug']], $data)]);

            $categories = collect([
                ['name' => 'Khai vị & Món nhắm', 'slug' => 'khai-vi-mon-nham', 'description' => 'Các món ăn chơi, món nhắm và món dùng ngay.', 'sort_order' => 20],
                ['name' => 'Đặc sản đồng quê', 'slug' => 'dac-san-dong-que', 'description' => 'Chim câu, ếch và các món đặc sản dân dã.', 'sort_order' => 30],
                ['name' => 'Trâu & Bê', 'slug' => 'trau-be', 'description' => 'Các món thịt trâu và thịt bê chế biến nóng.', 'sort_order' => 40],
                ['name' => 'Heo & Nội tạng', 'slug' => 'heo-noi-tang', 'description' => 'Các món thịt heo, lòng và nội tạng.', 'sort_order' => 50],
                ['name' => 'Gà & Vịt', 'slug' => 'ga-vit', 'description' => 'Các món gia cầm luộc, rang, xào và nộm.', 'sort_order' => 60],
                ['name' => 'Cá & Hải sản', 'slug' => 'ca-hai-san', 'description' => 'Cá, tôm, mực và các món thủy hải sản.', 'sort_order' => 70],
                ['name' => 'Rau & Món kèm', 'slug' => 'rau-mon-kem', 'description' => 'Rau xanh và các món ăn kèm.', 'sort_order' => 80],
                ['name' => 'Cơm & Canh', 'slug' => 'com-canh', 'description' => 'Cơm rang, cơm trắng, canh và món ăn no.', 'sort_order' => 90],
                ['name' => 'Lẩu', 'slug' => 'lau', 'description' => 'Các món lẩu dùng chung cho bàn.', 'sort_order' => 100],
                ['name' => 'Bún', 'slug' => 'bun', 'description' => 'Các món bún phục vụ nhanh.', 'sort_order' => 110],
            ])->mapWithKeys(function (array $data): array {
                $category = Category::withTrashed()->updateOrCreate(
                    ['slug' => $data['slug']],
                    $data + ['status' => Category::STATUS_ACTIVE],
                );
                $category->restore();

                return [$data['slug'] => $category];
            });

            Product::query()
                ->whereDoesntHave('category', fn ($query) => $query->where(function ($query): void {
                    $query->where('slug', 'like', '%bia%')->orWhere('slug', 'like', '%nuoc%')->orWhere('slug', 'like', '%drink%')->orWhere('slug', 'like', '%do-uong%');
                }))
                ->delete();

            foreach ($this->items() as $position => $item) {
                [$name, $price, $priceLabel, $timeTag, $hot] = $item;
                $slug = '89-'.Str::slug($name);
                $available = $price > 0 && ! in_array($priceLabel, ['Theo giá', 'Liên hệ'], true);
                $categorySlug = $this->categorySlug($name);
                [$short, $description] = $this->contentFor($name, $categorySlug, $timeTag);
                $imagePath = public_path("images/menu/{$slug}.webp");
                $product = Product::withTrashed()->updateOrCreate(['slug' => $slug], [
                    'category_id' => $categories[$categorySlug]->id,
                    'name' => $name,
                    'short_description' => $short,
                    'description' => $description,
                    'price' => $price,
                    'image_url' => is_file($imagePath) ? "/images/menu/{$slug}.webp" : null,
                    'status' => Product::STATUS_ACTIVE,
                    'is_available' => $available,
                ]);
                $product->restore();
                $tagSlugs = array_values(array_filter([$hot ? 'hot' : null, $timeTag]));
                $product->tags()->sync($tags->only($tagSlugs)->pluck('id')->all());
                if (str_starts_with($name, 'Lẩu')) {
                    foreach ([400000, 500000, 600000] as $variantPosition => $variantPrice) {
                        $variant = $product->variants()->withTrashed()->updateOrCreate(
                            ['name' => 'Set '.($variantPrice / 1000).'K'],
                            ['price' => $variantPrice, 'is_available' => true, 'sort_order' => $variantPosition],
                        );
                        $variant->restore();
                    }
                    $product->variants()->whereNotIn('name', ['Set 400K', 'Set 500K', 'Set 600K'])->delete();
                    $product->update(['price' => 400000]);
                } else {
                    $product->variants()->delete();
                }
            }

            Category::query()->where('slug', 'mon-an-89')->delete();
        });
    }

    private function categorySlug(string $name): string
    {
        $name = Str::lower($name);

        if (str_starts_with($name, 'lẩu')) {
            return 'lau';
        }
        if (str_starts_with($name, 'bún')) {
            return 'bun';
        }
        if (Str::contains($name, ['cơm', 'canh chua', 'trứng rán'])) {
            return 'com-canh';
        }
        if (Str::contains($name, ['rau', 'mùng tơi', 'ngồng cải'])) {
            return 'rau-mon-kem';
        }
        if (Str::contains($name, ['cá ', 'cá trạch', 'lươn', 'mực', 'tôm'])) {
            return 'ca-hai-san';
        }
        if (Str::contains($name, ['gà', 'vịt', 'chân vịt', 'nộm chân vịt'])) {
            return 'ga-vit';
        }
        if (Str::contains($name, ['trâu', 'bê'])) {
            return 'trau-be';
        }
        if (Str::contains($name, ['khấu đuôi', 'lòng', 'chân giò', 'gan', 'ba chỉ', 'tràng', 'dạ dày', 'tim lợn', 'tai heo', 'tóp mỡ'])) {
            return 'heo-noi-tang';
        }
        if (Str::contains($name, ['chim câu', 'thịt chó', 'dồi chó', 'ếch', 'châu chấu'])) {
            return 'dac-san-dong-que';
        }

        return 'khai-vi-mon-nham';
    }

    /** @return array{string, string} */
    private function contentFor(string $name, string $categorySlug, ?string $timeTag): array
    {
        $normalizedName = Str::lower($name);
        $categoryDescriptions = [
            'khai-vi-mon-nham' => 'món khai vị và món nhắm dễ dùng khi bắt đầu bữa ăn',
            'dac-san-dong-que' => 'món đặc sản dân dã, hợp với những bàn thích hương vị đậm đà',
            'trau-be' => 'món nóng từ thịt trâu hoặc thịt bê, phù hợp gọi chung cho bàn',
            'heo-noi-tang' => 'món thịt và nội tạng được phục vụ nóng, chú trọng độ thơm và độ giòn mềm phù hợp',
            'ga-vit' => 'món gia cầm có khẩu phần phù hợp để dùng chung',
            'ca-hai-san' => 'món cá hoặc hải sản được chế biến sau khi khách gọi',
            'rau-mon-kem' => 'món rau và món ăn kèm giúp cân bằng bữa ăn',
            'com-canh' => 'món cơm hoặc canh dùng để hoàn thiện bữa chính',
            'lau' => 'món lẩu dùng chung, thích hợp cho nhóm bạn và gia đình',
            'bun' => 'món bún nóng, gọn bữa và dễ thưởng thức',
        ];
        $servingSuggestions = [
            'khai-vi-mon-nham' => 'dùng khai vị, gọi nhắm cùng đồ uống hoặc dùng xen kẽ với các món chính',
            'dac-san-dong-que' => 'dùng khi còn nóng và gọi thêm rau hoặc món nhắm để bàn ăn phong phú hơn',
            'trau-be' => 'dùng nóng, phù hợp gọi cùng rau, cơm hoặc đồ uống lạnh',
            'heo-noi-tang' => 'dùng ngay khi món còn nóng để giữ kết cấu và hương vị tốt nhất',
            'ga-vit' => 'gọi chung cho bàn và dùng cùng rau, cơm hoặc món nhắm',
            'ca-hai-san' => 'dùng nóng, có thể gọi kèm rau và món ăn no',
            'rau-mon-kem' => 'gọi kèm món thịt, món nướng hoặc lẩu để cân bằng khẩu vị',
            'com-canh' => 'dùng cùng các món mặn hoặc gọi riêng như một phần ăn no',
            'lau' => 'dùng chung tại bàn; nên chọn set phù hợp với số người và nhu cầu gọi thêm món',
            'bun' => 'dùng như món ăn no hoặc gọi thêm sau các món nhắm',
        ];

        [$preparation, $flavour] = match (true) {
            Str::contains($normalizedName, 'lẩu') => ['phục vụ theo set với nước lẩu nóng và phần nguyên liệu dùng chung', 'nước dùng đậm vừa, nóng lâu và phù hợp cho nhiều người'],
            Str::contains($normalizedName, 'rang muối') => ['rang cùng lớp gia vị mặn thơm bám đều bên ngoài', 'đậm đà, thơm và có độ khô ráo dễ ăn'],
            Str::contains($normalizedName, 'rang riềng') => ['rang nóng cùng riềng để tạo mùi thơm đặc trưng', 'thơm riềng, vị đậm và hợp dùng cùng đồ uống'],
            Str::contains($normalizedName, 'rang') => ['rang nóng để gia vị bám đều và dậy mùi', 'đậm vị, thơm và có độ khô vừa phải'],
            Str::contains($normalizedName, ['chiên', 'rán']) => ['chiên hoặc rán đến khi bề mặt vàng thơm', 'bên ngoài ráo giòn, bên trong giữ độ mềm phù hợp'],
            Str::contains($normalizedName, 'nướng') => ['nướng nóng để tạo mùi thơm và màu hấp dẫn', 'thơm mùi nướng, vị đậm và phù hợp làm món nhắm'],
            Str::contains($normalizedName, 'xào') => ['xào nhanh trên lửa lớn để món nóng và thấm gia vị', 'đậm vừa, thơm và giữ được kết cấu của nguyên liệu'],
            Str::contains($normalizedName, 'luộc') => ['luộc vừa chín để giữ vị tự nhiên và độ mềm', 'thanh, dễ ăn và phù hợp dùng cùng nước chấm'],
            Str::contains($normalizedName, 'hấp') => ['hấp nóng để giữ độ ẩm và hương vị tự nhiên', 'mềm, ngọt vị và không quá nhiều dầu'],
            Str::contains($normalizedName, 'nộm') => ['trộn theo kiểu nộm để tạo độ tươi và vị hài hòa', 'chua ngọt nhẹ, giòn và dễ dùng giữa bữa'],
            Str::contains($normalizedName, 'canh') => ['nấu nóng với phần nước canh cân bằng vị', 'dễ ăn, giúp bữa cơm hài hòa hơn'],
            Str::contains($normalizedName, 'cơm rang') => ['rang đều để hạt cơm tơi và thấm gia vị', 'thơm, vừa miệng và đủ chắc bụng'],
            Str::contains($normalizedName, 'cơm trắng') => ['nấu chín mềm và phục vụ nóng', 'vị thanh, phù hợp dùng cùng nhiều món mặn'],
            Str::contains($normalizedName, 'bún') => ['chuẩn bị nóng theo từng phần khi khách gọi', 'gọn vị, dễ ăn và phù hợp cho một phần ăn no'],
            Str::contains($normalizedName, 'xông hơi') => ['làm nóng theo kiểu xông hơi để giữ độ mềm và mùi thơm', 'mềm ẩm, thơm nhẹ và phù hợp dùng nóng'],
            default => ['chế biến khi khách gọi để món đạt trạng thái phục vụ phù hợp', 'hài hòa, dễ dùng và phù hợp với không khí dùng món tại quán'],
        };

        $timeLabel = match ($timeTag) {
            '10p' => 'Khoảng 10 phút',
            '15-20p' => 'Khoảng 15–20 phút',
            '30-40p' => 'Khoảng 30–40 phút',
            'co-ngay' => 'Có thể phục vụ ngay',
            default => 'Tùy tình trạng phục vụ tại thời điểm gọi món',
        };
        $categoryDescription = $categoryDescriptions[$categorySlug];
        $servingSuggestion = $servingSuggestions[$categorySlug];
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $short = $name.' được '.$preparation.'. Món có vị '.$flavour.'.';
        $description = '<p><strong>'.$safeName.'</strong> là '.$categoryDescription.'. Món được '.$preparation.' và phục vụ ngay sau khi hoàn thiện.</p>'
            .'<h3>Hương vị và cách thưởng thức</h3>'
            .'<p>Thành phẩm có hương vị '.$flavour.'. Món phù hợp để '.$servingSuggestion.'.</p>'
            .'<h3>Thông tin phục vụ</h3>'
            .'<ul><li><strong>Thời gian chuẩn bị:</strong> '.$timeLabel.'.</li>'
            .'<li><strong>Khẩu phần:</strong> Có thể gọi riêng hoặc dùng chung tùy nhu cầu của bàn.</li>'
            .'<li><strong>Lưu ý:</strong> Hình thức trình bày và nguyên liệu đi kèm có thể thay đổi theo tình trạng phục vụ thực tế.</li></ul>';

        return [$short, $description];
    }

    /** @return list<array{string, int, ?string, ?string, bool}> */
    private function items(): array
    {
        return [
            ['Chim câu băm', 150000, null, '15-20p', true],
            ['Chim câu xào', 130000, null, '15-20p', false],
            ['Cá trạch chiên lá lốt', 100000, null, '15-20p', true],
            ['Cá bống chiên giòn', 100000, null, '15-20p', false],
            ['Lươn xào sả ớt', 110000, null, '15-20p', false],
            ['Khấu đuôi chiên giòn', 90000, null, '15-20p', false],
            ['Khấu đuôi xào dưa', 90000, null, '15-20p', false],
            ['Thịt trâu xào tỏi', 110000, null, '10p', true],
            ['Thịt trâu xào lá lốt', 110000, null, '10p', false],
            ['Thịt trâu xào măng trúc', 120000, null, '10p', false],
            ['Thịt trâu xào rau muống', 110000, null, '10p', false],
            ['Thịt trâu xông hơi', 120000, null, '30-40p', false],
            ['Lòng trâu xào rau răm', 80000, null, '15-20p', false],
            ['Nem chua', 35000, null, 'co-ngay', true],
            ['Nem Bùi', 35000, null, 'co-ngay', false],
            ['Nem ngựa', 80000, null, null, false],
            ['Trứng cút lộn', 60000, null, '10p', false],
            ['Trứng cút luộc', 50000, null, '10p', false],
            ['Lạc luộc hoặc rang', 30000, null, 'co-ngay', false],
            ['Cá chỉ vàng nướng', 50000, null, '10p', false],
            ['Mực nướng', 130000, 'Theo con / 130.000 ₫', '10p', true],
            ['Ngô chiên', 40000, null, '15-20p', false],
            ['Khoai lệ phố', 50000, null, '15-20p', false],
            ['Đậu phụ lướt ván', 40000, null, '10p', false],
            ['Đậu phụ tẩm hành', 50000, null, '15-20p', true],
            ['Lòng xào dưa', 80000, null, '15-20p', false],
            ['Thịt bê xào sả ớt', 110000, null, '15-20p', false],
            ['Thịt bê xào lăn', 110000, null, '15-20p', false],
            ['Thịt bê xông hơi', 120000, null, '30-40p', false],
            ['Chân giò hun khói', 90000, null, '10p', false],
            ['Gan cháy tỏi', 70000, null, '15-20p', false],
            ['Ba chỉ rang', 80000, '80.000–100.000 ₫', '15-20p', true],
            ['Tràng lợn', 130000, null, '15-20p', false],
            ['Dạ dày lợn', 110000, null, '15-20p', false],
            ['Tim lợn xào thập cẩm', 80000, null, '15-20p', false],
            ['Vịt luộc', 110000, null, '30-40p', false],
            ['Vịt rang muối', 110000, null, '15-20p', true],
            ['Vịt rang riềng', 110000, null, '15-20p', false],
            ['Chân vịt rút xương', 80000, null, '15-20p', false],
            ['Nộm chân vịt', 80000, null, '15-20p', false],
            ['Chân vịt rang riềng', 0, 'Liên hệ', '15-20p', false],
            ['Gà luộc, rang muối, xào gừng hoặc chưng mắm', 160000, null, '30-40p', true],
            ['Thịt chó xào hoặc hấp', 110000, null, '15-20p', false],
            ['Dồi chó', 70000, null, '15-20p', false],
            ['Tôm chiên hoặc hấp', 0, 'Theo giá', '15-20p', true],
            ['Mực xào hoặc hấp', 0, 'Theo giá', '15-20p', true],
            ['Rau muống xào', 40000, null, '10p', false],
            ['Mùng tơi hoặc rau cải', 40000, null, '10p', false],
            ['Ngồng cải luộc chấm trứng', 50000, null, '10p', false],
            ['Rau bí xào', 0, 'Liên hệ', '10p', false],
            ['Cơm rang dưa bò', 35000, null, '15-20p', false],
            ['Cơm rang thập cẩm', 35000, null, '15-20p', false],
            ['Cơm trắng', 20000, '20.000–30.000–40.000 ₫', 'co-ngay', true],
            ['Cơm rang trứng', 30000, null, '10p', false],
            ['Cơm rang đùi gà', 45000, null, '15-20p', false],
            ['Cơm rang vịt quay', 40000, null, '15-20p', false],
            ['Canh chua thịt', 50000, null, '15-20p', false],
            ['Trứng rán', 50000, null, '15-20p', false],
            ['Nộm tai heo thập cẩm hoặc hoa chuối', 90000, null, '15-20p', false],
            ['Ếch rang muối', 120000, null, '15-20p', true],
            ['Ếch xào sả ớt hoặc măng cay', 120000, null, '15-20p', false],
            ['Châu chấu rang', 70000, null, '15-20p', false],
            ['Lẩu thập cẩm', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu gà', 400000, '400.000–500.000–600.000 ₫', '30-40p', true],
            ['Lẩu vịt', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu trâu', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu đuôi bò', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu chim câu', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu cá chép om dưa', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Lẩu ếch măng cay', 400000, '400.000–500.000–600.000 ₫', '30-40p', false],
            ['Bún bò', 35000, null, '10p', false],
            ['Bún vịt', 35000, null, '10p', false],
            ['Bún gà', 35000, null, '10p', false],
            ['Bún cá', 35000, null, '10p', false],
            ['Tóp mỡ xào dưa chua', 80000, null, '15-20p', false],
        ];
    }
}
