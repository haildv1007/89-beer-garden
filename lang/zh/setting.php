<?php

return [
    'title' => '系统配置',
    'intro' => '这里只能修改已批准的运行时设置。密钥和环境配置不通过此界面管理。',
    'valid' => '有效',
    'invalid' => '无效',
    'type' => '类型',
    'current_value' => '当前值',
    'updated_by' => '更新人',
    'updated_at' => '更新时间',
    'new_value' => '新值',
    'missing' => '尚未配置',
    'enabled' => '启用',
    'disabled' => '停用',
    'save' => '保存设置',
    'saved' => '设置已保存。',
    'types' => ['integer' => '整数', 'boolean' => '布尔值', 'string' => '文本'],
    'items' => [
        'contact_address' => [
            'label' => '门店地址',
            'description' => '用于联系页面、页脚和客户支持的完整地址。',
        ],
        'contact_phone' => [
            'label' => '服务热线',
            'description' => '餐厅对外公开的主要联系电话。',
        ],
        'contact_email' => [
            'label' => '联系邮箱',
            'description' => '用于客户支持和咨询的公开邮箱。',
        ],
        'contact_facebook_url' => [
            'label' => 'Facebook / Messenger',
            'description' => '',
        ],
        'contact_zalo_url' => [
            'label' => 'Zalo',
            'description' => '',
        ],
        'opening_hours' => [
            'label' => '营业时间',
            'description' => '公开营业时间，可按日期或时段分多行填写。',
        ],
        'map_url' => [
            'label' => '地图链接',
            'description' => '用于导航的 Google Maps 或其他地图服务 HTTPS 链接。',
        ],
        'no_show_timeout_minutes' => [
            'label' => '未到店超时',
            'description' => '确认预订在预订时间后可标记为未到店的分钟数（1–1440分钟）。',
        ],
        'delivery_fee' => [
            'label' => '配送费',
            'description' => '默认配送费，单位为越南盾（0–10,000,000）。已创建订单保留下单时的费用。',
        ],
        'vietqr_bank_id' => ['label' => 'VietQR银行', 'description' => '银行代码，例如MB、VCB或BIDV。'],
        'vietqr_account_number' => ['label' => '收款账号', 'description' => '餐厅银行账号。'],
        'vietqr_account_name' => ['label' => '账户名称', 'description' => '二维码显示的账户名称。'],
        'vietqr_transfer_prefix' => ['label' => '转账前缀', 'description' => '与订单代码组合的短前缀。'],
    ],
    'validation' => ['invalid' => '设置值无效或该键不允许修改。', 'actor_inactive' => '账号或员工档案已停用。'],
];
