<?php

return [
    'title' => '库存', 'create' => '创建库存项目', 'edit' => '编辑库存项目',
    'search' => '搜索 SKU、名称或产品', 'all_statuses' => '全部状态', 'low_stock_only' => '仅低库存',
    'low_stock' => '低库存', 'low_stock_warning' => '当前库存小于或等于最低库存。',
    'empty' => '暂无库存项目。', 'no_product' => '不关联产品',
    'stock_via_movement' => '库存只能通过可审计的库存变动进行修改。',
    'create_movement' => '创建库存变动', 'movement_created' => '库存变动已记录。',
    'history' => '不可变库存变动历史', 'no_movements' => '暂无库存变动。',
    'fields' => ['sku' => 'SKU', 'name' => '名称', 'unit' => '单位', 'minimum_stock' => '最低库存',
        'current_stock' => '当前库存', 'status' => '状态', 'product' => '关联产品',
        'time' => '时间', 'type' => '类型', 'quantity' => '数量', 'before' => '变动前',
        'after' => '变动后', 'actor' => '操作员工', 'note' => '备注'],
    'statuses' => ['active' => '启用', 'inactive' => '停用'],
    'types' => ['import' => '入库', 'export' => '出库', 'adjustment_in' => '增加调整',
        'adjustment_out' => '减少调整', 'damaged' => '损坏', 'return' => '退回库存'],
    'errors' => ['item_inactive' => '库存项目已删除或未启用。', 'actor_inactive' => '操作账户已停用。', 'overflow' => '变动后库存超出系统范围。',
        'insufficient' => '库存不足，无法执行该减少操作。'],
];
