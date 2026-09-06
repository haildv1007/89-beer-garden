<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class DevelopmentDemoSeeder extends Seeder
{
    private const INTERNAL_ACCOUNTS = [
        [
            'role' => 'admin',
            'email' => 'admin.demo@89beergarden.test',
            'employee_code' => 'DEMO-ADMIN',
            'name' => 'Nguyễn Minh Quân',
            'phone' => '0908900101',
            'position' => 'Quản trị hệ thống',
        ],
        [
            'role' => 'manager',
            'email' => 'manager.demo@89beergarden.test',
            'employee_code' => 'DEMO-MANAGER',
            'name' => 'Trần Thu Hà',
            'phone' => '0908900102',
            'position' => 'Quản lý nhà hàng',
        ],
        [
            'role' => 'staff',
            'email' => 'staff.demo@89beergarden.test',
            'employee_code' => 'DEMO-STAFF',
            'name' => 'Lê Quốc Bảo',
            'phone' => '0908900103',
            'position' => 'Nhân viên phục vụ',
        ],
        [
            'role' => 'kitchen',
            'email' => 'kitchen.demo@89beergarden.test',
            'employee_code' => 'DEMO-KITCHEN',
            'name' => 'Phạm Ngọc Anh',
            'phone' => '0908900104',
            'position' => 'Nhân viên bếp',
        ],
    ];

    private const CUSTOMER_ACCOUNTS = [
        [
            'email' => 'customer.lan.demo@89beergarden.test',
            'name' => 'Nguyễn Hoàng Lan',
            'phone' => '0908900201',
            'note' => 'Khách quen, ưu tiên bàn ngoài trời khi còn chỗ.',
        ],
        [
            'email' => 'customer.huy.demo@89beergarden.test',
            'name' => 'Trần Gia Huy',
            'phone' => '0908900202',
            'note' => null,
        ],
        [
            'email' => 'customer.mai.demo@89beergarden.test',
            'name' => 'Lê Thanh Mai',
            'phone' => '0908900203',
            'note' => 'Dị ứng đậu phộng; ghi chú chỉ dùng nội bộ.',
        ],
    ];

    public function run(): void
    {
        $this->ensureSafeEnvironment();
        $password = $this->validatedPassword();
        $roles = $this->canonicalRoles();

        DB::transaction(function () use ($password, $roles): void {
            $categories = $this->seedCategories();
            $this->seedProducts($categories);
            $this->seedRestaurantTables();
            $this->seedGuestCustomers();
            $this->seedCustomerAccounts($roles['customer'], $password);
            $this->seedInternalAccounts($roles, $password);
        });

        $this->call(NewsDemoSeeder::class);
    }

    private function ensureSafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DevelopmentDemoSeeder may only run in the local or testing environment.');
        }
    }

    private function validatedPassword(): string
    {
        $password = env('DEMO_USER_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException(
                'DEMO_USER_PASSWORD is not configured. Set it before running DevelopmentDemoSeeder.',
            );
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]],
        );

        if ($validator->fails()) {
            throw new RuntimeException('DEMO_USER_PASSWORD does not satisfy the current password policy.');
        }

        return $password;
    }

    /** @return array<string, Role> */
    private function canonicalRoles(): array
    {
        $requiredCodes = Role::CANONICAL_CODES;
        $roles = Role::query()->whereIn('code', $requiredCodes)->get()->keyBy('code');
        $missingCodes = array_values(array_diff($requiredCodes, $roles->keys()->all()));

        if ($missingCodes !== []) {
            throw new RuntimeException(
                'Canonical roles are missing: '.
                    implode(', ', $missingCodes).
                    '. Run the approved DatabaseSeeder first.',
            );
        }

        return $roles->all();
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $definitions = [
            [
                'name' => 'Bia & Đồ uống có cồn',
                'slug' => 'demo-bia-do-uong-co-con',
                'description' => 'Bia chai, bia lon, bia tươi và đồ uống có cồn.',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 10,
            ],
            [
                'name' => 'Khai vị',
                'slug' => 'demo-khai-vi',
                'description' => 'Các món ăn nhẹ để mở đầu bàn tiệc.',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 20,
            ],
            [
                'name' => 'Món nướng',
                'slug' => 'demo-mon-nuong',
                'description' => 'Món nướng đậm vị, phù hợp dùng cùng bia lạnh.',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 30,
            ],
            [
                'name' => 'Hải sản',
                'slug' => 'demo-hai-san',
                'description' => 'Hải sản chế biến theo phong cách Beer Garden.',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 40,
            ],
            [
                'name' => 'Lẩu',
                'slug' => 'demo-lau',
                'description' => 'Các món lẩu nóng dành cho nhóm bạn và gia đình.',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 50,
            ],
            [
                'name' => 'Nước giải khát',
                'slug' => 'demo-nuoc-giai-khat',
                'description' => 'Nước suối, nước ngọt và thức uống không cồn.',
                'status' => Category::STATUS_INACTIVE,
                'sort_order' => 60,
            ],
        ];

        $categories = [];
        foreach ($definitions as $definition) {
            $category = Category::withTrashed()->updateOrCreate(['slug' => $definition['slug']], $definition);
            if ($category->trashed()) {
                $category->restore();
            }
            $categories[$definition['slug']] = $category;
        }

        return $categories;
    }

    /** @param array<string, Category> $categories */
    private function seedProducts(array $categories): void
    {
        $products = [
            'demo-bia-do-uong-co-con' => [
                [
                    'Bia Sài Gòn Lager',
                    'demo-bia-sai-gon-lager',
                    'Bia lager vị cân bằng, dùng ngon nhất khi ướp lạnh.',
                    25000,
                ],
                [
                    'Bia Sài Gòn Special',
                    'demo-bia-sai-gon-special',
                    'Bia chai hương malt dịu và hậu vị sảng khoái.',
                    32000,
                ],
                ['Bia Heineken', 'demo-bia-heineken', 'Bia lager cao cấp, hương vị thanh nhẹ.', 42000],
                ['Bia Tiger Crystal', 'demo-bia-tiger-crystal', 'Bia lager êm dịu, chai lạnh sâu.', 38000],
                ['Bia tươi 89 - Ly 500ml', 'demo-bia-tuoi-89-500ml', 'Bia tươi rót tại quầy, dung tích 500ml.', 35000],
                [
                    'Tháp bia tươi 3 lít',
                    'demo-thap-bia-tuoi-3-lit',
                    'Tháp bia tươi dành cho nhóm từ bốn người.',
                    189000,
                ],
            ],
            'demo-khai-vi' => [
                ['Khoai tây chiên', 'demo-khoai-tay-chien', 'Khoai tây chiên vàng giòn, dùng kèm tương cà.', 49000],
                ['Đậu hũ chiên sả', 'demo-dau-hu-chien-sa', 'Đậu hũ giòn bên ngoài, thơm vị sả.', 55000],
                ['Nem chua rán', 'demo-nem-chua-ran', 'Nem chua rán giòn, ăn kèm rau và tương ớt.', 69000],
                ['Cánh gà chiên nước mắm', 'demo-canh-ga-chien-nuoc-mam', 'Cánh gà chiên phủ sốt nước mắm tỏi.', 99000],
                ['Chả giò hải sản', 'demo-cha-gio-hai-san', 'Chả giò nhân hải sản, lớp vỏ vàng giòn.', 89000],
                ['Gỏi xoài khô cá lóc', 'demo-goi-xoai-kho-ca-loc', 'Xoài xanh trộn khô cá lóc vị chua cay.', 109000],
            ],
            'demo-mon-nuong' => [
                [
                    'Ba chỉ heo nướng riềng mẻ',
                    'demo-ba-chi-nuong-rieng-me',
                    'Ba chỉ mềm béo ướp riềng mẻ thơm đậm.',
                    139000,
                ],
                ['Bò nướng lá lốt', 'demo-bo-nuong-la-lot', 'Thịt bò cuộn lá lốt nướng thơm.', 149000],
                ['Bò cuộn nấm kim châm', 'demo-bo-cuon-nam-kim-cham', 'Bò thái mỏng cuộn nấm, nướng sốt tiêu.', 169000],
                [
                    'Sườn non nướng mật ong',
                    'demo-suon-non-nuong-mat-ong',
                    'Sườn non nướng mềm với lớp sốt mật ong.',
                    179000,
                ],
                ['Gà nướng muối ớt', 'demo-ga-nuong-muoi-ot', 'Nửa con gà nướng da giòn, vị cay nhẹ.', 219000],
                ['Dồi sụn nướng', 'demo-doi-sun-nuong', 'Dồi sụn nướng thơm, dùng kèm rau răm.', 119000],
            ],
            'demo-hai-san' => [
                ['Mực nướng sa tế', 'demo-muc-nuong-sa-te', 'Mực tươi nướng sa tế cay thơm.', 179000],
                [
                    'Bạch tuộc nướng muối ớt',
                    'demo-bach-tuoc-nuong-muoi-ot',
                    'Bạch tuộc nướng săn giòn với muối ớt xanh.',
                    199000,
                ],
                ['Tôm sú nướng mọi', 'demo-tom-su-nuong-moi', 'Tôm sú nướng nguyên vị, ngọt thịt.', 229000],
                ['Hàu nướng mỡ hành', 'demo-hau-nuong-mo-hanh', 'Hàu nướng phủ mỡ hành và đậu phộng.', 129000],
                ['Nghêu hấp sả', 'demo-ngheu-hap-sa', 'Nghêu hấp nóng với sả và lá chanh.', 99000],
                [
                    'Cá kèo nướng muối ớt',
                    'demo-ca-keo-nuong-muoi-ot',
                    'Cá kèo nướng vừa lửa, dùng kèm rau răm.',
                    159000,
                ],
            ],
            'demo-lau' => [
                ['Lẩu Thái hải sản', 'demo-lau-thai-hai-san', 'Lẩu chua cay với tôm, mực, nghêu và rau.', 329000],
                ['Lẩu bò nhúng giấm', 'demo-lau-bo-nhung-giam', 'Bò mềm nhúng nước lẩu giấm thanh dịu.', 349000],
                [
                    'Lẩu cá kèo lá giang',
                    'demo-lau-ca-keo-la-giang',
                    'Cá kèo tươi cùng nước lẩu lá giang chua nhẹ.',
                    319000,
                ],
                ['Lẩu gà ớt hiểm', 'demo-lau-ga-ot-hiem', 'Lẩu gà vị cay ấm, thơm sả và ớt hiểm.', 299000],
                ['Lẩu riêu cua bắp bò', 'demo-lau-rieu-cua-bap-bo', 'Riêu cua đậm đà ăn cùng bắp bò và rau.', 369000],
                ['Lẩu nấm rau củ', 'demo-lau-nam-rau-cu', 'Nước lẩu thanh ngọt với nhiều loại nấm.', 259000],
            ],
            'demo-nuoc-giai-khat' => [
                ['Nước suối', 'demo-nuoc-suoi', 'Nước suối đóng chai 500ml.', 15000],
                ['Coca-Cola', 'demo-coca-cola', 'Nước ngọt có ga dùng lạnh.', 25000],
                ['Sprite', 'demo-sprite', 'Nước ngọt vị chanh dùng lạnh.', 25000],
                ['Trà tắc', 'demo-tra-tac', 'Trà tắc pha tại quầy, vị chua ngọt.', 29000],
                ['Nước cam ép', 'demo-nuoc-cam-ep', 'Nước cam tươi ép nguyên chất.', 45000],
                ['Soda chanh', 'demo-soda-chanh', 'Soda chanh mát lạnh, vị thanh.', 39000],
            ],
        ];

        $unavailable = ['demo-bia-tuoi-89-500ml', 'demo-canh-ga-chien-nuoc-mam', 'demo-bach-tuoc-nuong-muoi-ot'];
        $inactive = ['demo-doi-sun-nuong', 'demo-lau-nam-rau-cu'];

        foreach ($products as $categorySlug => $definitions) {
            foreach ($definitions as [$name, $slug, $description, $price]) {
                $product = Product::withTrashed()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $categories[$categorySlug]->id,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'image_url' => null,
                        'status' => in_array($slug, $inactive, true)
                            ? Product::STATUS_INACTIVE
                            : Product::STATUS_ACTIVE,
                        'is_available' => ! in_array($slug, $unavailable, true),
                    ],
                );
                if ($product->trashed()) {
                    $product->restore();
                }
            }
        }
    }

    private function seedRestaurantTables(): void
    {
        $locations = ['Trong nhà', 'Ngoài trời', 'Tầng trệt', 'Khu VIP'];
        $capacities = [2, 4, 4, 6, 8, 10, 2, 4, 6, 8, 4, 6, 8, 10, 2, 4, 6, 8, 10, 4];

        foreach ($capacities as $index => $capacity) {
            $number = $index + 1;
            $code = sprintf('DEMO-T%02d', $number);
            $table = RestaurantTable::withTrashed()->where('code', $code)->first();
            $attributes = [
                'code' => $code,
                'name' => sprintf('Bàn %02d', $number),
                'capacity' => $capacity,
                'location' => $locations[$index % count($locations)],
                'runtime_status' => in_array($number, [6, 12, 18], true)
                    ? RestaurantTableStatus::Cleaning
                    : RestaurantTableStatus::Available,
                'is_active' => $number !== 20,
            ];

            if ($table === null) {
                $table = RestaurantTable::query()->forceCreate($attributes);
            } else {
                $table
                    ->forceFill([
                        'name' => sprintf('Bàn %02d', $number),
                        'capacity' => $capacity,
                        'location' => $locations[$index % count($locations)],
                        'runtime_status' => $attributes['runtime_status'],
                        'is_active' => $attributes['is_active'],
                    ])
                    ->save();
            }
            if ($table->trashed()) {
                $table->restore();
            }
        }
    }

    private function seedGuestCustomers(): void
    {
        $customers = [
            ['Phạm Minh Tuấn', '0908900301', 'guest.tuan.demo@89beergarden.test', 'Khách thường đi nhóm sáu người.'],
            ['Võ Thảo Vy', '0908900302', 'guest.vy.demo@89beergarden.test', null],
            ['Đặng Quốc Khánh', '0908900303', 'guest.khanh.demo@89beergarden.test', 'Ưu tiên khu vực ít khói.'],
            ['Bùi Ngọc Trâm', '0908900304', 'guest.tram.demo@89beergarden.test', null],
            ['Hồ Anh Dũng', '0908900305', 'guest.dung.demo@89beergarden.test', 'Khách walk-in thường ghé cuối tuần.'],
            ['Đỗ Mỹ Linh', '0908900306', 'guest.linh.demo@89beergarden.test', null],
            ['Ngô Thành Đạt', '0908900307', 'guest.dat.demo@89beergarden.test', null],
            [
                'Dương Khánh Ngân',
                '0908900308',
                'guest.ngan.demo@89beergarden.test',
                'Liên hệ qua điện thoại trước khi xếp bàn.',
            ],
            ['Lý Hoàng Nam', '0908900309', 'guest.nam.demo@89beergarden.test', null],
        ];

        foreach ($customers as [$name, $phone, $email, $note]) {
            $customer = Customer::withTrashed()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'phone' => $phone, 'note' => $note],
            );
            $customer->forceFill(['user_id' => null])->save();
            if ($customer->trashed()) {
                $customer->restore();
            }
        }
    }

    private function seedCustomerAccounts(Role $role, string $password): void
    {
        foreach (self::CUSTOMER_ACCOUNTS as $definition) {
            $user = $this->demoUser($definition['email'], $role, $password);

            if ($user->employee()->withTrashed()->exists()) {
                throw new RuntimeException(
                    "Demo customer account {$definition['email']} is already linked to an employee.",
                );
            }

            $customer = Customer::withTrashed()->where('email', $definition['email'])->first();
            if ($customer !== null && $customer->user_id !== null && $customer->user_id !== $user->id) {
                throw new RuntimeException("Demo customer profile {$definition['email']} is linked to another user.");
            }

            $customer ??= new Customer(['email' => $definition['email']]);
            $customer
                ->forceFill([
                    'user_id' => $user->id,
                    'name' => $definition['name'],
                    'phone' => $definition['phone'],
                    'email' => $definition['email'],
                    'note' => $definition['note'],
                ])
                ->save();
            if ($customer->trashed()) {
                $customer->restore();
            }
        }
    }

    /** @param array<string, Role> $roles */
    private function seedInternalAccounts(array $roles, string $password): void
    {
        foreach (self::INTERNAL_ACCOUNTS as $definition) {
            $role = $roles[$definition['role']];
            $user = $this->demoUser($definition['email'], $role, $password);

            if ($user->customer()->withTrashed()->exists()) {
                throw new RuntimeException(
                    "Demo employee account {$definition['email']} is already linked to a customer.",
                );
            }

            $employeeByUser = Employee::withTrashed()->where('user_id', $user->id)->first();
            $employeeByCode = Employee::withTrashed()->where('employee_code', $definition['employee_code'])->first();

            if ($employeeByUser !== null && $employeeByUser->employee_code !== $definition['employee_code']) {
                throw new RuntimeException(
                    "Demo employee account {$definition['email']} is linked to another employee.",
                );
            }
            if (
                $employeeByCode !== null &&
                $employeeByCode->user_id !== null &&
                $employeeByCode->user_id !== $user->id
            ) {
                throw new RuntimeException(
                    "Demo employee code {$definition['employee_code']} is linked to another user.",
                );
            }

            $employee = $employeeByUser ?? ($employeeByCode ?? new Employee);
            $employee
                ->forceFill([
                    'user_id' => $user->id,
                    'employee_code' => $definition['employee_code'],
                    'name' => $definition['name'],
                    'phone' => $definition['phone'],
                    'position' => $definition['position'],
                    'status' => EmployeeStatus::Active,
                ])
                ->save();
            if ($employee->trashed()) {
                $employee->restore();
            }
        }
    }

    private function demoUser(string $email, Role $role, string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return User::query()->forceCreate([
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'status' => User::STATUS_ACTIVE,
            ]);
        }

        $user->forceFill(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE])->save();

        return $user;
    }
}
