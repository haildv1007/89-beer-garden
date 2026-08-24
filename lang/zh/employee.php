<?php

return [
    'employees' => [
        'title' => '员工', 'create' => '新建员工', 'edit' => '编辑员工',
        'empty' => '暂无员工。', 'disabled_success' => '员工及关联账户已停用。',
        'disable' => '停用', 'disable_confirm' => '确定停用此员工及关联账户吗？',
        'admin_disable_forbidden' => 'Manager 不能停用 Admin 账户。',
        'last_admin_protected' => '无法停用最后一个有效管理员。',
    ],
    'accounts' => [
        'title' => '关联账户', 'none' => '此员工尚无关联账户。',
        'create' => '创建账户', 'created' => '关联账户已创建。',
        'already_linked' => '此员工已有账户。', 'employee_disabled' => '无法为已停用员工创建账户。', 'invalid_role' => '此角色不适用于员工账户。', 'role_updated' => '角色已更新。',
    ],
    'roles' => [
        'title' => '角色与权限', 'current' => '当前角色', 'update' => '更新角色',
        'permissions_updated' => '权限矩阵已更新。', 'matrix_help' => '只能授予已批准目录中的权限。',
        'admin_mandatory_permissions' => 'Admin 角色必须保留 context.admin.access 和 permission.assign。',
    ],
    'permissions' => ['title' => '权限'],
    'fields' => [
        'employee_code' => '员工编号', 'name' => '姓名', 'phone' => '电话',
        'position' => '职位', 'status' => '状态', 'email' => '邮箱',
        'password' => '密码', 'password_confirmation' => '确认密码', 'role' => '角色',
    ],
    'statuses' => ['active' => '启用', 'disabled' => '已停用'],
    'filters' => ['all_statuses' => '全部状态', 'all_roles' => '全部角色'],
    'bootstrap_admin' => [
        'employee_code' => '员工编号', 'employee_name' => '员工姓名', 'email' => '管理员邮箱',
        'password' => '密码', 'password_confirmation' => '确认密码',
        'role_missing' => '缺少 admin 角色，请先运行 seeder。',
        'already_exists' => '初始管理员账户已存在。', 'created' => '初始管理员已安全创建。', 'failed' => '无法创建管理员；未保存任何数据。',
    ],
];
