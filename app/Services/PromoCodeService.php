<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Orientation;
use App\Models\PaymentDetail;
use App\Models\PromoCode;
use App\Models\PromoCodeDiscount;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Issuing\Transaction;

class PromoCodeService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create PromoCode
     */

    public function createPromoCode($data)
    {
        try {

            DB::beginTransaction();

            $promoCode = new PromoCode();
            $promoCode->promo_code = $data['promo_code'];
            $promoCode->discount_type = $data['discount_type'];
            $promoCode->code_type = 1;
            $promoCode->user_id = 0;
            $promoCode->start_date = $data['start_date'];
            $promoCode->end_date = $data['expiration_date'];
            // $promoCode->course_id = $data['course_id'];
            $promoCode->is_one_time = $data['is_one_time'];
            $promoCode->status = AppConstants::ACTIVE;
            $promoCode->save();



            foreach ($data['price'] as $discount) {
                $promoCodeDiscount = new PromoCodeDiscount();
                $promoCodeDiscount->promo_code_id = $promoCode->id;
                $promoCodeDiscount->currency_id = $discount['currency_id'];
                $promoCodeDiscount->discount = $discount['amount'];
                // $promoCodeDiscount->discount_type = $discount['discount_type'];
                $promoCodeDiscount->status = AppConstants::ACTIVE;
                $promoCodeDiscount->save();
            }
            DB::commit();
            return ['status' => true, 'message' => 'Promo Code Created Successfully', 'data' => $promoCode];
        } catch (\Exception $e) {

            DB::rollback();
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetchAllPromoCodes
     */

    public function fetchAllPromoCodes()
    {
        try {
            $promoCodes = PromoCode::with('promoCodeDiscount', 'promoCodeDiscount.currency')->get();
            return $promoCodes;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * updatePromoCode
     */

    public function updatePromoCode($data)
    {
        try {
            DB::beginTransaction();
            $promoCode = PromoCode::find($data['id']);
            $promoCode->promo_code = $data['promo_code'];
            $promoCode->discount_type = $data['discount_type'];
            $promoCode->code_type = 1;
            $promoCode->user_id = 0;
            $promoCode->start_date = $data['start_date'];
            $promoCode->end_date = $data['expiration_date'];
            // $promoCode->course_id = $data['course_id'];
            $promoCode->is_one_time = $data['is_one_time'];
            $promoCode->status = AppConstants::ACTIVE;
            $promoCode->save();

            $promoCodeDiscounts = PromoCodeDiscount::where('promo_code_id', $data['id'])->get();
            foreach ($promoCodeDiscounts as $promoCodeDiscount) {
                $promoCodeDiscount->delete();
            }

            foreach ($data['price'] as $discount) {
                $promoCodeDiscount = new PromoCodeDiscount();
                $promoCodeDiscount->promo_code_id = $promoCode->id;
                $promoCodeDiscount->currency_id = $discount['currency_id'];
                $promoCodeDiscount->discount = $discount['amount'];
                // $promoCodeDiscount->discount_type = $discount['discount_type'];
                $promoCodeDiscount->status = AppConstants::ACTIVE;
                $promoCodeDiscount->save();
            }
            DB::commit();
            return ['status' => true, 'message' => 'Promo Code Updated Successfully'];
        } catch (\Exception $e) {
            DB::rollback();
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * activateDeactivatePromoCode
     */

    public function activateDeactivatePromoCode($data)
    {
        try {
            $promoCode = PromoCode::find($data['id']);
            $promoCode->status = $data['status'];
            $promoCode->save();
            return ['status' => true, 'message' => 'Promo Code Updated Successfully'];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    //check promo code is valid or not
    public function checkPromoCode($promoCode, $courseId, $currencyId)
    {
        try {

            //check promo code type
            $discountType = PromoCode::where('promo_code', $promoCode)->where('status', AppConstants::ACTIVE)
                // ->where('course_id', $courseId)
                ->where('start_date', '<=', date('Y-m-d'))
                ->where('end_date', '>=', date('Y-m-d'))
                ->first();

            if (!$discountType) {
                return false;
            }

            if ($discountType['discount_type'] == AppConstants::PERCENTAGE) {
                $currencyId = 0;
            }


            $promoCode = PromoCodeDiscount::where('promo_code_id', $discountType['id'])
                ->where('currency_id', $currencyId)
                ->where('status', AppConstants::ACTIVE)
                ->first();

            if (!$promoCode) {
                return false;
            }

            $promoCode['discount_type'] = $discountType['discount_type'];
            $promoCode['is_one_time'] = $discountType['is_one_time'];
            $promoCode['promo_code'] = $discountType['promo_code'];
            $promoCode['promocode_id'] = $discountType['id'];


            if ($promoCode) {
                return $promoCode;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //checkPromoCodeIsUsed
    public function checkPromoCodeIsUsed($userId, $promoCode,  $currencyId)
    {
        try {

            //check promo code type
            $discountType = PromoCode::where('promo_code', $promoCode)->where('status', AppConstants::ACTIVE)
                // ->where('course_id', $courseId)
                ->where('start_date', '<=', date('Y-m-d'))
                ->where('end_date', '>=', date('Y-m-d'))
                ->first();

            if (!$discountType) {
                return ['status' => false, 'message' => 'Promo Code is not valid'];
            }
            if ($discountType['discount_type'] == AppConstants::PERCENTAGE) {
                $currencyId = 0;
            }


            $promoCodeVal = PromoCodeDiscount::where('promo_code_id', $discountType['id'])
                ->where('currency_id', $currencyId)
                ->where('status', AppConstants::ACTIVE)
                ->first();


            // //get promocode details
            // $promoCodeVal = PromoCode::with('promoCodeDiscount')
            //     ->whereHas('promoCodeDiscount', function ($query) use ($currencyId) {
            //         $query->where('currency_id', $currencyId);
            //     })
            //     ->where('promo_code', $promoCode)
            //     ->where('status', AppConstants::ACTIVE)
            //     ->where('start_date', '<=', date('Y-m-d'))
            //     ->where('end_date', '>=', date('Y-m-d'))
            //     ->first();

            if (!$promoCodeVal) {
                return ['status' => false, 'message' => 'Promo Code is not valid'];
            }
            if ($discountType['is_one_time'] == 1) {

                $promoCodeUser = PaymentDetail::where('promo_code_id', $discountType['id'])->where('user_id', $userId)->first();
                if ($promoCodeUser) {
                    return ['status' => false, 'message' => 'Promo Code is already used'];
                }
            }

            $promoCodeVal['discount_type'] = $discountType['discount_type'];
            $promoCodeVal['is_one_time'] = $discountType['is_one_time'];
            $promoCodeVal['promo_code'] = $discountType['promo_code'];
            $promoCodeVal['promocode_id'] = $discountType['id'];

            return $promoCodeVal;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
