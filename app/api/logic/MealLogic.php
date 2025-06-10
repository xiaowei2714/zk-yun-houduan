<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\SetMeal;
use Exception;

/**
 * 充值金额
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class MealLogic extends BaseLogic
{
    /**
     * @return array
     */
    public function getMealList(): array
    {
        try {
            return SetMeal::field('id,name,type,discount,price')
                ->order('type','asc')
                ->order('sort','desc')
                ->select()
                ->toArray();
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }

    /**
     * @param $type
     * @return array
     */
    public function getMealListByType($type): array
    {
        try {
            return SetMeal::where(['type' => $type])
                ->field('id,name,type,discount,price')
                ->order('sort','desc')
                ->select()
                ->toArray();
        } catch (Exception $e) {
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
                ->findOrEmpty()
                ->toArray();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }
}
