<?php

namespace App\Console\Commands;

use App\Services\Access\CreateInitialAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateInitialAdmin extends Command
{
    protected $signature = 'app:create-initial-admin';

    protected $description = 'Securely create the first administrator account and employee profile';

    public function handle(CreateInitialAdminService $service): int
    {
        $data = [
            'employee_code' => trim((string) $this->ask(__('employee.bootstrap_admin.employee_code'))),
            'employee_name' => trim((string) $this->ask(__('employee.bootstrap_admin.employee_name'))),
            'email' => mb_strtolower(trim((string) $this->ask(__('employee.bootstrap_admin.email')))),
            'password' => (string) $this->secret(__('employee.bootstrap_admin.password')),
            'password_confirmation' => (string) $this->secret(__('employee.bootstrap_admin.password_confirmation')),
        ];

        $validator = Validator::make($data, [
            'employee_code' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:employees,employee_code'],
            'employee_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $service->create($data['employee_code'], $data['employee_name'], $data['email'], $data['password']);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable) {
            $this->error(__('employee.bootstrap_admin.failed'));

            return self::FAILURE;
        }

        $this->info(__('employee.bootstrap_admin.created'));

        return self::SUCCESS;
    }
}
