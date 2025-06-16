<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\api\logic\IndexLogic;
use app\api\logic\LoginLogic;
use app\api\logic\MealLogic;
use app\api\logic\SubstationLogic;
use app\api\logic\UserMealLogic;
use app\api\service\UserMealService;
use app\api\service\UserTokenService;
use app\common\cache\UserAccountSafeCache;
use app\common\enum\YesNoEnum;
use app\common\model\EmailVerifyCode;
use app\common\model\Notice;
use app\common\model\Substation;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
use app\common\model\UserPayType;
use app\common\model\Withdraw;
use app\common\model\WriteOffUser;
use app\common\service\ConfigService;
use app\common\service\FileService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use PHPMailer\PHPMailer\PHPMailer;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Config;
use think\facade\Db;
use think\response\Json;

/**
 * index
 * Class IndexController
 * @package app\api\controller
 */
class IndexController extends BaseApiController
{
    public array $notNeedLogin = ['index', 'config', 'policy', 'decorate', 'getPrivacy', 'getSetting', 'sendCode', 'register', 'login', 'changePwd', 'getIndexConfig',
        'getNoticeList', 'getNoticeDetail', 'getKf'];

    /**
     * 获取用户余额
     * Author: Jarshs
     * 2025/5/4
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getAdUserMoney()
    {
        $user = User::find($this->userId);
        return $this->success('', [
            'user_money' => $user->user_money,
        ]);
    }

    /**
     * 获取昵称第一个字
     * Author: Jarshs
     * 2025/5/4
     * @param $nickname
     * @return string
     */
    private function getFirstChar($nickname)
    {
        if (empty($nickname)) {
            return '';
        }
        return mb_substr($nickname, 0, 1, 'UTF-8');
    }

    /**
     * 获取资产明细列表
     * Author: Jarshs
     * 2025/5/2
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMoneyLogList()
    {
        $type = $this->request->get('type');
        $date_type = $this->request->get('date_type');
        $date = $this->request->get('date');
        $lastId = $this->request->get('last_id');

        $where = [];
        $where[] = ['user_id', '=', $this->userId];
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }

        if ($date_type > 0) {
            if ($date_type == 1) {
                $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
                $endTime = strtotime(date('Y-m-d 23:59:59'));
            } elseif ($date_type == 2) {
                $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-14 days')));
                $endTime = strtotime(date('Y-m-d 23:59:59'));
            } elseif ($date_type == 3) {
                $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
                $endTime = strtotime(date('Y-m-d 23:59:59'));
            }

            $where[] = ['create_time', 'between', [$startTime, $endTime]];
        } else {
            if ($date) {
                $startTime = strtotime($date . ' 00:00:00');
                $endTime = strtotime($date . ' 23:59:59');
                $where[] = ['create_time', 'between', [$startTime, $endTime]];
            }
        }

        $limit = 10;

        $listQuery = UserMoneyLog::where($where);

        if (!empty($lastId)) {
            $listQuery = $listQuery->where('id', '<', $lastId);
        }

        $list = $listQuery->order('id', 'desc')->limit($limit)->select()->toArray();

        $lastId = '';
        foreach ($list as $value) {
            $lastId = $value['id'];
        }

        $shouru = UserMoneyLog::where($where)->where('change_type', '=', 1)->sum('change_money');
        $zhichu = UserMoneyLog::where($where)->where('change_type', '=', 2)->sum('change_money');

        return $this->success('', [
            'list' => $list,
            'last_id' => (!empty($list) && count($list) == $limit) ? $lastId : '',
            'shouru' => number_format($shouru, 3),
            'zhichu' => number_format($zhichu, 3),
        ]);
    }

    /**
     * 获取提现列表
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getTxList()
    {
        $list = Withdraw::where(['user_id' => $this->userId])
            ->select()
            ->toArray();
        return $this->success('', [
            'list' => $list
        ]);
    }

    /**
     * 提现
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function submitWithDrawal()
    {
        $money = $this->request->post('money');
        $address = $this->request->post('address');
        $user = User::find($this->userId);
        if (empty($user)) {
            return $this->fail('用户不存在');
        }
        if ($user->user_money < (float)$money) {
            return $this->fail('提现数量大于可用余额');
        }
        $user->user_money -= (float)$money;
        $user->freeze_money += (float)$money;
        $user->save();
        $user = User::find($this->userId);
        UserMoneyLog::create([
            'user_id' => $this->userId,
            'type' => 8,
            'desc' => '提现扣除，进入冻结金额',
            'change_type' => 2,
            'change_money' => $money,
            'changed_money' => $user->user_money,
        ]);
        Withdraw::create([
            'user_id' => $this->userId,
            'money' => $money,
            'address' => $address,
            'order_no' => $this->generateOrderNo($this->userId, 1),
            'status' => 1
        ]);
        return $this->success();
    }

    /**
     * 生成唯一订单号
     * Author: Jarshs
     * 2025/5/1
     * @param $userId
     * @param $productType
     * @return string
     */
    private function generateOrderNo($userId, $productType = 1)
    {
        $prefix = [
            1 => 'T', // 提现
            2 => 'G', // 充值
            3 => 'C',  // 查询
            4 => 'J',  // 交易
            5 => 'L',  // 礼品卡
        ];

        return $prefix[$productType]
            . date('Ymd')
            . substr(str_pad($userId, 6, '0', STR_PAD_LEFT), -6)
            . mt_rand(100, 999);
    }

