<?php

return [
    'logout' => '退出登录',
    'cancel' => '取消',
    'account_statuses' => ['active' => '启用', 'disabled' => '已停用'],
    'fields' => [
        'name' => '姓名', 'email' => '电子邮箱', 'phone' => '电话号码',
        'password' => '密码', 'password_confirmation' => '确认密码',
        'created_at' => '创建时间', 'updated_at' => '最近更新',
    ],
    'registration' => [
        'title' => '注册顾客账户', 'action' => '注册', 'submit' => '创建账户',
        'login_prompt' => '还没有账户？立即注册',
        'password_help' => '至少 12 个字符，并包含大小写字母、数字和符号。',
        'success' => '顾客账户已成功创建。',
    ],
    'profile' => [
        'title' => '我的资料', 'edit' => '编辑资料', 'updated' => '资料已更新。',
        'identity_explanation' => '您的顾客资料已安全关联到登录账户。',
    ],
    'history' => [
        'title' => '历史摘要', 'reservations' => '预订', 'sessions' => '用餐会话', 'orders' => '订单',
        'details_later' => '详细历史将在预订、用餐会话和订单模块中提供。',
    ],
    'admin' => [
        'title' => '顾客', 'search_placeholder' => '按姓名、电话或邮箱搜索',
        'empty' => '未找到匹配的顾客。', 'account' => '账户',
        'linked_account' => '已关联账户', 'guest_profile' => '访客/到店顾客',
        'account_email' => '登录邮箱', 'account_status' => '账户状态',
        'history_summary' => ':reservations 条预订 · :sessions 个用餐会话 · :orders 个订单',
        'back' => '返回顾客列表',
    ],
];
