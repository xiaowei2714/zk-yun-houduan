<?php
declare (strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use Exception;

class ClearUnPayOrder extends Command
{
    const DAY = 20;

    protected function configure()
    {
        // 指令配置
        $this->setName('clear_order')
            ->setDescription('清理逾期未支付订单');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            Log::record('info: command-ClearUnPayOrder Start');

            $this->handleData();

            Log::record('info: command-ClearUnPayOrder End');

            return true;

        } catch (Exception $e) {
            Log::record('Exception: command-ClearUnPayOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    private function handleData()
    {
        try {

            $curTime = time();
            $dayTime = time() - self::DAY * 24 * 3600;

            $tablePre = env('database.prefix');

            // 删除话费、电费、礼品卡充值记录
            $consumeRechargeTable = $tablePre . 'consume_recharge';

            $sql = <<< EOT
UPDATE $consumeRechargeTable SET `delete_time` = $curTime WHERE `status` = 1 AND `create_time` < $dayTime
EOT;

            $res = Db::execute($sql);
            if ($res) {
                Log::record('info: command-ClearUnPayOrder 删除话费电费礼品卡订单成功');
            } else {
                Log::record('info: command-ClearUnPayOrder 删除话费电费礼品卡订单失败');
            }

            // 删除交易记录
            $adOrderTable = $tablePre . 'ad_order';

            $sql = <<< EOT
UPDATE $adOrderTable SET `delete_time` = $curTime WHERE `status` = 1 AND `create_time` < $dayTime
EOT;

            $res = Db::execute($sql);
            if ($res) {
                Log::record('info: command-ClearUnPayOrder 删除交易订单成功');
            } else {
                Log::record('info: command-ClearUnPayOrder 删除交易订单失败');
            }

            // 删除充值记录
            $rechargeTable = $tablePre . 'recharge';

            $sql = <<< EOT
UPDATE $rechargeTable SET `delete_time` = $curTime WHERE `status` = 1 AND `create_time` < $dayTime
EOT;

            $res = Db::execute($sql);
            if ($res) {
                Log::record('info: command-ClearUnPayOrder 删除交易充值成功');
            } else {
                Log::record('info: command-ClearUnPayOrder 删除交易充值失败');
            }

        } catch (Exception $e) {
            Log::record('Exception: command-ClearUnPayOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }
}
