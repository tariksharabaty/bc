<?php

use Illuminate\Support\Facades\Route;
use App\Models\PiggyBank;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/piggy-banks/{piggyBank}/qr-download', function (PiggyBank $piggyBank) {
        return response()->streamDownload(
            function () use ($piggyBank) {
                echo QrCode::size(300)->format('svg')->generate($piggyBank->unique_box_id);
            },
            "qr-{$piggyBank->unique_box_id}.svg",
            ['Content-Type' => 'image/svg+xml']
        );
    })->name('piggy-banks.qr-download');

    Route::get('/piggy-banks/{piggyBank}/qr-print', function (PiggyBank $piggyBank) {
        return view('qr-print', ['record' => $piggyBank]);
    })->name('piggy-banks.qr-print');
});