    /**
     * 获取客服信息
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     */
    public function getKf()
    {
        return $this->success('', [
            'kf_qrcode' => FileService::getFileUrl(ConfigService::get('website', 'kf_qrcode')),
            'kf_mobile' => ConfigService::get('website', 'kf_mobile', ''),
            'kf_time' => ConfigService::get('website', 'kf_time', ''),
        ]);
    }

    /**
     * 开通分钟
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function openSub()
    {
        $sub = Substation::where(['user_id' => $this->userId])->find();
        if ($sub) {
            return $this->fail('请不要重复开通');
        }
        $user = User::find($this->userId);
        if (!$user) {
            return $this->fail('用户不存在');
        }
        try {
            $price = ConfigService::get('website', 'substation_price', '');
            $user->user_money -= (float)$price;
            $user->save();
            $user = User::find($this->userId);
            UserMoneyLog::create([
                'user_id' => $this->userId,
                'type' => 7,
                'desc' => '开通分站扣除',
                'change_type' => 2,
                'change_money' => (float)$price,
                'changed_money' => $user['user_money']
            ]);
            Substation::create([
                'user_id' => $this->userId,
                'parent_user_id' => $user->p_first_user_id,
                'status' => 1
            ]);
            return $this->success();
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取分站信息
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSubstation()
    {
        $sub = Substation::where(['user_id' => $this->userId])->find();
        if (empty($sub)) {
            $sub['status'] = 0;
        }
//        $sub['status'] = $sub->status;

        return $this->success('', [
            'substation' => $sub,
            'open_substation_tips' => ConfigService::get('website', 'open_substation_tips', ''),
            'substation_price' => ConfigService::get('website', 'substation_price', '')
        ]);
    }

    /**
     * 获取邀请好友数据
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getInviteFriend()
    {
        $user = User::find($this->userId);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $fullDomain = $protocol . $_SERVER['HTTP_HOST'];
        $link = $fullDomain . '/mobile/#/?invite=' . $user->invite_code;
        if (!$user->invite_qrcode) {
            $qrCode = new QrCode($link);
            $writer = new PngWriter();

            $result = $writer->write($qrCode);
            $result->saveToFile($_SERVER['DOCUMENT_ROOT'] . '/uploads/qrcode/' . $user->invite_code . '.png');
            $user->invite_qrcode = '/uploads/qrcode/' . $user->invite_code . '.png';
            $user->save();
            $qrCode = FileService::getFileUrl('/uploads/qrcode/' . $user->invite_code . '.png');
        } else {
            $qrCode = FileService::getFileUrl($user->invite_qrcode);
        }

        return $this->success('', [
            'invite' => $user->invite_code,
            'link' => $link,
            'invite_logo' => FileService::getFileUrl(ConfigService::get('website', 'invite_logo')),
            'qrcode' => $qrCode,
        ]);
    }

    /**
     * 获取邀请页面数据
     * Author: Jarshs
     * 2025/5/1
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getInvite()
    {
        $user = User::find($this->userId);

//        $startTime = date('Y-m-d 00:00:00');
//        $endTime = date('Y-m-d 23:59:59');

        return $this->success('', [
            'invite' => $user->invite_code,
            'one_xj' => User::where(['p_first_user_id' => $this->userId])->count(),
            'two_xj' => User::where(['p_second_user_id' => $this->userId])->count(),
            'three_xj' => User::where(['p_three_user_id' => $this->userId])->count(),
            'share_rules' => ConfigService::get('website', 'share_rules', '')
        ]);
    }

    /**
     * 获取推广页数据
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserExtend()
    {
        $user = User::find($this->userId);
        if (!$user) {
            return $this->fail('用户不存在');
        }

        // 获取今天的开始和结束时间
        $startTime = strtotime(date('Y-m-d 00:00:00'));
        $endTime = strtotime(date('Y-m-d 23:59:59'));

        $todayTotal = UserMoneyLog::where(['user_id' => $this->userId, 'change_type' => 1, 'type' => 3])
            ->whereBetween('create_time', [$startTime, $endTime])
            ->sum('change_money');

        $totalSubordinates = User::where('p_first_user_id', $this->userId)
            ->whereOr('p_second_user_id', $this->userId)
            ->whereOr('p_three_user_id', $this->userId)
            ->count();

        $subList = User::where('p_first_user_id', $this->userId)
            ->field('avatar')
            ->paginate(3);
        foreach ($subList as &$sub) {
            $sub['avatar'] = FileService::getFileUrl($sub['avatar']);
        }

        $parent = User::where(['id' => $user->p_first_user_id])
            ->field('id,avatar,nickname')
            ->find();
        if ($parent) {
            $parentSubNums = User::where('p_first_user_id', $parent->id)
                ->whereOr('p_second_user_id', $parent->id)
                ->whereOr('p_three_user_id', $parent->id)
                ->count();
            $parent['xj_nums'] = $parentSubNums;
            $parent['avatar'] = FileService::getFileUrl($parent->avatar);
        }

        return $this->success('', [
            'todayTotal' => $todayTotal,
            'total_award_price' => $user->total_award_price,
            'totalSubordinates' => $totalSubordinates,
            'subList' => $subList,
            'share_tips' => ConfigService::get('website', 'share_tips', ''),
            'parent' => $parent,
        ]);
    }

    /**
     * 获取话费套餐列表
     *
     * @return Json
     */
    public function getMealList(): Json
    {
        $rate = ConfigService::get('website', 'reference_rate', '');
        if (empty($rate)) {
            return $this->fail('未获取到汇率');
        }

        $type = 1;
        $mealList = (new UserMealService())->getMealList($type, $this->userId, $rate);

        $newData = [];
        foreach ($mealList as $value) {
            $newData[] = [
                'id' => $value['id'],
                'price' => $value['price'],
                'name' => $value['name'],
                'discount' => $value['real_discount'],
                'discountedPrice' => $value['discounted_price'],
                'price2' => $value['price2'],
                'type' => $value['type'],
            ];
        }

        return $this->success('', [
            'list' => $newData,
            'reference_rate' => $rate
        ]);
    }

