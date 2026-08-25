<?php

return [
    'title' => '后厨队列', 'refresh' => '刷新', 'empty' => '此区域暂无菜品。',
    'quantity' => '数量：:count', 'actions' => '操作',
    'start_preparing' => '开始制作', 'mark_ready' => '标记为已备好',
    'mark_served' => '标记为已上菜', 'cancel_item' => '取消菜品', 'cancellation_reason' => '取消原因',
    'started' => '菜品已开始制作。', 'ready' => '菜品已备好。',
    'served' => '菜品已标记为上菜。', 'cancelled' => '菜品已取消。',
    'sections' => ['waiting' => '等待中', 'preparing' => '制作中', 'ready' => '待上菜'],
    'errors' => ['transition_invalid' => '菜品或用餐会话状态已不允许此操作。', 'cancellation_invalid' => '菜品已不可取消或用餐会话已结束。'],
];
