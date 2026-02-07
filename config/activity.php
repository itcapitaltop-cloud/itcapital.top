<?php
return [
    'strategies' => [
        'package_purchased' => \App\ActivityLog\Strategy\PackageStagingPurchasedStrategy::class,
        'profit_accrued' => \App\ActivityLog\Strategy\PackageProfitAccruedStrategy::class,
        'admin_package_staking_changed_percentage' => \App\ActivityLog\Strategy\Admin\AdminPackageStakingPercentStrategy::class,
        'admin_package_purchased' => \App\ActivityLog\Strategy\Admin\AdminPackagePurchasedStrategy::class,
        'admin_package_changed_amount' => \App\ActivityLog\Strategy\Admin\AdminPackageChangedPercent::class,
        'admin_package_changed_percentage' => \App\ActivityLog\Strategy\Admin\AdminPackageChangedPercent::class,
    ],
];