    /**
     * 获取电费套餐列表
     *
     * @return Json
     */
    public function getMealElectricityList(): Json
    {
        $rate = ConfigService::get('website', 'reference_rate', '');
        if (empty($rate)) {
            return $this->fail('未获取到汇率');
        }

        $type = 2;
        $mealList = (new UserMealService())->getMealList($type, $this->userId, $rate);

        $newData = [];
        foreach ($mealList as $value) {
            $newData[] = [
                'id' => $value['id'],
                'price' => $value['price'],
                'name' => $value['name'],
                'discount' => $value['real_discount'],
                'discountedPrice' => $value['discounted_price'],
                'price2' => $value['price2'],
                'type' => $value['type'],
            ];
        }

        return $this->success('', [
            'list' => $newData,
            'reference_rate' => $rate
        ]);
    }

    /**
     * 获取快速充值套餐列表
     *
     * @return Json
     */
    public function getMealQuicklyList(): Json
    {
        $rate = ConfigService::get('website', 'reference_rate', '');
        if (empty($rate)) {
            return $this->fail('未获取到汇率');
        }

        $type = 3;
        $mealList = (new UserMealService())->getMealList($type, $this->userId, $rate);

        $newData = [];
        foreach ($mealList as $value) {
            $newData[] = [
                'id' => $value['id'],
                'price' => $value['price'],
                'name' => $value['name'],
                'discount' => $value['real_discount'],
                'discountedPrice' => $value['discounted_price'],
                'price2' => $value['price2'],
                'type' => $value['type'],
            ];
        }

        return $this->success('', [
            'list' => $newData,
            'reference_rate' => $rate
        ]);
    }

