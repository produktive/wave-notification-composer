<?php

return [
    'team_name' => env('NOTIFICATION_TEAM_NAME', env('APP_NAME', 'Wave').' Team'),
    'team_avatar' => env('NOTIFICATION_TEAM_AVATAR', '/storage/demo/default.png'),
    'labels' => ['security' => 'Security', 'marketing' => 'Marketing', 'product_update' => 'Product update'],
    'label_styles' => [
        'security' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'marketing' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
        'product_update' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    ],
    'layout' => 'theme::components.layouts.app',
];
