<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $wallet): View
    {
        $user = $request->user();

        return view('portal.wallet.index', [
            'balance' => $wallet->balance($user),
            'transactions' => $wallet->transactions($user),
            'hasBankAccount' => $user->hasBankAccount(),
        ]);
    }
}
