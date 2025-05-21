<?php

namespace app\api\controller;

use app\adminapi\logic\RechargeLogic;
use app\api\logic\ConsumeRechargeLogic;
use app\api\logic\UserAccountLogLogic;
use app\api\logic\UserLogic;
use app\api\logic\WebSettingLogic;
use app\api\service\UserMealService;
use app\api\validate\ConsumeRechargeValidate;
use app\api\validate\UserAccountLogValidate;
use app\common\enum\user\AccountLogEnum;
use app\common\service\ConfigService;
use app\common\service\ConsumeRechargeService;
use think\facade\Config;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 用户账户记录
 *
 * Class UserAccountController
 * @package app\shopapi\controller
 */
class UserAccountController extends BaseApiController
{
    /**
     * 列表
     *
     * @return Json
     */
    public function list(): Json
    {
        try {

            $list = UserAccountLogLogic::transferlist($this->userId);
            if ($list === false) {
                return $this->fail('系统异常，请联系客服');
            }

            $newData = [];
            foreach ($list as $value) {
                $tmpData = [
                    'money' => $value['change_amount'],
                    'time' => $value['create_time']
                ];

                $newData[] = $tmpData;
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-UserAccountController-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 转账
     *
     * @return Json
     */
    public function transfer(): Json
    {
        $params = (new UserAccountLogValidate())->post()->goCheck('transfer', [
            'user_id' => $this->userId
        ]);

        try {
            if (strlen($params['account']) > 30) {
                return $this->fail('输入账号错误');
            }
            if (!preg_match("/^(\d+(\.\d{1,2})?)$/", $params['money'])) {
                return $this->fail('输入金额错误');
            }

            // 获取转账对象详情
            $changeUserInfo = UserLogic::infoBySn($params['account']);
            if (empty($changeUserInfo['id'])) {
                return $this->fail('获取不到划转用户信息');
            }
            if ($changeUserInfo['is_disable'] == 1) {
                return $this->fail('划转用户账号异常，请联系客服');
            }
            if ($changeUserInfo['id'] == $this->userId) {
                return $this->fail('不能向自己划转');
            }

            $newData = [
                'user_id' => $this->userId,
                'change_user_id' => $changeUserInfo['id'],
                'change_user_sn' => $changeUserInfo['sn'],
                'money' => $params['money'],
            ];

            $res = UserAccountLogLogic::addTransferData($newData);
            if (!$res) {
                return $this->fail(UserAccountLogLogic::getError());
            }

            return $this->success();

        } catch (Exception $e) {
            Log::record('Exception: api-UserAccountController-transfer Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
