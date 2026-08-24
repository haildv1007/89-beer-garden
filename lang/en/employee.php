<?php

return [
    'employees' => [
        'title' => 'Employees', 'create' => 'Create employee', 'edit' => 'Edit employee',
        'empty' => 'No employees yet.', 'disabled_success' => 'Employee and linked account disabled.',
        'disable' => 'Disable', 'disable_confirm' => 'Disable this employee and linked account?',
        'admin_disable_forbidden' => 'Managers cannot disable an Admin account.',
        'last_admin_protected' => 'The last active Admin cannot be disabled.',
    ],
    'accounts' => [
        'title' => 'Linked account', 'none' => 'This employee has no linked account.',
        'create' => 'Create account', 'created' => 'Linked account created.',
        'already_linked' => 'The employee already has a linked account.', 'employee_disabled' => 'An account cannot be created for a disabled employee.', 'invalid_role' => 'This role is not valid for an employee account.', 'role_updated' => 'Role updated.',
    ],
    'roles' => [
        'title' => 'Roles and permissions', 'current' => 'Current role', 'update' => 'Update role',
        'permissions_updated' => 'Permission matrix updated.', 'matrix_help' => 'Only permissions from the approved catalog can be granted.',
        'admin_mandatory_permissions' => 'The Admin role must retain context.admin.access and permission.assign.',
    ],
    'permissions' => ['title' => 'Permissions'],
    'fields' => [
        'employee_code' => 'Employee code', 'name' => 'Name', 'phone' => 'Phone',
        'position' => 'Position', 'status' => 'Status', 'email' => 'Email',
        'password' => 'Password', 'password_confirmation' => 'Confirm password', 'role' => 'Role',
    ],
    'statuses' => ['active' => 'Active', 'disabled' => 'Disabled'],
    'filters' => ['all_statuses' => 'All statuses', 'all_roles' => 'All roles'],
    'bootstrap_admin' => [
        'employee_code' => 'Employee code', 'employee_name' => 'Employee name', 'email' => 'Admin email',
        'password' => 'Password', 'password_confirmation' => 'Confirm password',
        'role_missing' => 'The admin role is missing. Run the seeder first.',
        'already_exists' => 'The initial Admin account already exists.', 'created' => 'Initial Admin created securely.', 'failed' => 'The Admin could not be created; no data was saved.',
    ],
];
