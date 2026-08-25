<?php

return [
    'title' => '用餐会话', 'active_title' => '活动中的用餐会话',
    'search' => '搜索会话编号、餐桌编号或顾客', 'empty' => '当前没有活动中的用餐会话。',
    'anonymous' => '匿名到店顾客', 'open_walk_in' => '开启到店用餐会话', 'open' => '开启用餐会话',
    'opened' => '用餐会话已开启。', 'checked_in' => '预订已签到并开启用餐会话。',
    'check_in' => '签到', 'select_table' => '选择合适的餐桌', 'view_session' => '查看会话 :code',
    'completion_later' => '会话完成、付款及餐桌转为清洁中状态将在账单/支付流程中实现。',
    'fields' => ['code' => '会话编号', 'table' => '餐桌', 'customer' => '顾客', 'reservation' => '预订', 'guests' => '客人数', 'opened_by' => '开启人', 'started_at' => '开始时间', 'status' => '状态', 'note' => '备注'],
    'statuses' => ['active' => '活动中', 'completed' => '已完成'],
    'errors' => ['table_unavailable' => '餐桌未启用、不可用、容量不足或已有活动会话。', 'confirmed_required' => '仅已确认且尚无用餐会话的预订可以签到。', 'no_suitable_table' => '没有启用、可用且容量足够的餐桌。'],
];
