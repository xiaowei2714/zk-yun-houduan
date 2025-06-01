<?php
declare (strict_types=1);

namespace app\common\command;

use app\common\model\notice\NoticeRecord;
use app\common\model\Recharge;
use app\common\model\RechargeApi;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
use app\common\service\ConfigService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;
use Exception;

class RechargeOrder extends Command
{
    const START_TIME = 30 * 60;
    private array $header = [];
    private $flag = '';

    protected function configure()
    {
        // 指令配置
        $this->setName('recharge_order')
            ->setDescription('检查支付订单成功');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $this->flag = time() . random_int(1000000, 9999999);
            Log::record('Info: command-RechargeOrder-' . $this->flag . ' Start');

            $this->handleData();

            Log::record('Info: command-RechargeOrder-' . $this->flag. ' End');

            return true;

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder-' . $this->flag . ' execute msg: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    private function handleData()
    {
        try {
            // 支付地址
            $confAddress = ConfigService::get('website', 'recharge_address');
            if (empty($confAddress)) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 未配置的收款地址');
                return false;
            }

            // 合约地址
            $configContract = Config::get('project.recharge_contract');
            if (empty($configContract)) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 未配置的合约地址');
                return false;
            }

            $data = $this->getPayOrder($confAddress, $configContract);
            if (empty($data)) {
                Log::record('Info: command-RechargeOrder-' . $this->flag . ' handleData msg: 获取不到交易数据');
                return false;
            }

            foreach ($data as $value) {
                if (empty($value['transaction_id'])) {
                    Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 不识别的交易ID:' . json_encode($value));
                    continue;
                }

                $payPrice = null;
                $info = RechargeApi::field('id,status,pay_price')->where('transaction_id', '=', $value['transaction_id'])->find();
                if (!empty($info['id'])) {
                    if ($info['status'] == 1) {
                        Log::record('Info: command-RechargeOrder-' . $this->flag . ' handleData msg: 已完成匹配，交易平台编号：【' . $value['transaction_id'] . '】');
                        continue;
                    }

                    $id = $info['id'];
                    $payPrice = $info['pay_price'];

                } else {
                    $res = RechargeApi::create($value);
                    if (!$res) {
                        Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 新增API数据失败：' . json_encode($value));
                        continue;
                    }

                    $id = $res['id'];
                }

                // 检查合约地址是否匹配
                if (strtolower($value['token_info_address']) !== strtolower($configContract)) {
                    Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 合约地址未匹配，交易平台编号：【' . $value['transaction_id'] . '】');
                    continue;
                }

                // 检查接收地址是否匹配
                if (strtolower($value['to']) !== strtolower($confAddress)) {
                    Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 接收地址未匹配，交易平台编号：【' . $value['transaction_id'] . '】');
                    continue;
                }

                // 检查金额(考虑代币小数位)
                if (empty($payPrice)) {
                    $tmpNum = (string)pow(10, $value['token_info_decimals']);
                    $payPrice = bcdiv($value['value'], $tmpNum, 3);
                    RechargeApi::where('id', '=', $id)->update([
                        'pay_price' => $payPrice
                    ]);
                }

                // 匹配金额
                $cacheKey = 'GEN_RECHARGE_ORDER_' . $payPrice;
                $res = Cache::get($cacheKey);
//                if ($value['transaction_id'] == 'f1ebf082ff35efc898566fcd7215ef37ccb2e4a61a4fe4390dc8816f62243d7a') {
//                    $res = 49;
//                } else {
//                    $res = null;
//                }

                if (empty($res) || !is_numeric($res)) {
                    Log::record('Error: command-RechargeOrder-' . $this->flag . ' handleData msg: 未匹配到该金额，交易平台编号：【' . $value['transaction_id']. '】交易金额：' . $payPrice);
                    continue;
                }

                // 设置为成功
                $this->setSuccess($value, $res, $id);
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder-' . $this->flag . ' handleData msg: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 设置为成功
     *
     * @param $apiInfo
     * @param $rechargeId
     * @param $rechargeApiId
     * @return bool
     */
    public function setSuccess($apiInfo, $rechargeId, $rechargeApiId): bool
    {
        try {
            Db::startTrans();

            // 获取充值详情
            $rechargeInfo = Recharge::where('id', $rechargeId)->find();
            if (empty($rechargeInfo['id'])) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 获取充值详情失败，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }
            if ($rechargeInfo['status'] == 2) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 当前订单已充值成功，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }

            // 更新充值表
            $rechargeParams = [
                'hash' => $apiInfo['transaction_id'],
                'status' => 2,
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = Recharge::where('id', $rechargeId)->where('status', '=', 1)->update($rechargeParams);
            if (empty($res)) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 更新充值订单表失败，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }

            // 更新充值API表
            $rechargeApiParams = [
                'status' => 1,
                'recharge_id' => $rechargeId,
                'update_time' => time()
            ];

            $res = RechargeApi::where('id', $rechargeApiId)->update($rechargeApiParams);
            if (empty($res)) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 更新充值API表失败，充值API表ID【' . $rechargeApiId . '】订单');
                Db::rollback();
                return false;
            }

            $addMoney = substr($rechargeInfo['pay_money'], 0, strpos($rechargeInfo['pay_money'], '.') + 4);

            // 增加用户余额
            $res = User::where('id', $rechargeInfo['user_id'])
                ->inc('user_money', (float)$addMoney)
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 增加用户余额失败，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $rechargeInfo['user_id'])->find();

