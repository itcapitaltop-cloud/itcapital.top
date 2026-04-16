<?php

return [
    'strategies' => [
        'top_up_package' => \App\ActivityLog\Strategy\PackageTopUpStrategy::class,
        'package_purchased' => \App\ActivityLog\Strategy\PackageStagingPurchasedStrategy::class,
        'profit_accrued' => \App\ActivityLog\Strategy\PackageProfitAccruedStrategy::class,
        'start_bonus_package' => \App\ActivityLog\Strategy\StartBonusPackage::class,
        'regular_premium_package' => \App\ActivityLog\Strategy\RegularPremiumPackageStrategy::class,

        'admin_package_staking_changed_percentage' => \App\ActivityLog\Strategy\Admin\AdminPackageStakingPercentStrategy::class,
        'admin_package_purchased' => \App\ActivityLog\Strategy\Admin\AdminPackagePurchasedStrategy::class,
        'admin_package_changed_amount' => \App\ActivityLog\Strategy\Admin\AdminPackageChangedAmount::class,
        'admin_package_changed_percentage' => \App\ActivityLog\Strategy\Admin\AdminPackageChangedPercent::class,
        'admin_package_added_manual_profit' => \App\ActivityLog\Strategy\Admin\AdminPackageAddedManualProfit::class,
    ],
];
