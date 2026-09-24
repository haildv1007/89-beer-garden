<?php

return [
    'create' => '点餐',
    'additional' => '追加点餐',
    'submit' => '创建点餐',
    'created' => '点餐已创建。',
    'item_updated' => '等待中的菜品已更新。',
    'history' => '点餐记录',
    'empty' => '暂无点餐记录。',
    'empty_products' => '当前没有可点的菜品。',
    'search_products' => '搜索菜品或分类',
    'selected_products' => '道已选择',
    'subtotal' => '显示小计',
    'fields' => [
        'product' => '菜品',
        'price' => '历史单价',
        'quantity' => '数量',
        'line_total' => '金额',
        'status' => '状态',
        'order_note' => '点餐备注',
        'item_note' => '菜品备注',
    ],
    'sources' => ['staff' => 'POS', 'customer' => '顾客'],
    'statuses' => [
        'waiting' => '等待中',
        'preparing' => '制作中',
        'ready' => '已备好',
        'served' => '已上菜',
        'cancelled' => '已取消',
    ],
    'errors' => [
        'session_invalid' => '用餐会话或餐桌状态已不允许点餐。',
        'product_invalid' => '一个或多个菜品已停用、不可用、已删除或所属分类未启用。',
        'waiting_required' => '只有活动会话中等待状态的菜品可以修改。',
        'quantity_overflow' => '数量导致金额超出存储限制。',
    ],
];