    /**
     * 获取礼品卡配置
     *
     * @return Json
     */
    public function cardConf(): Json
    {
        $discount = ConfigService::get('website', 'card_discount', '');
        if (empty($discount) || $discount >= 10 || $discount <= 0) {
            $discount = '';
        }

        return $this->success('', [
            'discount' => $discount,
            'rate' => ConfigService::get('website', 'reference_rate', '')
        ]);
    }

    /**
     * 获取公告详情
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getNoticeDetail()
    {
        $id = $this->request->get('id');
        $info = Notice::find($id);
        if (empty($info)) {
            return $this->fail('数据不存在');
        }
        return $this->success('', [
            'info' => $info
        ]);
    }

    /**
     * 获取公告列表
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getNoticeList()
    {
        $list = Notice::where(['type' => 1])->order('id', 'desc')->select()->toArray();
        foreach ($list as &$v) {
            $v['content'] = $this->cleanHtml($v['content']);
        }
        return $this->success('', [
            'list' => $list
        ]);
    }

    /**
     * 去除html标签
     * Author: Jarshs
     * 2025/4/30
     * @param $input
     * @param $allowed_tags
     * @return string
     */
    private function cleanHtml($input, $allowed_tags = '')
    {
        // 去除HTML标签
        $output = strip_tags($input, $allowed_tags);

        // 可选：转换剩余HTML实体为普通字符
        return html_entity_decode($output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 获取首页公告列表
     * Author: Jarshs
     * 2025/4/30
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getIndexConfig()
    {
        $notice = Notice::where(['type' => 1])->select()->toArray();

        $discount = ConfigService::get('website', 'card_discount', '');
        if (empty($discount) || $discount >= 10 || $discount <= 0) {
            $discount = '';
        }

        return $this->success('', [
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'index_banner' => FileService::getFileUrl(ConfigService::get('website', 'index_banner')),
            'card_discount' => $discount,
            'notice' => $notice
        ]);
    }

    /**
     * 获取单个支付配置信息
     * Author: Jarshs
     * 2025/4/29
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getOnePayType()
    {
        $type = $this->request->get('type');
        $pay = UserPayType::where(['user_id' => $this->userId, 'type' => $type])->find();
        return $this->success('', [
            'info' => $pay,
        ]);
    }

    /**
     * 添加|编辑用户支付账号
     * Author: Jarshs
     * 2025/4/29
     * @return Json
     */
    public function addPayType()
    {
        $postData = $this->request->post();
        if ($postData['scenc'] == 'add') {
            UserPayType::create([
                'user_id' => $this->userId,
                'type' => $postData['type'],
                'name' => $postData['name'],
                'wechat' => $postData['wechat'],
                'qrcode' => $postData['qrcode'],
                'alipay' => $postData['alipay'],
                'bank_card' => $postData['bank_card'],
                'trc' => $postData['trc']
            ]);
        } else {
            UserPayType::where(['id' => $postData['id']])->update([
                'name' => $postData['name'],
                'wechat' => $postData['wechat'],
                'qrcode' => $postData['qrcode'],
                'alipay' => $postData['alipay'],
                'bank_card' => $postData['bank_card'],
                'trc' => $postData['trc']
            ]);
        }

        return $this->success();
    }

    /**
     * 获取用户支付账号列表
     * Author: Jarshs
     * 2025/4/29
     * @return Json
     */
    public function getUserPayType()
    {
        $wx = UserPayType::where(['user_id' => $this->userId, 'type' => 'wx'])->findOrEmpty();
        $alipay = UserPayType::where(['user_id' => $this->userId, 'type' => 'zfb'])->findOrEmpty();
        $card = UserPayType::where(['user_id' => $this->userId, 'type' => 'yhk'])->findOrEmpty();
        $usdt = UserPayType::where(['user_id' => $this->userId, 'type' => 'usdt'])->findOrEmpty();

        return $this->success('', [
            'wx' => $wx,
            'alipay' => $alipay,
            'card' => $card,
            'usdt' => $usdt
        ]);
    }

    /**
     * 确定注销账号
     * Author: Jarshs
     * 2025/4/10
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function cancelAccount()
    {
        $user = User::find($this->userId);
        if (!$user) {
            return $this->fail('用户不存在');
        }
        // 创建注销记录
        WriteOffUser::create([
            'user_id' => $this->userId,
            'nickname' => $user['nickname'],
            'email' => $user['account'],
        ]);
        // 删除用户
        $user->delete_time = time();
        $user->save();
        // 登录的时候禁止登录

        // 注册的时候禁止注册
        return $this->success();
    }

    /**
     * 修改用户邮件提醒配置
     * Author: Jarshs
     * 2025/4/9
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function changeEmailStatus()
    {
        $type = $this->request->post('type');
        $value = $this->request->post('value');
//        var_dump($value);
        $user = User::find($this->userId);
        if (!$user) {
            return $this->fail('用户不存在');
        }
        switch ($type) {
            case 'order_success_notice':
                $user->order_success_notice = $value === 'true' ? 1 : 0;
                break;
            case 'order_fail_notice':
                $user->order_fail_notice = $value === 'true' ? 1 : 0;
                break;
            default:
                break;
        }
        $user->save();
        return $this->success();
    }

    /**
     * 获取用户邮件提醒配置
     * Author: Jarshs
     * 2025/4/9
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getEmailStatus()
    {
        $user = User::find($this->userId);
        if (!$user) {
            return $this->fail('用户不存在');
        }
        return $this->success('', [
            'order_success_notice' => $user->order_success_notice ? 1 : 0,
            'order_fail_notice' => $user->order_fail_notice ? 1 : 0,
        ]);
    }

    /**
     * 修改昵称
     * Author: Jarshs
     * 2025/4/8
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function changeNickname()
    {
        $nickname = $this->request->post('nickname');
        if (empty($nickname)) {
            return $this->fail('昵称不能为空');
        }
        $user = User::find($this->userId);
        if (empty($user)) {
            return $this->fail('用户不存在');
        }
        $user->nickname = $nickname;
        $user->save();
        return $this->success();
    }

    /**
     * 修改头像
     * Author: Jarshs
     * 2025/4/8
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function changeAvatar()
    {
        $url = $this->request->post('url');
        if (empty($url)) {
            return $this->fail('头像不能为空');
        }
        $user = User::find($this->userId);
        if (empty($user)) {
            return $this->fail('用户不存在');
        }
        $user->avatar = $url;
        $user->save();
        return $this->success();
    }

    /**
     * 修改密码
     * Author: Jarshs
     * 2025/4/7
     * @return Json
     */
    public function changePwd()
    {
        $postData = $this->request->post();
        Db::startTrans();
        try {
            if (!self::verifyCode($postData['email'], $postData['code'])) {
                throw new \Exception('验证码错误或已过期');
            }

            $user = User::where('account', $postData['email'])->find();
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($postData['password'], $passwordSalt);

            $user->password = $password;
            $user->save();

            Db::commit();

            return $this->success();
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 退出登录
     * Author: Jarshs
     * 2025/4/7
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function logout()
    {
        LoginLogic::logout($this->userInfo);
        return $this->success();
    }

    /**
     * 获取用户信息
     * Author: Jarshs
     * 2025/4/6
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserInfo()
    {
        $user = User::where('id', $this->userId)->field('id,sn,avatar,nickname,account,user_money,invite_code,freeze_money')->find();
        $substation = Substation::where('user_id', $this->userId)->find();
        if ($substation) {
            $user['is_substation'] = true;
        } else {
            $user['is_substation'] = false;
        }
        $userPayTypes = UserPayType::where('user_id', $this->userId)->count();
        $user['pay_type_count'] = $userPayTypes;
        $user['user_money'] = substr($user['user_money'], 0, strpos($user['user_money'], '.') + 4);

        return $this->success('', [
            'info' => $user
        ]);
    }

    /**
     * 登录
     * Author: Jarshs
     * 2025/4/6
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function login()
    {
        $postData = $this->request->post();
        //账号安全机制，连续输错后锁定，防止账号密码暴力破解
        $userAccountSafeCache = new UserAccountSafeCache();
        if (!$userAccountSafeCache->isSafe()) {
            return '密码连续' . $userAccountSafeCache->count . '次输入错误，请' . $userAccountSafeCache->minute . '分钟后重试';
        }

        $user = User::where(['account' => $postData['email']])->findOrEmpty();
        if (!$user) {
            return $this->fail('用户不存在');
        }

        $writeOffUser = WriteOffUser::where('user_id', $this->userId)->find();
        if ($writeOffUser) {
            return $this->fail('用户已注销');
        }

        if ($user['is_disable'] === YesNoEnum::YES) {
            return $this->fail('用户已禁用');
        }

        $passwordSalt = Config::get('project.unique_identification');
        if ($user['password'] !== create_password($postData['password'], $passwordSalt)) {
            $userAccountSafeCache->record();
            return $this->fail('密码错误');
        }

        $userAccountSafeCache->relieve();

        //更新登录信息
        $user->login_time = time();
        $user->login_ip = request()->ip();
        $user->save();

        //设置token
        $userInfo = UserTokenService::setToken($user->id, 6);

        //返回登录信息
        $avatar = $user->avatar ?: Config::get('project.default_image.user_avatar');
        $avatar = FileService::getFileUrl($avatar);

        return $this->success('', [
            'nickname' => $userInfo['nickname'],
            'sn' => $userInfo['sn'],
            'mobile' => $userInfo['mobile'],
            'avatar' => $avatar,
            'token' => $userInfo['token'],
        ]);
    }

    /**
     * 注册账号
     * Author: Jarshs
     * 2025/4/6
     * @return Json
     */
    public function register()
    {
        $postData = $this->request->post();
        Db::startTrans();
        try {
            if (!self::verifyCode($postData['email'], $postData['code'])) {
                throw new \Exception('验证码错误或已过期');
            }

            $writeOffUser = WriteOffUser::where('email', $postData['email'])->find();
            if ($writeOffUser) {
                return $this->fail('用户已注销');
            }

            $user = User::where(['account' => $postData['email']])->find();
            if ($user) {
                return $this->fail('用户已存在');
            }

            $p_first_user_id = 0;
            $p_second_user_id = 0;
            $p_three_user_id = 0;
            $invite_code = User::generateInviteCode();

            if (!empty($postData['invite_code']) && $postData['invite_code'] != 'undefined') {
                $inviter = (new \app\common\model\user\User)->where('invite_code', $postData['invite_code'])->find();
                if (!$inviter) {
                    throw new \Exception('邀请码无效');
                }
                $p_first_user_id = $inviter->id;
                $p_second_user_id = $inviter->p_first_user_id;
                $p_three_user_id = $inviter->p_second_user_id;
            }

            $userSn = User::createUserSn();
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($postData['password'], $passwordSalt);
            $avatar = ConfigService::get('default_image', 'user_avatar');

            $res = User::create([
                'sn' => $userSn,
                'external_sn' => md5(time() . random_int(0, 100000000)),
                'avatar' => $avatar,
                'nickname' => '用户' . $userSn,
                'account' => $postData['email'],
                'password' => $password,
                'channel' => 6,
                'invite_code' => $invite_code,
                'p_first_user_id' => $p_first_user_id,
                'p_second_user_id' => $p_second_user_id,
                'p_three_user_id' => $p_three_user_id,
            ]);

            if (empty($res['id'])) {
                Db::rollback();
                return $this->fail('注册失败');
            }

            // 我的注册上级为分站站长时，所有话费电费等折扣默认为10折
            if (!empty($p_first_user_id)) {

                $substationInfo = SubstationLogic::info($p_first_user_id);
                if (!empty($substationInfo['id'])) {
                    $userMealLogic = new UserMealLogic();

                    // 获取套餐列表
                    $mealList = (new MealLogic())->getMealList();
                    if (!empty($mealList)) {
                        foreach ($mealList as $value) {
                            $tmpRes = $userMealLogic->addData($res['id'], $value['id'], 10);
                            if (!$tmpRes) {
                                Db::rollback();
                                return $this->fail('注册设置套餐失败');
                            }
                        }
                    }
                }
            }

            Db::commit();

            return $this->success();
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 发送验证码
     * Author: Jarshs
     * 2025/4/6
     * @return Json
     */
    public function sendCode()
    {
        $getData = $this->request->post();
        try {
            if (empty($getData['email'])) {
                throw new \Exception('邮箱不能为空');
            }
            if (!filter_var($getData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('邮箱格式不正确');
            }
            // 生成6位验证码
            $code = mt_rand(100000, 999999);
            $expireTime = time() + 300; // 5分钟后过期

            // 保存验证码到数据库
            EmailVerifyCode::create([
                'email' => $getData['email'],
                'code' => $code,
                'expire_time' => $expireTime,
                'created_at' => time()
            ]);

            // 发送邮件
            $mail = new PHPMailer(true);
            $config = [
                'host' => ConfigService::get('website', 'email_host', ''),    // SMTP服务器
                'username' => ConfigService::get('website', 'email_username', ''),    // 邮箱账号
                'password' => ConfigService::get('website', 'email_password', ''), // 邮箱授权码
                'port' => ConfigService::get('website', 'email_port', ''),              // SMTP端口
                'encryption' => ConfigService::get('website', 'email_encryption', ''),            // 加密方式
                'from' => ConfigService::get('website', 'email_from', ''),    // 发件人邮箱
                'from_name' => ConfigService::get('website', 'email_from_name', ''),      // 发件人名称
            ];

            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($config['from'], $config['from_name']);
            $mail->addAddress($getData['email']);

            $mail->isHTML(true);
            $mail->Subject = '您的验证码';
            $mail->Body = "您的验证码是：<b>{$code}</b>，5分钟内有效";

            $mail->send();

            return $this->success('验证码发送成功');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 验证邮箱验证码
     * Author: Jarshs
     * 2025/4/6
     * @param $email
     * @param $code
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function verifyCode($email, $code)
    {
        $record = EmailVerifyCode::where('email', $email)
            ->where('code', $code)
            ->where('expire_time', '>', time())
            ->order('id', 'desc')
            ->find();

        return !empty($record);
    }

    /**
     * 获取设置
     * Author: Jarshs
     * 2025/4/6
     * @return Json
     */
    public function getSetting()
    {
        return $this->success('', [
            'info' => [
                'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),
                'shop_name' => ConfigService::get('website', 'shop_name')
            ]
        ]);
    }

    /**
     * 获取隐私协议
     * Author: Jarshs
     * 2025/4/6
     * @return Json
     */
    public function getPrivacy()
    {
        $type = $this->request->get('type');
        if (!$type) {
            return $this->fail('参数不能为空');
        }
        if ($type == 1) {
            $title = ConfigService::get('agreement', 'service_title');
            $content = ConfigService::get('agreement', 'service_content');
        } elseif ($type == 2) {
            $title = ConfigService::get('agreement', 'privacy_title');
            $content = ConfigService::get('agreement', 'privacy_content');
        } elseif ($type == 3) {
            $title = ConfigService::get('agreement', 'safety_title');
            $content = ConfigService::get('agreement', 'safety_content');
        } else {
            return $this->fail('参数错误');
        }
        return $this->success('', [
            'title' => $title,
            'content' => $content,
        ]);
    }


    /**
     * @notes 首页数据
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:15
     */
    public function index()
    {
        $result = IndexLogic::getIndexData();
        return $this->data($result);
    }


    /**
     * @notes 全局配置
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:41
     */
    public function config()
    {
        $result = IndexLogic::getConfigData();
        return $this->data($result);
    }


    /**
     * @notes 政策协议
     * @return Json
     * @author 段誉
     * @date 2022/9/20 20:00
     */
    public function policy()
    {
        $type = $this->request->get('type/s', '');
        $result = IndexLogic::getPolicyByType($type);
        return $this->data($result);
    }


    /**
     * @notes 装修信息
     * @return Json
     * @author 段誉
     * @date 2022/9/21 18:37
     */
    public function decorate()
    {
        $id = $this->request->get('id/d');
        $result = IndexLogic::getDecorate($id);
        return $this->data($result);
    }


}