            // 流水
            $billData = [
                'user_id' => $rechargeInfo['user_id'],
                'type' => 5,
                'desc' => '充值成功',
                'change_type' => 1,
                'change_money' => $addMoney,
                'changed_money' => $userInfo['user_money'],
                'source_sn' => $rechargeInfo['order_no']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 记录流水失败，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }

            // 消息通知
            $noticeData = [
                'user_id' => $rechargeInfo['user_id'],
                'title' => 'Y币充值成功提醒',
                'content' => '您于 ' . date('Y-m-d H:i:s') . ' 成功充值 ' . $rechargeInfo['pay_money'] . ' Y币',
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 3
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                Log::record('Error: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 充值消息失败，充值订单ID【' . $rechargeId . '】订单');
                Db::rollback();
                return false;
            }

            Log::record('Info: command-RechargeOrder-' . $this->flag . ' setSuccess msg: 充值成功，充值订单ID【' . $rechargeId . '】订单');

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder-' . $this->flag . ' setSuccess msg: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }

    /**
     * @return array|false
     */
    private function getPayOrder($confAddress, $configContract)
    {
        try {
            $url = 'https://api.trongrid.io/v1/accounts/' . $confAddress . '/transactions/trc20';

            $params = [
                'only_to' => 'true',
                'min_timestamp' => time() - self::START_TIME,
//                'min_timestamp' => 1746086006,
                'contract_address' => $configContract
            ];

            $data = $this->request($url, $params, 'GET');
            if (empty($data)) {
                return [];
            }

            if (is_string($data)) {
                $data = json_decode($data, true);
            }

            if (empty($data['data']) || !is_array($data['data'])) {
                return [];
            }

            $newData = [];
            foreach ($data['data'] as $value) {
                $newData[] = [
                    'transaction_id' => $value['transaction_id'] ?? null,
                    'block_timestamp' => $value['block_timestamp'] ?? null,
                    'from' => $value['from'] ?? null,
                    'to' => $value['to'] ?? null,
                    'type' => $value['type'] ?? null,
                    'value' => $value['value'] ?? null,
                    'token_info_symbol' => $value['token_info']['symbol'] ?? null,
                    'token_info_address' => $value['token_info']['address'] ?? null,
                    'token_info_decimals' => $value['token_info']['decimals'] ?? null,
                    'token_info_name' => $value['token_info']['name'] ?? null,
                ];
            }

            return $newData;

        } catch (Exception $e) {
            Log::record('Exception: command-RechargeOrder-' . $this->flag . ' getPayOrder msg: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @return bool|string
     * @throws Exception
     */
    private function request($url, array $params = [], string $method = 'GET')
    {
        if (!function_exists('curl_init')) {
            throw new Exception('缺失curl扩展');
        }

        $startTime = $this->micTime();
        $method = strtoupper($method);
        $curl = curl_init();
        $option = [
            CURLOPT_USERAGENT => 'XW',
            CURLOPT_CONNECTTIMEOUT => 0,    // 在发起连接前等待的时间，如果设置为0，则无限等待。
            CURLOPT_TIMEOUT => 10,          // 设置CURL允许执行的最长秒数
            CURLOPT_RETURNTRANSFER => true, // 在启用CURLOPT_RETURNTRANSFER的时候，返回原生的（Raw）输出
            CURLOPT_HEADER => false         // 启用时会将头文件的信息作为数据流输出
        ];

        if (strtolower(substr($url, 0, 5)) == 'https') {
            $option[CURLOPT_SSL_VERIFYPEER] = false;
            $option[CURLOPT_SSL_VERIFYHOST] = false;
        }

        if (!empty($this->getHeader())) {
            $option[CURLOPT_HTTPHEADER] = $this->getHeader();
        }

        switch ($method) {
            case 'GET':
                if (!empty($params)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?') . (is_array($params) ? http_build_query($params) : $params);
                }
                break;

            case 'POST':
                $option[CURLOPT_POST] = TRUE;
                if (!empty($params)) {
                    $option[CURLOPT_POSTFIELDS] = $params;
                }
                break;
        }

        $option[CURLOPT_URL] = $url;
        curl_setopt_array($curl, $option);
        $returnData = curl_exec($curl);
        curl_close($curl);

        $endTime = $this->micTime();

        // log record
        $log = ['params' => func_get_args(), 'response' => $returnData, 'start_time' => $startTime, 'end_time' => $endTime, 'total_time' => $endTime - $startTime];
        Log::record('Info: command-RechargeOrder-' . $this->flag . ' request msg: ' . json_encode($log));

        return $returnData;
    }

    /**
     * @return array
     */
    private function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @param array $header
     * @return $this
     */
    private function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return float
     */
    private function micTime(): float
    {
        list($msc, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msc) + floatval($sec)) * 1000);
    }
}
