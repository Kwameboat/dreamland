<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var float $topups */
/** @var int $platformCommission */
/** @var int $creatorEarnings */
/** @var int $pendingCount */
/** @var float $pendingAmount */
/** @var float $completedWithdrawals */
/** @var common\models\CreditPackageTransaction[] $recentTopups */
/** @var common\models\PurchasedVideo[] $recentUnlocks */

$this->title = 'Dreamland Revenue';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="dreamland-revenue-index">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Paystack top-ups, paywall commission, creator earnings, and withdrawal queue.</p>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-credit-card"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Paystack top-ups</span>
                    <span class="info-box-number">GHS <?= number_format($topups, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-line-chart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Platform commission</span>
                    <span class="info-box-number"><?= number_format($platformCommission) ?> credits</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Creator earnings</span>
                    <span class="info-box-number"><?= number_format($creatorEarnings) ?> credits</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-bank"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending withdrawals</span>
                    <span class="info-box-number"><?= (int) $pendingCount ?> · GHS <?= number_format($pendingAmount, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Completed payouts</h3>
        </div>
        <div class="box-body">
            <p><strong>GHS <?= number_format($completedWithdrawals, 2) ?></strong> paid out to creators.</p>
            <p>
                <?= Html::a('Review withdrawal requests', ['/withdrawal-payment'], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Legacy payments received', ['/payment'], ['class' => 'btn btn-default']) ?>
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">Recent Paystack top-ups</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Ref</th><th>Amount</th><th>Credits</th><th>When</th></tr></thead>
                        <tbody>
                        <?php if (!$recentTopups): ?>
                            <tr><td colspan="4">No completed top-ups yet.</td></tr>
                        <?php else: foreach ($recentTopups as $row): ?>
                            <tr>
                                <td><?= Html::encode($row->paystack_reference) ?></td>
                                <td><?= Html::encode($row->currency) ?> <?= number_format((float) $row->amount, 2) ?></td>
                                <td><?= (int) $row->credits_to_grant ?></td>
                                <td><?= Html::encode($row->completed_at ?: $row->created_at) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">Recent premium unlocks</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>Video</th><th>Paid</th><th>Creator</th><th>Platform</th></tr></thead>
                        <tbody>
                        <?php if (!$recentUnlocks): ?>
                            <tr><td colspan="4">No unlock purchases yet.</td></tr>
                        <?php else: foreach ($recentUnlocks as $row): ?>
                            <tr>
                                <td>#<?= (int) $row->video_id ?></td>
                                <td><?= (int) $row->credits_paid ?> cr</td>
                                <td><?= (int) $row->creator_credits ?> cr</td>
                                <td><?= (int) $row->platform_commission ?> cr</td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
