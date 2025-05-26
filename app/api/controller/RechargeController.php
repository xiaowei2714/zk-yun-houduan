<?php

namespace app\api\controller;

use app\api\logic\RechargeLogic;
use app\api\validate\RechargeValidate;
use app\common\service\ConfigService;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 充值控制器
 * Class RechargeController
 * @package app\shopapi\controller
 */
class RechargeController extends BaseApiController
{
    /**
     * @return Json
     */
    public function getConfig(): Json
    {
        try {

            $confNames = [
                'recharge_network',
                'recharge_address',
            ];

            $confData = ConfigService::getByNames('website', $confNames);
            $confData = array_column($confData, 'value', 'name');

            return $this->success('', [
                'network' => $confData['recharge_network'] ?? '',
                'address' => $confData['recharge_address'] ?? '',
                'img' => !empty($confData['recharge_address']) ? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . $confData['recharge_address'] : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-getConfig Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 列表
     *
     * @return Json
     */
    public function list(): Json
    {
        try {

            $list = RechargeLogic::list($this->userId);
            if ($list === false) {
                return $this->fail(RechargeLogic::getError());
            }

            $newData = [];
            foreach ($list as $value) {
                $newData[] = [
                    'id' => $value['id'],
                    'status' => $value['status'],
                    'money' => $value['money'],
                    'time' => $value['create_time'],
                ];
            }

            return $this->success('', [
                'list' => $newData
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 详情
     *
     * @return Json
     */
    public function info(): Json
    {
        $params = (new RechargeValidate())->get()->goCheck('id', [
            'user_id' => $this->userId,
        ]);

        try {

            $info = RechargeLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('订单不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('异常操作');
            }

            return $this->success('', [
                'info' => [
                    'id' => $info['id'],
                    'order_no' => $info['order_no'],
                    'money' => $info['money'],
                    'status' => $info['status'],
                ]
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 充值
     *
     * @return Json
     */
    public function recharge(): Json
    {
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
        ]);

        try {

            $result = RechargeLogic::recharge($params);
            if ($result === false) {
                return $this->fail(RechargeLogic::getError());
            }

            return $this->success('', [
                'id' => $result
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-recharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

//    /**
//     * 充值
//     *
//     * @return Json
//     */
//    public function recharge(): Json
//    {
//        $params = (new RechargeValidate())->post()->goCheck('recharge', [
//            'user_id' => $this->userId,
//            'terminal' => $this->userInfo['terminal'],
//        ]);
//        $result = RechargeLogic::recharge($params);
//        if (false === $result) {
//            return $this->fail(RechargeLogic::getError());
//        }
//        return $this->data($result);
//    }

    /**
     * @notes 充值配置
     * @return Json
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public function config(): Json
    {
        return $this->data(RechargeLogic::config($this->userId));
    }
}
