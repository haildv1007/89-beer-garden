<?php

return [
    'validation' => [
        'unknown_field' => '请求中包含不支持的字段。',
    ],
    'summary' => [
        'rule_based' => '此套餐根据您的偏好、用餐人数和现有数据进行均衡推荐。',
        'popular' => '推荐目前可供应的热门菜品。',
        'over_budget' => '这是最接近需求的套餐，但预计总价超出预算。',
        'empty' => '目前没有合适的推荐套餐，您仍可从菜单中选择。',
    ],
    'reasons' => [
        'preference' => '符合您选择的偏好。',
        'history' => '符合您过去的选择。',
        'cart_pairing' => '适合与购物车中的菜品搭配。',
        'popular' => '近期受到许多顾客欢迎。',
        'time' => '适合当前时段。',
        'balanced' => '让推荐套餐更加均衡。',
    ],
    'cart' => [
        'added' => '已将整套推荐菜品加入购物车。',
        'set_invalid' => '此推荐套餐已失效，请重新生成推荐。',
        'product_unavailable' => '一个或多个菜品已停止供应，请重新生成推荐或单独添加仍可供应的菜品。',
        'quantity_exceeded' => '加入后的购物车数量超过允许上限。',
    ],
];
