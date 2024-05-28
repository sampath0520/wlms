<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ResponseHelper;
use App\Http\Requests\PromoCodeCreateRequest;
use App\Http\Requests\PromoCodeUpdateRequest;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    protected $promoCodeService;
    public function __construct(PromoCodeService $orientationService)
    {
        $this->promoCodeService = $orientationService;
    }


    /**
     * create promocode
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function createPromoCode(PromoCodeCreateRequest $request)
    {
        $validated = $request->validated();

        $response = $this->promoCodeService->createPromoCode($validated);

        if ($response['status']) {
            return ResponseHelper::success($response['message'], $response['data']);
        } else {
            return ResponseHelper::error($response['message']);
        }
    }

    /**
     * fetchAllPromoCodes
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchAllPromoCodes()
    {
        $promoCodes = $this->promoCodeService->fetchAllPromoCodes();
        if ($promoCodes) {
            return ResponseHelper::success(trans('messages.record_fetched'), $promoCodes);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * updatePromoCode
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function updatePromoCode(PromoCodeUpdateRequest $request)
    {
        $validated = $request->validated();
        $response = $this->promoCodeService->updatePromoCode($validated);
        if ($response['status']) {
            return ResponseHelper::success($response['message']);
        } else {
            return ResponseHelper::error($response['message']);
        }
    }

    /**
     * activateDeactivatePromoCode
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function activateDeactivatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:promo_codes,id',
            'status' => 'required|integer|in:0,1'
        ]);

        $response = $this->promoCodeService->activateDeactivatePromoCode($validated);
        if ($response['status']) {
            return ResponseHelper::success($response['message']);
        } else {
            return ResponseHelper::error($response['message']);
        }
    }
}
