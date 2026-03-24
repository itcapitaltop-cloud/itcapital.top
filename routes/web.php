<?php

use App\Helpers\Notify;
use App\Http\Controllers\AcademyNewsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLogActionController;
use App\Http\Controllers\CommonFundController;
use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\MainPageModalsController;
use App\Http\Controllers\Packages\ItcStakingController;
use App\Http\Controllers\WalletQrController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MoonShine\Http\Middleware\Authenticate;

$domain = config('app.domain');

Route::domain("academy.{$domain}")->group(function () {
    Route::view('/', 'academy.landing')->name('academy.landing');
    Route::get('/news', [AcademyNewsController::class, 'index'])->name('academy.news.index');
    Route::get('/news/{news}', [AcademyNewsController::class, 'show'])->name('academy.news.show');
    Route::view('/admin', 'academy.admin')->name('academy.admin');

    Route::fallback(function () {
        abort(404);
    });
});

/* страница «Проверьте почту» */
Route::get('/email/verify', static function () {
    if (auth()->check() && ! is_null(auth()->user()->email_verified_at)) {
        return redirect()->route('dashboard');
    }

    return view('pages.auth.verify-email');
})->middleware('auth')->name('verification.notice');

/* переход по ссылке из письма */
Route::get('/email/verify/{id}/{hash}', static function (EmailVerificationRequest $request) {
    $user = $request->user();

    if (is_null($user->email_verification_sent_at)) {
        return redirect()->route('email.verified');
    }

    $wasVerified = $user->hasVerifiedEmail();

    $request->fulfill();

    if (! $wasVerified) {
        Notify::welcome($user);
    }

    return redirect()->route('email.verified');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/email/change/verify/{user}/{hash}', EmailChangeController::class)
    ->middleware(['auth', 'signed'])
    ->name('email.change.verify');

/* повторная отправка письма */
Route::post('/email/verification-notification', static function (Request $request) {
    if (! is_null(auth()->user()->email_verification_sent_at)) {
        return back()->with('status', 'error-verification-link-sent');
    }

    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/email/verified', static function () {
    return view('pages.auth.email-verified');
})->middleware('auth')->name('email.verified');

Route::get('/', 'App\Http\Controllers\IndexController@index')->name('index');

Route::middleware('guest')->group(function () {
    Route::view('sign-up', 'pages.auth.sign-up')->name('sign-up');
    Route::view('login', 'pages.auth.login')->name('login');
    Route::view('email-restore', 'pages.auth.email-restore')->name('email-restore');
    Route::get('password/reset/{token}', static function (string $token) {
        return view('pages.auth.password-reset', ['token' => $token]);
    })->name('password.reset');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/auth/logout', 'App\Http\Controllers\AuthController@logout')->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/account/', 'pages.account.dashboard.index')->name('dashboard');
    Route::view('/account/finance', 'pages.account.finance.finance')->name('finance');
    Route::view('/account/partners', 'pages.account.partners.partners')->name('partners');
    Route::view('/account/itc', 'pages.account.itc.itc-packages')->name('itc-packages');
    Route::view('/account/itc-staking', 'pages.account.itc-staking.index')->name('itc-staking');
    Route::view('/account/wallet/deposit', 'pages.account.wallet.deposit')->name('deposit');
    Route::view('/account/wallet/withdraw', 'pages.account.wallet.withdraw')->name('withdraw');
    Route::view('/account/my-business', 'pages.account.my-business.index')->name('my-business');
    Route::get('/wallet/qr/{address}', WalletQrController::class)
        ->where(
            'address',
            '(0x[a-fA-F0-9]{40}'
            . '|T[1-9A-HJ-NP-Za-km-z]{33}'
            . '|[1-9A-HJ-NP-Za-km-z]{32,44})'
        )
        ->name('wallet.qr');

    Route::view('/account/news', 'pages.news.index')->name('news.index');
});

Route::controller(CommonFundController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/account/common-fund/buy', 'buyPackage')->name('common-fund-buy');
});

Route::controller(AdminController::class)->middleware(Authenticate::class)->prefix('itcapitalmoonshineadminpanel')->group(function () {
    Route::post('itc-packages/profits/mass', 'createItcPackagesProfits');
    Route::post('itc-packages/profits/recalculate', 'recalculate');
    Route::post('itc-staking/change/percentage', [ItcStakingController::class, 'changePercentage']);
    Route::post('itc-staking/change/start-bonus-percentage', [ItcStakingController::class, 'changeStartBonusPercentage'])->name('admin.itc-staking.change-start-bonus-percentage');
    Route::post('itc-staking/change/regular-percentage', [ItcStakingController::class, 'changeRegularPercentage'])->name('admin.itc-staking.change-regular-percentage');
    Route::post('itc-staking/package/staking/{uuid}', [ItcStakingController::class, 'editStaking']);
    Route::post('reinvest-profit/{uuid}/withdraw', 'withdrawOneProfitReinvest')->name('reinvest-profit-withdraw');
    Route::delete('reinvest-profit/{uuid}/delete', 'deleteProfitReinvest')->name('reinvest-profit-delete');
    Route::post('reinvest-profit/{uuid}/extend', 'extendProfitReinvest')->name('reinvest-profit-extend');
    Route::post('reinvest-profit/{uuid}/remove-all', 'removeAllProfitsAndReinvests')->name('reinvest-profit-remove-all');
    Route::post('reinvest-profit-withdraw/bulk', 'bulkWithdraw')->name('reinvest-profit-withdraw-bulk');
    Route::post('itc-packages/{uuid}/close', 'closeItcPackage')->name('itc-package-close');
    Route::post('users/amount', 'addAmountToBalance');
    Route::post('itc-packages/{uuid}', 'updateItcPackage');
    Route::post('withdraw/update', 'withdrawUpdate')->name('withdraw-update');
    Route::post('partners', 'addPartner')->name('add-partner');
    Route::post('partners/{partner_id}', 'updatePartner');
    Route::get('users/suggest', 'suggestUsers')->name('admin.users.suggest');
    Route::post('partners/rank', 'updateRank');
});

Route::controller(MainPageModalsController::class)
    ->middleware(Authenticate::class)
    ->prefix('itcapitalmoonshineadminpanel')
    ->group(function () {
        // HTML для модалок (лениво подгружается по asyncUrl)
        Route::get('modals/percents', 'percents')->name('modal.percents');
        Route::get('modals/requirements', 'requirements')->name('modal.requirements');
        Route::post('modals/percents/save', 'saveGlobalPercents')->name('modal.percents.save');
    });

Route::controller(AdminLogActionController::class)->middleware(Authenticate::class)->prefix('admin')->group(function () {
    Route::post('log-admin-action', 'withdrawOneProfitReinvest')->name('admin.log.store');
});
