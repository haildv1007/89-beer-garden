<?php

namespace Tests\Feature\POS;

use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DiningSessionConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_walk_in_and_reservation_check_in_cannot_open_the_same_table_concurrently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $walkInActor = $this->staff('CONCURRENT-WALK-IN');
        $checkInActor = $this->staff('CONCURRENT-CHECK-IN');
        $table = RestaurantTable::query()->forceCreate(['code' => 'CONCURRENT-TABLE', 'name' => 'Concurrent', 'capacity' => 8, 'runtime_status' => RestaurantTableStatus::Available, 'is_active' => true]);
        $customer = Customer::query()->forceCreate(['name' => 'Concurrent Customer']);
        $reservation = Reservation::query()->forceCreate(['customer_id' => $customer->id, 'reservation_code' => 'RSV-CONCURRENT', 'reservation_date' => now()->toDateString(), 'reservation_time' => '18:00', 'party_size' => 4, 'status' => ReservationStatus::Confirmed]);
        $startAt = microtime(true) + 1.5;

        $walkIn = $this->process('walk-in', $walkInActor, $table, $reservation, $startAt);
        $checkIn = $this->process('check-in', $checkInActor, $table, $reservation, $startAt);
        $walkIn->start();
        $checkIn->start();
        $walkIn->wait();
        $checkIn->wait();

        $exitCodes = [$walkIn->getExitCode(), $checkIn->getExitCode()];
        sort($exitCodes);
        $this->assertSame([0, 2], $exitCodes, $walkIn->getErrorOutput().$checkIn->getErrorOutput());
        $this->assertDatabaseCount('dining_sessions', 1);
        $this->assertSame(RestaurantTableStatus::Occupied, $table->fresh()->runtime_status);
    }

    private function staff(string $code): User
    {
        $user = User::factory()->forRole(Role::where('code', 'staff')->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => $code, 'name' => $code, 'status' => EmployeeStatus::Active]);

        return $user;
    }

    private function process(string $flow, User $actor, RestaurantTable $table, Reservation $reservation, float $startAt): Process
    {
        $script = <<<'PHP'
            chdir($argv[5]);
            require $argv[5].'/vendor/autoload.php';
            $app = require $argv[5].'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            while (microtime(true) < (float) $argv[6]) { usleep(1000); }
            try {
                $actor = App\Models\User::findOrFail((int) $argv[2]);
                $table = App\Models\RestaurantTable::findOrFail((int) $argv[3]);
                if ($argv[1] === 'walk-in') {
                    app(App\Services\DiningSession\OpenDiningSessionService::class)->open($table, $actor, 2, null, null);
                } else {
                    $reservation = App\Models\Reservation::findOrFail((int) $argv[4]);
                    app(App\Services\DiningSession\CheckInReservationService::class)->checkIn($reservation, $table, $actor);
                }
                exit(0);
            } catch (Illuminate\Validation\ValidationException) {
                exit(2);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getMessage());
                exit(3);
            }
            PHP;

        $connection = config('database.connections.mysql');
        $process = new Process([PHP_BINARY, '-r', $script, $flow, (string) $actor->id, (string) $table->id, (string) $reservation->id, base_path(), (string) $startAt], base_path(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'], 'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'], 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
        ]);
        $process->setTimeout(20);

        return $process;
    }
}
