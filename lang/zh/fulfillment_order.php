<?php

return [
    'title' => '自取订单',
    'lead' => '审核自取订单并将已确认菜品发送到厨房。',
    'code' => '订单编号',
    'customer' => '顾客',
    'pickup_time' => '取餐时间',
    'status' => '状态',
    'empty' => '暂无自取订单。',
    'confirm' => '确认并发送厨房',
    'reject' => '拒绝订单',
    'rejection_reason' => '拒绝原因',
    'confirmed' => '订单已确认并发送厨房。',
    'rejected' => '订单已拒绝。',
    'sent_to_kitchen' => '菜品已显示在厨房队列中。',
    'pickup_badge' => '自取',
    'pickup_at' => ':time 取餐',
    'statuses' => ['pending' => '待确认', 'confirmed' => '已确认', 'rejected' => '已拒绝'],
    'errors' => ['not_pending' => '订单已不在待确认状态。'],
];
