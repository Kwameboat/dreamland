<?php
use yii\helpers\Html;

?>
<div class="box box-danger">
  <div class="box-header with-border">
    <h3 class="box-title">New Users</h3>

    <div class="box-tools pull-right">
      <span class="label label-danger">8 New Users</span>
      <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
      </button>
      <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i>
      </button>
    </div>
  </div>
  <!-- /.box-header -->
  <div class="box-body no-padding">
    <ul class="users-list clearfix">
      <?php

      // print_r($latestUsers);
      foreach ($latestUsers as $user) { ?>
        <li>

          <?= Html::img($user->imageUrl, ['alt' => $user->username,'style'=>'height:64px']); ?>
          <?= Html::a($user->username, ['/user/view', 'id' => $user->id], ['class' => 'users-list-name']); ?>
          <span class="users-list-date">
            <?php
            $epochTime = $user->created_at; // Replace with your epoch time
          

            $todayDate = date('Y-m-d', time());
            $epochDate = date('Y-m-d', $epochTime);

            if ($todayDate === $epochDate) {
              echo 'Today';
            } else {
              echo $epochDate = date('d M', $epochTime);
            }

            ?>
          </span>
        </li>
      <?php
      }
      ?>


    </ul>
    <!-- /.users-list -->
  </div>
  <!-- /.box-body -->
  <div class="box-footer text-center">

    <?= Html::a('View All users', ['/user'], ['class' => 'btn btn-sm btn-default btn-flat pull-right']); ?>
  </div>
  <!-- /.box-footer -->
</div>