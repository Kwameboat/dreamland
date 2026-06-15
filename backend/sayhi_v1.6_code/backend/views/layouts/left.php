<aside class="main-sidebar">
    <section class="sidebar">
        <?= backend\widgets\DreamlandMenu::widget([
            'options' => ['class' => 'sidebar-menu tree', 'data-widget' => 'tree'],
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'tachometer', 'url' => Yii::$app->homeUrl],
                ['label' => 'Administrators', 'icon' => 'user-secret', 'url' => ['/administrator'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::ADMINISTRATOR)],
                [
                    'label' => 'Users',
                    'icon' => 'users',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::USER),
                    'items' => [
                        ['label' => 'General Users (Viewers)', 'icon' => 'user', 'url' => ['/user']],
                        ['label' => 'User Verification', 'icon' => 'check-circle', 'url' => ['/user-verification']],
                        ['label' => 'Creator Genres', 'icon' => 'tags', 'url' => ['/user-profile-category']],
                    ],
                ],
                [
                    'label' => 'Content Creators',
                    'icon' => 'video-camera',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::USER),
                    'items' => [
                        ['label' => 'All Creators', 'icon' => 'list', 'url' => ['/content-creator/index']],
                        ['label' => 'Add Creator', 'icon' => 'user-plus', 'url' => ['/content-creator/create']],
                        ['label' => 'Active Creators', 'icon' => 'check-circle', 'url' => ['/content-creator/index', 'filter' => 'active']],
                        ['label' => 'Suspended / Banned', 'icon' => 'ban', 'url' => ['/content-creator/index', 'filter' => 'banned']],
                        ['label' => 'Pending Approval', 'icon' => 'clock-o', 'url' => ['/content-creator/index', 'filter' => 'pending']],
                        ['label' => 'Broadcast to Creators', 'icon' => 'bullhorn', 'url' => ['/broadcast-notification/create', 'audience' => 'creators'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::BROADCAST_NOTIFICATIONS)],
                    ],
                ],
                [
                    'label' => 'Reels',
                    'icon' => 'play-circle',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::REEL),
                    'items' => [
                        ['label' => 'Genres', 'icon' => 'tags', 'url' => ['/category', 'type' => 4]],
                        ['label' => 'All Reels', 'icon' => 'film', 'url' => ['/audio/post-reels']],
                    ],
                ],
                [
                    'label' => 'Content',
                    'icon' => 'file-text-o',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::POST),
                    'items' => [
                        ['label' => 'Posts', 'icon' => 'file-text-o', 'url' => ['/post']],
                        ['label' => 'Reported Posts', 'icon' => 'flag', 'url' => ['/post/reported-post']],
                    ],
                ],
                [
                    'label' => 'Live',
                    'icon' => 'youtube-play',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::LIVE_HISTORY),
                    'items' => [
                        ['label' => 'Live History', 'icon' => 'history', 'url' => ['/user-live-history']],
                    ],
                ],
                [
                    'label' => 'Dreamland',
                    'icon' => 'star',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::PAYMENT),
                    'items' => [
                        ['label' => 'Credit Packages', 'icon' => 'money', 'url' => ['/credit-package']],
                        ['label' => 'Appraisal Workspace', 'icon' => 'balance-scale', 'url' => ['/dreamland-appraisal']],
                        ['label' => 'AI Moderation Agent', 'icon' => 'android', 'url' => ['/dreamland-moderation']],
                        ['label' => 'Safety Queue', 'icon' => 'shield', 'url' => ['/dreamland-safety']],
                        ['label' => 'Dreamland Settings', 'icon' => 'cog', 'url' => ['/dreamland-settings']],
                        ['label' => 'Broadcast Notifications', 'icon' => 'bullhorn', 'url' => ['/broadcast-notification'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::BROADCAST_NOTIFICATIONS)],
                        ['label' => 'Notify viewers', 'icon' => 'paper-plane', 'url' => ['/broadcast-notification/create', 'audience' => 'viewers'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::BROADCAST_NOTIFICATIONS)],
                        ['label' => 'Notify creators', 'icon' => 'bullhorn', 'url' => ['/broadcast-notification/create', 'audience' => 'creators'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::BROADCAST_NOTIFICATIONS)],
                        ['label' => 'Payments Received', 'icon' => 'credit-card', 'url' => ['/payment']],
                        ['label' => 'Withdrawal Requests', 'icon' => 'bank', 'url' => ['/withdrawal-payment']],
                        ['label' => 'Completed Payouts', 'icon' => 'check-square-o', 'url' => ['/withdrawal-payment', 'type' => 'completed']],
                    ],
                ],
                ['label' => 'Support Requests', 'icon' => 'ticket', 'url' => ['/support-request'], 'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::SUPPORT_REQUEST)],
                [
                    'label' => 'Reports',
                    'icon' => 'bar-chart',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::REPORT),
                    'items' => [
                        ['label' => 'Reported Users', 'icon' => 'user-times', 'url' => ['/user/reported-user']],
                        ['label' => 'Reported Posts', 'icon' => 'flag', 'url' => ['/post/reported-post']],
                        ['label' => 'Reported Comments', 'icon' => 'comment', 'url' => ['/post-comment/reported-comment']],
                        ['label' => 'Blocked IPs', 'icon' => 'lock', 'url' => ['/blocked-ip']],
                    ],
                ],
                [
                    'label' => 'Settings',
                    'icon' => 'cog',
                    'url' => '#',
                    'visible' => Yii::$app->authPermission->can(Yii::$app->authPermission::SETTING),
                    'items' => [
                        ['label' => 'Contact Information', 'icon' => 'phone', 'url' => ['/setting']],
                        ['label' => 'General', 'icon' => 'info-circle', 'url' => ['/setting/general-information']],
                        ['label' => 'Payment', 'icon' => 'credit-card', 'url' => ['/setting/payment']],
                        ['label' => 'Storage', 'icon' => 'hdd-o', 'url' => ['/setting/storage']],
                        ['label' => 'Content Moderation', 'icon' => 'eye', 'url' => ['/setting/content-moderation']],
                        ['label' => 'Dreamland Settings', 'icon' => 'cog', 'url' => ['/dreamland-settings']],
                    ],
                ],
            ],
        ]) ?>
    </section>
</aside>
