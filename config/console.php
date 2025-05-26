<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 定时任务
        'crontab' => 'app\common\command\Crontab',
        // 退款查询
        'query_refund' => 'app\common\command\QueryRefund',

        // 清理逾期未支付订单
        'clear_order' => 'app\common\command\ClearUnPayOrder',

        // 充值成功
        'recharge_order' => 'app\common\command\RechargeOrder',

    ],
];
