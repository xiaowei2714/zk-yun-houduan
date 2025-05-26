<?php

namespace app\api\controller;

use app\adminapi\logic\RechargeLogic;
use app\api\logic\ConsumeQueryLogic;
use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\UserLogic;
use app\api\logic\WebSettingLogic;
use app\api\service\UserMealService;
use app\api\validate\ConsumeQueryValidate;
use app\api\validate\ConsumeRechargeValidate;
use app\common\service\ConsumeRechargeService;
use think\facade\Config;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 话费、电费、油费查询
 *
 * Class RechargeController
 * @package app\shopapi\controller
 */
class ConsumeQueryController extends BaseApiController
{
    /**
     * 话费配置
     *
     * @return Json
     */
    public function queryPhoneConfig(): Json
    {
        try {
            $queryPhonePrice = WebSettingLogic::getQueryPhone();

            return $this->data([
                'query' => $queryPhonePrice,
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-queryPhoneConfig Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 电费配置
     *
     * @return Json
     */
    public function queryElectricityConfig(): Json
    {
        try {
            $queryElectricityPrice = WebSettingLogic::getQueryElectricity();

            return $this->data([
                'query' => $queryElectricityPrice
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-queryElectricityConfig Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 查询话费
     *
     * @return Json
     */
    public function queryPhone(): Json
    {
        $params = (new ConsumeQueryValidate())->post()->goCheck('phone', [
            'user_id' => $this->userId,
            'type' => 1
        ]);

        try {

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 查询需要花费金额
            $queryPrice = WebSettingLogic::getQueryPhone();
            if ($queryPrice === '') {
                return $this->fail('系统未设置查询，请联系客服');
            }

            // 比较用户余额
            if (bccomp($userInfo['user_money'], $queryPrice, 3) < 0) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            // 实时查询话费
            $requestData = (new ConsumeRechargeService())->getPhoneBalance($params['phone']);
            if (empty($requestData)) {
                return $this->fail('暂不支持的手机号查询');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号查询');
            }

            // 新增数据
            $params['account'] = $params['phone'];
            $params['account_type'] = $requestData['isp_id'];
            $params['balance'] = $requestData['cur_fee'];
            $params['pay_price'] = $queryPrice;

            $res = ConsumeQueryLogic::addData($params);
            if (!$res) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }

            // 列表
            $data = ConsumeQueryLogic::list($params['user_id'], $params['account'], $params['type']);

            $newData = [];
            foreach ($data as $value) {
                $tmpData = [
                    'balance' => $value['balance'],
                    'time' => date('Y-m-d H:i', strtotime($value['create_time']))
                ];

                $newData[] = $tmpData;
            }

            return $this->data([
                'number' => $params['account'],
                'isp' => $requestData['isp'],
                'balance' => $requestData['cur_fee'],
                'list' => $newData,
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-QueryPhone Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量查询话费
     *
     * @return Json
     */
    public function batchQueryPhone(): Json
    {
        $params = (new ConsumeQueryValidate())->post()->goCheck('batch', [
            'user_id' => $this->userId,
            'type' => 1
        ]);

        try {

            $batchData = explode(',', $params['batch_data']);
            if (empty($batchData)) {
                return $this->fail('请正确输入手机号');
            }

            $msg = '';
            foreach ($batchData as $phone) {
                if (!preg_match("/^1[3456789]\d{9}$/", $phone)) {
                    $msg .= $phone . ' 请正确输入手机号' . PHP_EOL;
                }
            }

            if (!empty($msg)) {
                return $this->fail($msg);
            }

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 查询需要花费金额
            $queryPrice = WebSettingLogic::getQueryPhone();
            if ($queryPrice === '') {
                return $this->fail('系统未设置查询，请联系客服');
            }

            // 比较用户余额
            $num = count($batchData);
            if (bccomp($userInfo['user_money'], bcmul($num, $queryPrice, 3), 3) < 0) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            // 实时查询话费
            $newData = [];
            foreach ($batchData as $phone) {
                $tmp = [
                    'cs' => false,
                    'msg' => '',
                    'number' => $phone,
                    'balance' => 0,
                    'isp' => ''
                ];

                $requestData = (new ConsumeRechargeService())->getPhoneBalance($phone);
                if (empty($requestData)) {
                    $tmp['msg'] = '暂不支持的手机号查询' . PHP_EOL;
                    $newData[] = $tmp;
                    continue;
                }
                if (!$requestData['is_success']) {
                    $tmp['msg'] = !empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的手机号查询';
                    $newData[] = $tmp;
                    continue;
                }

                if ($phone == '13211111111') {
                    $tmp['msg'] = '暂不支持的手机号充值';
                    $newData[] = $tmp;
                    continue;
                }

                // 新增数据
                $params['account'] = $phone;
                $params['account_type'] = $requestData['isp_id'];
                $params['balance'] = $requestData['cur_fee'];
                $params['pay_price'] = $queryPrice;

                $res = ConsumeQueryLogic::addData($params);
                if (!$res) {
                    $tmp['msg'] = ConsumeRechargeLogic::getError();
                    $newData[] = $tmp;
                    continue;
                }

                $tmp['cs'] = true;
                $tmp['balance'] = $requestData['cur_fee'];
                $tmp['isp'] = $requestData['isp'];

                $newData[] = $tmp;
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-QueryPhone Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 查询电费
     *
     * @return Json
     */
    public function queryElectricity(): Json
    {
        $params = (new ConsumeQueryValidate())->post()->goCheck('electricity', [
            'user_id' => $this->userId,
            'type' => 2
        ]);

        try {

            $areaData = Config::get('project.area');
            if (empty($areaData[$params['area']])) {
                return $this->fail('请正确选择省份');
            }

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 查询需要花费金额
            $queryPrice = WebSettingLogic::getQueryElectricity();
            if ($queryPrice === '') {
                return $this->fail('系统未设置查询，请联系客服');
            }

            // 比较用户余额
            if (bccomp($userInfo['user_money'], $queryPrice, 3) < 0) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            // 实时查询
            $requestData = (new ConsumeRechargeService())->getElectricityBalance($params['number'], ($params['area'] + 1));
            if (empty($requestData)) {
                return $this->fail('暂不支持的电费号查询');
            }
            if (!$requestData['is_success']) {
                return $this->fail(!empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的电费号查询');
            }

            // 新增数据
            $params['account'] = $params['number'];
            $params['balance'] = $requestData['real_balance'];
            $params['pay_price'] = $queryPrice;

            $res = ConsumeQueryLogic::addData($params);
            if (!$res) {
                return $this->fail(ConsumeRechargeLogic::getError());
            }

            // 列表
            $data = ConsumeQueryLogic::list($params['user_id'], $params['account'], $params['type']);

            $newData = [];
            foreach ($data as $value) {
                $tmpData = [
                    'balance' => $value['balance'],
                    'time' => date('Y-m-d H:i', strtotime($value['create_time']))
                ];

                $newData[] = $tmpData;
            }

            return $this->data([
                'number' => $params['account'],
                'area' => $params['area'],
                'balance' => $requestData['real_balance'],
                'list' => $newData,
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-queryElectricity Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量查询电费
     *
     * @return Json
     */
    public function batchQueryElectricity(): Json
    {
        $params = (new ConsumeQueryValidate())->post()->goCheck('batch', [
            'user_id' => $this->userId,
            'type' => 2
        ]);

        try {

            $batchData = explode(',', $params['batch_data']);
            if (empty($batchData)) {
                return $this->fail('请正确输入电费号');
            }

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            // 查询需要花费金额
            $queryPrice = WebSettingLogic::getQueryElectricity();
            if ($queryPrice === '') {
                return $this->fail('系统未设置查询，请联系客服');
            }

            // 比较用户余额
            $num = count($batchData);
            if (bccomp($userInfo['user_money'], bcmul($num, $queryPrice, 3), 3) < 0) {
                return $this->fail('余额不足，请前往充值页面进行充值');
            }

            $areaData = array_flip(Config::get('project.area'));

            // 实时查询话费
            $newData = [];
            foreach ($batchData as $value) {

                $tmp = [
                    'cs' => false,
                    'msg' => '',
                    'number' => $value,
                    'area' => '',
                    'balance' => 0,
                ];

                $tmpData = explode(' ', $value);
                if (empty($tmpData[0])) {
                    $tmp['msg'] = '暂不支持的户号查询';
                    $newData[] = $tmp;
                    continue;
                }

                $tmp['number'] = $tmpData[0];
                $tmp['area'] = $tmpData[1] ?? '';

                if (strlen($tmp['number']) > 30) {
                    $tmp['msg'] = '户号不能超过30个字符';
                    $newData[] = $tmp;
                    continue;
                }
                if (mb_strlen($tmp['area']) > 30) {
                    $tmp['msg'] = '区域不能超过30个字符';
                    $newData[] = $tmp;
                    continue;
                }
                if (!isset($areaData[$tmp['area']])) {
                    $tmp['msg'] = '不支持的区域';
                    $newData[] = $tmp;
                    continue;
                }

                $areaCode = $areaData[$tmp['area']];
                $requestData = (new ConsumeRechargeService())->getElectricityBalance($tmp['number'], ($areaCode + 1));
                if (empty($requestData)) {
                    $tmp['msg'] = '暂不支持的户号充值' . PHP_EOL;
                    $newData[] = $tmp;
                    continue;
                }
                if (!$requestData['is_success']) {
                    $tmp['msg'] = !empty($requestData['msg']) ? $requestData['msg'] : '暂不支持的户号查询';
                    $newData[] = $tmp;
                    continue;
                }

                // 新增数据
                $params['account'] = $tmp['number'];
                $params['balance'] = $requestData['real_balance'];
                $params['area'] = $areaCode;
                $params['pay_price'] = $queryPrice;

                $res = ConsumeQueryLogic::addData($params);
                if (!$res) {
                    $tmp['msg'] = ConsumeRechargeLogic::getError();
                    $newData[] = $tmp;
                    continue;
                }

                $tmp['cs'] = true;
                $tmp['balance'] = $requestData['real_balance'];

                $newData[] = $tmp;
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-batchQueryElectricity Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 查询历史记录
     *
     * @return Json
     */
    public function history(): Json
    {
        $params = (new ConsumeQueryValidate())->get()->goCheck('type', [
            'user_id' => $this->userId
        ]);

        try {

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $newParams = [
                'user_id' => $this->userId,
                'type' => $params['type'],
                'last_id' => !empty($params['last_id']) ? $params['last_id'] : '',
                'limit' => 15
            ];
            $data = ConsumeQueryLogic::listByUser($newParams);

            $newData = [];
            $lastId = '';
            if (!empty($data)) {
                $operatorData = Config::get('project.operator');
                $areaData = Config::get('project.area');

                foreach ($data as $value) {

                    $tmpData = [
                        'account' => $value['account'],
                        'type' => $operatorData[$value['account_type']] ?? '',
                        'balance' => $value['balance'],
                    ];

                    if ($params['type'] == 2) {
                        $tmpData['type'] = $areaData[$value['area']] ?? '';
                    }

                    $newData[] = $tmpData;
                    $lastId = $value['id'];
                }
            }

            return $this->data([
                'list' => $newData,
                'last_id' => (!empty($newData) && count($newData) == $newParams['limit']) ? $lastId : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-QueryPhone Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 查询指定账号历史记录
     *
     * @return Json
     */
    public function accountHistory(): Json
    {
        $params = (new ConsumeQueryValidate())->get()->goCheck('accountHistory', [
            'user_id' => $this->userId
        ]);

        try {

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo)) {
                return $this->fail('当前用户不可用，请联系客服');
            }
            if ($userInfo['is_disable'] == 1) {
                return $this->fail('当前用户账号异常，请联系客服');
            }

            $newParams = [
                'user_id' => $this->userId,
                'type' => $params['type'],
                'number' => $params['number'],
                'last_id' => !empty($params['last_id']) ? $params['last_id'] : '',
                'limit' => 15
            ];
            $data = ConsumeQueryLogic::listByAccount($newParams);

            $number = '';
            $isp = '';
            $area = '';
            $balance = 0;
            $newData = [];
            $lastId = '';
            if (!empty($data)) {
                foreach ($data as $value) {
                    $newData[] = [
                        'balance' => $value['balance'],
                        'time' => date('Y-m-d H:i', strtotime($value['create_time']))
                    ];

                    $lastId = $value['id'];
                }

                $operatorData = Config::get('project.operator');
                $firstData = array_pop($data);
                $info = ConsumeQueryLogic::info($firstData['id']);
                $number = $info['account'];
                $isp = $operatorData[$info['account_type']] ?? '';
                $area = $info['area'];
                $balance = $info['balance'];
            }

            return $this->data([
                'number' => $number,
                'isp' => $isp,
                'area' => $area,
                'balance' => $balance,
                'list' => $newData,
                'last_id' => (!empty($newData) && count($newData) == $newParams['limit']) ? $lastId : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-ConsumeQueryController-QueryPhone Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
