<?php

return [
    'title' => '优惠券',
    'create' => '创建优惠券',
    'search' => '按代码或名称搜索',
    'all_statuses' => '全部状态',
    'empty' => '暂无优惠券。',
    'historical_locked' => '此优惠券已关联账单，历史数据已锁定。',
    'fields' => [
        'code' => '代码',
        'name' => '名称',
        'type' => '优惠类型',
        'value' => '优惠值',
        'max_discount' => '最高优惠',
        'minimum' => '最低订单金额',
        'start' => '开始时间',
        'end' => '结束时间',
        'usage_limit' => '使用上限',
        'used' => '已使用',
        'status' => '状态',
    ],
    'types' => ['fixed' => '固定金额', 'percentage' => '百分比（0–100）'],
    'statuses' => ['active' => '启用', 'inactive' => '停用'],
    'errors' => ['historical' => '已关联账单的优惠券不能编辑或删除。'],
];
