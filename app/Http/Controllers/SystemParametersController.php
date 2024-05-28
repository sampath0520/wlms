<?php

namespace App\Http\Controllers;

use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Models\CoursesCurrency;
use App\Models\Currency;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SystemParametersController extends Controller
{
    public function getSystemParameters()
    {
        $systemParameters = Config::get('systemparameters');
        return ResponseHelper::success(trans('messages.record_fetched'), $systemParameters);
    }

    public function getCurrencies()
    {
        try {
            $currencies = Currency::orderBy('currency_name', 'asc')->get();
            if ($currencies) {
                return ResponseHelper::success(trans('messages.record_fetched'), $currencies);
            } else {
                return ResponseHelper::error(trans('messages.record_fetch_failed'));
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    //showHideCurrencies
    public function showHideCurrencies()
    {
        try {
            $currencyDisplay = ['currency_display' => 1]; // 1 display  0 hide
            return ResponseHelper::success(trans('messages.record_fetched'), $currencyDisplay);
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }


    public function getCourseCurrencies($id)
    {
        try {
            //course cuurencies with currency name
            $currencies = CoursesCurrency::with('currency')->where('course_id', $id)->get();
            if ($currencies) {
                return ResponseHelper::success(trans('messages.record_fetched'), $currencies);
            } else {
                return ResponseHelper::error(trans('messages.record_fetch_failed'));
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }
}
