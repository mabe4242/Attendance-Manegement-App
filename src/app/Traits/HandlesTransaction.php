<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HandlesTransaction
{
    public function handleTransaction(\Closure $callback, ?string $errorMessage = null)
    {
        try {
            return DB::transaction(function () use ($callback) {
                return $callback();
            });
        } catch (\Throwable $e) {
            // ログに記録
            report($e);

            return back()->with('error', $errorMessage ?? '処理中にエラーが発生しました。');
        }
    }
}
