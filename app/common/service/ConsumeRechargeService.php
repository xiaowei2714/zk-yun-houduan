<?php

namespace app\common\service;

use Exception;
use think\facade\Config;
use think\facade\Log;

class ConsumeRechargeService
{
    const USE_MOCK = true;
    protected array $header = [];

    /**
     * 获取手机余额
     *
     * @param $phone
     * @return array
     * @throws Exception
     */
    public function getPhoneBalance($phone): array
    {
        if (self::USE_MOCK) {
            return [
                'is_success' => true,
                'code' => 200,
                'msg' => '',
                'mobile' => '',
                'cur_fee' => '300.10',
                'area' => '',
                'isp' => '联通',
                'isp_id' => 2,
            ];
        }

        $configKey = Config::get('project.recharge_key');
        $url = 'https://ap.xiaoyun.top/api/xy/cx';

        $params = [
            'phone' => $phone,
            'key' => $configKey
        ];

        $data = $this->request($url, $params, 'POST');
        if (empty($data)) {
            return [];
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $code = $data['code'] ?? '';

        $ispId = null;
        $isp = $data['data']['isp'] ?? '';
        switch ($isp) {
            case '移动':
                $ispId = 1;
                break;

            case '联通':
                $ispId = 2;
                break;

            case '电信':
                $ispId = 3;
                break;

            case '虚拟':
                $ispId = 4;
                break;
        }

        return [
            'is_success' => $code == 200,
            'code' => $code,
            'msg' => $data['msg'] ?? '',
            'mobile' => $data['data']['mobile'] ?? '',
            'cur_fee' => $data['data']['curFee'] ?? '',
            'area' => $data['data']['area'] ?? '',
            'isp' => $isp,
            'isp_id' => $ispId
        ];
    }

    /**
     * 获取电量余额
     *
     * @param $account
     * @param $areaCode
     * @return array
     * @throws Exception
     */
    public function getElectricityBalance($account, $areaCode): array
    {
        if (self::USE_MOCK) {
            return [
                'is_success' => true,
                'code' => 200,
                'msg' => '',
                'balance' => 101.00,
                'owed_balance' => 200.50,
                'avail_balance' => 300.50,
            ];
        }

        $configKey = Config::get('project.recharge_key');
        $url = '/ap.xiaoyun.top/api/xy/dfcx';

        $params = [
            'account' => $account,
            'areaCode' => $areaCode,
            'key' => $configKey
        ];

        $data = $this->request($url, $params, 'POST');
        if (empty($data)) {
            return [];
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $code = $data['code'] ?? '';

        return [
            'is_success' => $code == 200,
            'code' => $code,
            'msg' => $data['msg'] ?? '',
            'balance' => $data['data']['balance'] ?? 0,
            'owed_balance' => $data['data']['owedBalance'] ?? 0,
            'avail_balance' => $data['data']['availableBalance'] ?? 0,
        ];
    }

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @return bool|string
     * @throws Exception
     */
    protected function request($url, array $params = [], string $method = 'GET')
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
                    $option[CURLOPT_POSTFIELDS] = json_encode($params);
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
        $log = json_encode($log);
        Log::record('request: ' . $log);

        return $returnData;
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header)
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
