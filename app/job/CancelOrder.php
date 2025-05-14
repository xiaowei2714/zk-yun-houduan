<?php
/**
 * Author: Jarshs
 * 2025/5/6
 */

namespace app\job;

use app\common\model\AdOrder;
use think\queue\Job;
use think\facade\Log;
//use app\service\OrderService;

class CancelOrder
{
    public function fire(Job $job, $data)
    {
        $orderId = $data['order_id'] ?? 0;

        if (!$orderId) {
            $job->delete();
            return;
        }

        // 处理订单取消
        $result = $this->handleCancel($orderId);

        if ($result) {
            Log::info("订单自动取消成功order_id".$orderId, ['order_id' => $orderId]);
            $job->delete();
        } else {
            // 失败重试（最多3次）
            if ($job->attempts() > 3) {
                Log::error("订单取消任务多次尝试失败", ['order_id' => $orderId]);
                $job->delete();
            } else {
                $job->release(60); // 1分钟后重试
            }
        }
    }

    protected function handleCancel($orderId)
    {
        try {
            $order = AdOrder::find($orderId);

            if (!$order) {
                Log::warning("订单不存在order_id".$orderId, ['order_id' => $orderId]);
                return true;
            }

            // 只有待支付订单才能取消
            if ($order->status != 1) {
                Log::info("订单状态已变更，无需取消", [
                    'order_id' => $orderId,
                    'current_status' => $order->status
                ]);
                return true;
            }

            // 开启事务
            $order->startTrans();

            // 更新订单状态
            $order->status = 5;
            $order->cancel_type = 1;
            $order->cancel_time = time();
            $order->save();

            // 其他关联操作（如库存释放、日志记录等）
            // ...

            $order->commit();
            return true;
        } catch (\Exception $e) {
            $order->rollback();
            Log::error("订单取消失败: " . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e
            ]);
            return false;
        }
    }
}