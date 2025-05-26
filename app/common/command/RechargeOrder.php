<?php
declare (strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use Exception;

class RechargeOrder extends Command
{
    const DAY = 20;

    protected function configure()
    {
        // 指令配置
        $this->setName('recharge_order')
            ->setDescription('检查支付订单成功');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            Log::record('info: command-RechargeOrder Start');

            $this->handleData();

            Log::record('info: command-RechargeOrder End');

            return true;

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    private function handleData()
    {
        try {
var_dump(1);

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }
}
