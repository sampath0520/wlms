<?php



namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class PaymentGatewayResponse extends Model
{
    protected $table = 'payment_gateway_responses';
    protected $casts = [
        'response' => 'array',
    ];
    public $timestamps = true;
    protected $fillable = [
        'transaction_id',
        'response'
    ];
}
