<?php

namespace app\api\controller;

use app\api\logic\AdLogic;
use app\api\logic\AdOrderLogic;
use app\api\logic\UserLogic;
use app\api\logic\UserPayTypeLogic;
use app\api\validate\AdValidate;
use think\facade\Log;
use think\facade\Queue;
use think\response\Json;
use Exception;

/**
 * 交易大厅
 *
 * Class RechargeController
 * @package app\shopapi\controller
 */
class AdController extends BaseApiController
{
    /**
     * 交易大厅
     *
     * @return Json
     */
    public function list(): Json
    {
        $params = (new AdValidate())->get()->goCheck('list');

        try {
            $params['type'] = $params['type'] == 1 ? 2 : 1;

            $newParams = [
                'type' => $params['type'],
                'last_id' => !empty($params['last_id']) ? $params['last_id'] : '',
                'limit' => 10
            ];
            $list = AdLogic::list($newParams);
            if ($list === false) {
                return $this->fail('获取数据异常');
            }

            if (empty($list)) {
                return $this->success('', [
                    'list' => [],
                    'last_id' => '',
                ]);
            }

            $adIds = array_unique(array_column($list, 'id'));
            $totalData = AdOrderLogic::getCountData($adIds);
            $totalData = array_column($totalData, 'cou', 'ad_id');
            $completeData = AdOrderLogic::getCountData($adIds, 4);
            $completeData = array_column($completeData, 'cou', 'ad_id');

            $lastId = '';
            $newData = [];
            foreach ($list as $value) {

                $sCou = $completeData[$value['id']] ?? 0;
                $cou = $totalData[$value['id']] ?? 0;

                $newData[] = [
                    'id' => $value['id'],
                    'nickname' => $value['nickname'],
                    'first_nickname' => $this->getFirstChar($value['nickname']),
                    'num' => $value['left_num'] ?? '0.000',
                    'price' => $value['price'],
                    'min_price' => $value['min_price'],
                    'max_price' => $value['max_price'],
                    'pay_time' => $value['pay_time'],
                    'pay_type' => explode(',', $value['pay_type']),
                    'type' => $value['type'] == 1 ? 2 : 1,
                    's_cou' => $sCou,
                    'rate' => $sCou > 0 ? bcmul(bcdiv($sCou, $cou, 4), 100, 2) : 0
                ];

                $lastId = $value['id'];
            }

            return $this->success('', [
                'list' => $newData,
                'last_id' => (!empty($newData) && count($newData) == $newParams['limit']) ? $lastId : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 我的广告列表
     *
     * @return Json
     */
    public function mList(): Json
    {
        try {
            $newParams = [
                'user_id' => $this->userId,
                'last_id' => !empty($params['last_id']) ? $params['last_id'] : '',
                'limit' => 10
            ];
            $list = AdLogic::listByUser($newParams);
            if ($list === false) {
                return $this->fail('获取数据异常');
            }

            if (empty($list)) {
                return $this->success('', [
                    'list' => [],
                    'last_id' => '',
                ]);
            }

            $lastId = '';
            $newData = [];
            foreach ($list as $value) {
                $nickname = $this->userInfo['nickname'] ?? '';

                $newData[] = [
                    'id' => $value['id'],
                    'nickname' => $nickname,
                    'first_nickname' => $this->getFirstChar($nickname),
                    'num' => $value['left_num'] ?? '0.000',
                    'price' => $value['price'],
                    'min_price' => $value['min_price'],
                    'max_price' => $value['max_price'],
                    'pay_time' => $value['pay_time'],
                    'pay_type' => explode(',', $value['pay_type']),
                    'status_b' => !($value['status'] == 1),
                    'type' => $value['type'],
                    's_count' => $value['s_cou'],
                    'rate' => $value['cou'] > 0 ? bcmul(bcdiv($value['s_cou'], $value['cou'], 4), 100, 2) : 0,
                ];

                $lastId = $value['id'];
            }

            return $this->success('', [
                'list' => $newData,
                'last_id' => (!empty($newData) && count($newData) == $newParams['limit']) ? $lastId : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-mList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        $params = (new AdValidate())->get()->goCheck('id');

        try {
            $info = AdLogic::infoHaveUser($params['id']);
            if (empty($info['id'])) {
                return $this->fail('数据不存在');
            }

            $zxjye = bcmul($info['min_price'], $info['price'], 2);
            $zdjye = bcmul($info['max_price'], $info['price'], 2);

            $newData = [
                'id' => $info['id'],
                'nickname' => $info['nickname'],
                'first_nickname' => $this->getFirstChar($info['nickname']),
                'num' => $info['left_num'],
                'price' => $info['price'],
                'min_price' => $info['min_price'],
                'max_price' => $info['max_price'],
                'pay_time' => $info['pay_time'],
                'pay_type' => explode(',', $info['pay_type']),
                'status_b' => !($info['status'] == 1),
                'type' => $info['type'],
                'tips' => $info['tips'],
                'zdjye2' => $info['max_price'] * $info['price'],
                'zxjye' => $zxjye,
                'zdjye' => $zdjye,
            ];

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 用户发布广告权限
     *
     * @return Json
     */
    public function perm(): Json
    {
        try {

            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo['id'])) {
                return $this->fail('用户不存在');
            }

            return $this->success('', [
                'ad_perm' => $userInfo['ad_perm']
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-perm Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 用户发布广告
     *
     * @return Json
     */
    public function addData(): Json
    {
        $params = (new AdValidate())->post()->goCheck('add', [
            'user_id' => $this->userId
        ]);

        try {
            $params['type'] = 2;
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo['id'])) {
                return $this->fail('用户不存在');
            }
            if ($userInfo['ad_perm'] != 2) {
                return $this->fail('广告功能不可用');
            }
            if (bccomp($userInfo['user_money'], $params['num'], 3) < 0) {
                return $this->fail('出售数量大于可出售数量');
            }

            $payTypeParams = $params['pay_type'];
            if (is_string($params['pay_type'])) {
                $payTypeParams = explode(',', $payTypeParams);
            }
            $tmpPayType = [];
            if (in_array('wx', $payTypeParams)) {
                $userPayTypeData = UserPayTypeLogic::infoByType($this->userId, 'wx');
                if (empty($userPayTypeData['id'])) {
                    return $this->fail('请先去我的-收款账户填写微信收款方式');
                }

                $tmpPayType[] = 'wx';
            }
            if (in_array('zfb', $payTypeParams)) {
                $userPayTypeData = UserPayTypeLogic::infoByType($this->userId, 'zfb');
                if (empty($userPayTypeData['id'])) {
                    return $this->fail('请先去我的-收款账户填写支付宝收款方式');
                }

                $tmpPayType[] = 'zfb';
            }
            if (in_array('yhk', $payTypeParams)) {
                $userPayTypeData = UserPayTypeLogic::infoByType($this->userId, 'yhk');
                if (empty($userPayTypeData['id'])) {
                    return $this->fail('请先去我的-收款账户填写银行卡收款方式');
                }

                $tmpPayType[] = 'yhk';
            }
            if (in_array('usdt', $payTypeParams)) {
                $userPayTypeData = UserPayTypeLogic::infoByType($this->userId, 'usdt');
                if (empty($userPayTypeData['id'])) {
                    return $this->fail('请先去我的-收款账户填写USDT收款方式');
                }

                $tmpPayType[] = 'usdt';
            }

            $newParams = [
                'user_id' => $this->userId,
                'num' => $params['num'],
                'price' => $params['price'],
                'min_price' => $params['min_price'],
                'max_price' => $params['max_price'],
                'pay_time' => $params['pay_time'],
                'pay_type' => implode(',', $tmpPayType),
                'type' => $params['type'],
                'tips' => $params['tips'] ?? '',
                'status' => 2
            ];

            $res = AdLogic::addData($newParams);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 状态转换
     *
     * @return Json
     */
    public function statusChange(): Json
    {
        $params = (new AdValidate())->post()->goCheck('id');

        try {
            $info = AdLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }

            $res = AdLogic::changeStatus($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-statusChange Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 删除广告
     *
     * @return Json
     */
    public function del(): Json
    {
        $params = (new AdValidate())->post()->goCheck('id');

        try {
            $info = AdLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }

            $userInfo = UserLogic::info($info['user_id']);
            if (empty($userInfo['id'])) {
                return $this->fail('用户不存在');
            }
            if (bccomp($userInfo['freeze_money'], $info['left_num'], 3) < 0) {
                return $this->fail('返还的冻结金额不足');
            }

            $res = AdLogic::deleteData($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-del Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 广告订单列表
     *
     * @return Json
     */
    public function adOrderList(): Json
    {
        try {

            $search = (string)$this->request->get('search');
            $status = (int)$this->request->get('status');
            $lastId = (int)$this->request->get('last_id');
            if ($status < 0 || $status > 5) {
                $status = 0;
            }
            if (strlen($search) > 30) {
                return $this->fail('搜索数据过长');
            }

            $newParams = [
                'user_id' => $this->userId,
                'status' => $status,
                'search' => $search,
                'last_id' => !empty($lastId) ? $lastId : '',
                'limit' => 10
            ];

            // 广告订单列表
            $list = AdOrderLogic::list($newParams);
            if ($list === false) {
                return $this->fail(AdOrderLogic::getError());
            }

            $newData = [];
            $lastId = '';
            foreach ($list as $value) {
                $isBuy = $value['user_id'] == $this->userId;
                $newData[] = [
                    'id' => $value['id'],
                    'order_no' => $value['order_no'],
                    'order_type' => $value['order_type'],
                    'num' => $value['num'],
                    'dan_price' => $value['dan_price'],
                    'price' => $value['price'],
                    'pay_type' => $value['pay_type'],
                    'status' => $value['status'],
                    'expire_time' => $value['expire_time'],
                    'time' => $value['create_time'],
                    'is_buy' => $isBuy,
                    'nickname' => $isBuy ? $value['sell_nickname'] : $value['buy_nickname']
                ];

                $lastId = $value['id'];
            }

            return $this->success('', [
                'list' => $newData,
                'last_id' => (!empty($newData) && count($newData) == $newParams['limit']) ? $lastId : '',
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-adOrderList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 成交数量
     *
     * @return Json
     */
    public function completeData(): Json
    {
        try {

            // 数量
            $count = AdOrderLogic::getCount($this->userId);
            $successCount = AdOrderLogic::getCount($this->userId, 4);

            $newData = [
                's_count' => $successCount,
                'rate' => bcmul(bcdiv($successCount, $count, 4), 100, 2)
            ];

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-adOrderList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 30日内成交数量
     *
     * @return Json
     */
    public function thirtyCompleteData(): Json
    {
        try {

            $adId = (string)$this->request->get('id');

            $newData = [
                'cou' => 0,
                'rate' => 0,
                'complete_time' => 0,
                'pay_time' => 0,
            ];

            // 数量
            $count = AdOrderLogic::geCompleteCount($adId);
            if ($count == 0) {
                return $this->success('', $newData);
            }

            $data = AdOrderLogic::geCompleteSumData($adId);
            $newData['cou'] = $data['cou'] ?? 0;
            $newData['rate'] = bcmul(bcdiv($newData['cou'], $count, 4), 100, 2);

            $time = $data['time'] ?? 0;
            $payTime = $data['pay_time'] ?? 0;
            $completeTime = $data['complete_time'] ?? 0;

            $newData['pay_time'] = !empty($time) && $payTime > $time ? bcdiv(bcsub($payTime, $time), 60, 2) : 0;
            $newData['complete_time'] = !empty($time) && $completeTime > $time ? bcdiv(bcsub($completeTime, $time), 60, 2) : 0;

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-thirtyCompleteData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 广告订单详情
     *
     * @return Json
     */
    public function adOrderInfo(): Json
    {
        $params = (new AdValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告订单详情
            $info = AdOrderLogic::infoHaveUser($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId && $info['to_user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }

            $isBuy = $info['user_id'] == $this->userId;

            $userPayTypeData = UserPayTypeLogic::infoByType($info['to_user_id'], $info['pay_type']);

            $payTypeShow = '';
            $payAccount = '';
            switch ($info['pay_type']) {
                case 'wx':
                    $payTypeShow = '微信';
                    $payAccount = $userPayTypeData['wechat'] ?? '';
                    break;

                case 'zfb':
                    $payTypeShow = '支付宝';
                    $payAccount = $userPayTypeData['alipay'] ?? '';
                    break;

                case 'yhk':
                    $payTypeShow = '银行卡';
                    $payAccount = $userPayTypeData['bank_card'] ?? '';
                    break;

                case 'usdt':
                    $payTypeShow = 'USDT';
                    $payAccount = $userPayTypeData['trc'] ?? '';
                    break;
            }

            // 数量
            $count = AdOrderLogic::getCount($info['to_user_id']);
            $successCount = AdOrderLogic::getCount($info['to_user_id'], 4, true);

            $nickname = $isBuy ? $info['sell_nickname'] : $info['buy_nickname'];

            $newData = [
                'id' => $info['id'],
                'order_no' => $info['order_no'],
                'ad_id' => $info['ad_id'],
                'expire_time' => $info['expire_time'],
                'status' => $info['status'],
                'order_type' => $info['order_type'],
                'price' => $info['price'],
                'dan_price' => $info['dan_price'],
                'pay_type' => $info['pay_type'],
                'pay_type_show' => $payTypeShow,
                'num' => $info['num'],
                'tips' => $info['tips'],
                'cancel_type' => $info['cancel_type'],
                'is_buy' => $isBuy,
                'nickname' => $nickname,
                'first_nickname' => $this->getFirstChar($nickname),
                'pay_name' => $userPayTypeData['name'] ?? '',
                'pay_account' => $payAccount,
                'pay_qrcode' => $userPayTypeData['qrcode'] ?? '',
                'rate' => ($count > 0) ? bcmul(bcdiv($successCount, $count, 4), 100, 2) : 100,
                'time' => $info['create_time'],
            ];

            return $this->success('', $newData);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-adOrderInfo Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 买入发布的广告
     *
     * @return Json
     */
    public function buy(): Json
    {
        $params = (new AdValidate())->post()->goCheck('adBuy', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告详情
            $info = AdLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['status'] == 1) {
                return $this->fail('广告已下架');
            }
            if ($info['user_id'] == $this->userId) {
                return $this->fail('不支持向自己的订单下单');
            }

            $minMoney = bcmul($info['min_price'], $info['price'], 2);
            $maxMoney = bcmul($info['max_price'], $info['price'], 2);
            if (bccomp($params['price'], $minMoney, 2) < 0) {
                return $this->fail('购买金额限制：' . $minMoney . '-' . $maxMoney);
            }
            if (bccomp($params['price'], $maxMoney, 2) > 0) {
                return $this->fail('购买金额限制：' . $minMoney . '-' . $maxMoney);
            }

            // 用户详情
            $userInfo = UserLogic::info($this->userId);
            if (empty($userInfo['id'])) {
                return $this->fail('用户不存在');
            }

            $buyNum = number_format(bcdiv($params['price'], $info['price'], 4), 3);
            if (bccomp($buyNum, $info['left_num'], 3) > 0) {
                return $this->fail('购买数量超过卖家可卖数量，卖家当前可卖数量为：' . $info['num']);
            }

            $newParams = [
                'user_id' => $this->userId,
                'buy_num' => $buyNum,
                'price' => $params['price'],
                'pay_type' => $params['pay_type']
            ];

            $res = AdOrderLogic::addOrder($info, $newParams);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            // 触发定时取消订单
            $delay = $info['pay_time'] * 60;
            Queue::later(
                $delay,
                'app\job\CancelOrder',
                ['order_id' => $res['id']],
                'order_cancel' // 单独队列，便于管理
            );

            Log::info("订单取消任务已推送", ['order_id' => $res['id']]);

            return $this->success('', [
                'order_id' => $res['id'],
            ]);

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-buy Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 支付订单
     *
     * @return Json
     */
    public function payOrder(): Json
    {
        $params = (new AdValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告订单详情
            $info = AdOrderLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }

            $res = AdOrderLogic::paySuccessOrder($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 申诉订单
     *
     * @return Json
     */
    public function appealOrder(): Json
    {
        $params = (new AdValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告订单详情
            $info = AdOrderLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }
            if ($info['status'] != 2) {
                return $this->fail('当前订单状态不允许申诉');
            }

            $res = AdOrderLogic::appealOrder($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 完成订单
     *
     * @return Json
     */
    public function completeOrder(): Json
    {
        $params = (new AdValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告订单详情
            $info = AdOrderLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['to_user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }
            if ($info['status'] != 2) {
                return $this->fail('当前订单状态不允许确认收款');
            }

            $res = AdOrderLogic::completeOrder($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 取消订单
     *
     * @return Json
     */
    public function cancelOrder(): Json
    {
        $params = (new AdValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            // 广告订单详情
            $info = AdOrderLogic::info($params['id']);
            if (empty($info['id'])) {
                return $this->fail('广告不存在');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('获取数据异常');
            }
            if ($info['status'] != 1 && $info['status'] != 2) {
                return $this->fail('当前订单状态不允许取消');
            }

            $res = AdOrderLogic::cancelOrder($info);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 获取昵称第一个字
     *
     * @param $nickname
     * @return string
     */
    private function getFirstChar($nickname): string
    {
        if (empty($nickname)) {
            return '';
        }

        return mb_substr($nickname, 0, 1, 'UTF-8');
    }
}
