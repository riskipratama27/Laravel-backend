<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if($id)
        {
            $transaction = Transaction::with(['items.product'])->find($id);

            if($transaction)
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            else
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada',
                    404
                );
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if($status)
            $transaction->where('status', $status);

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaksi berhasil diambil'
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:products,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status
        ]);
        
        foreach ($request->items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $product['quantity']
            ]);
                    // Konfigurasi midtrans
    //     Config::$serverKey = config('services.midtrans.serverKey');
    //     Config::$isProduction = config('services.midtrans.isProduction');
    //     Config::$isSanitized = config('services.midtrans.isSanitized');
    //     Config::$is3ds = config('services.midtrans.is3ds');

    //     $transaction = Transaction::with(['product','user'])->find($transaction->id);

    //     $midtrans = array(
    //         'transaction_details' => array(
    //             'order_id' =>  $transaction->id,
    //             'gross_amount' => (int) $transaction->total,
    //         ),
    //         'customer_details' => array(
    //             'first_name'    => $transaction->user->name,
    //             'email'         => $transaction->user->email
    //         ),
    //         'enabled_payments' => array('gopay','bank_transfer'),
    //         'vtweb' => array()
    //     );

    //     try {
    //         // Ambil halaman payment midtrans
    //         $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

    //         $transaction->payment_url = $paymentUrl;
    //         $transaction->save();

    //         // Redirect ke halaman midtrans
    //         return ResponseFormatter::success($transaction,'Transaksi berhasil');
    //     }
    //     catch (Exception $e) {
    //         return ResponseFormatter::error($e->getMessage(),'Transaksi Gagal');
    //     }
    }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction,'Transaksi berhasil diperbarui');
    }
}

