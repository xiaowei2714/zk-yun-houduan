<?php
/**
 * Author: Jarshs
 * 2025/5/6
 */

namespace app\job;

use app\api\logic\AdOrderLogic;
use app\common\model\AdOrder;
use think\queue\Job;
use think\facade\Log;
use Exception;

class CancelOrder
{
    public function fire(Job $job, $data)
    {
        try {
            Log::record('info: job-CancelOrder Start');

            $this->handleData($job, $data);

            Log::record('info: job-CancelOrder End');

        } catch (Exception $e) {
            Log::record('Exception: job-CancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    protected function handleData(Job $job, $data)
    {
        Log::record('info: job-CancelOrder params: ' . json_encode($data));

        $orderId = $data['order_id'] ?? 0;

        if (!$orderId) {
            $job->delete();
            return;
        }

        // 处理订单取消
        $result = $this->handleCancel($orderId);

        if ($result) {
            Log::info("订单自动取消成功order_id" . $orderId, ['order_id' => $orderId]);
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
                Log::warning("订单不存在order_id" . $orderId, ['order_id' => $orderId]);
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

            $res = AdOrderLogic::cancelOrder($order);
            if (!$res) {
                Log::error("订单取消失败: " . AdOrderLogic::getError(), [
                    'order_id' => $orderId,
                    'error' => AdOrderLogic::getError()
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("订单取消失败: " . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e
            ]);
            return false;
        }
    }
}
