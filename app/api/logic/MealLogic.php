<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\SetMeal;
use think\Model;

/**
 * 充值金额
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class MealLogic extends BaseLogic
{
    /**
     * @param $type
     * @return array
     */
    public function getMealList($type): array
    {
        try {
            return SetMeal::where(['type' => $type])
                ->field('id,name,type,discount,price')
                ->order('sort','desc')
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }

    /**
     * @param $id
     * @return array
     */
    public function getMealInfo($id): array
    {
        try {
            return SetMeal::where(['id' => $id])
                ->order('sort','desc')
                ->field('id,name,type,discount,price')
                ->find()
                ->toArray();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }
}
