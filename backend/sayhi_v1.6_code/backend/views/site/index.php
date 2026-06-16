<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\assets\ChartJsAsset;

ChartJsAsset::register($this);

/* @var $this yii\web\View */

$this->title = 'Dashboard';
$this->registerJs('document.body.classList.add("dreamland-dashboard");', \yii\web\View::POS_READY);
?>

<div class="site-index dreamland-dashboard">

  <div class="dl-dashboard-hero">
    <img src="<?= Url::to('@web/img/logo.svg') ?>" alt="Dreamland" class="dl-dashboard-hero__logo">
    <div>
      <h2 class="dl-dashboard-hero__title">Welcome to <span>Dreamland</span></h2>
      <p class="dl-dashboard-hero__subtitle">Play, Watch, Earn — your command center for creators, viewers, and live content.</p>
    </div>
    <div class="dl-dashboard-hero__actions">
      <?= Html::a('<i class="fa fa-bullhorn"></i> Broadcast', ['/broadcast-notification/create'], ['class' => 'dl-quick-btn']) ?>
      <?= Html::a('<i class="fa fa-video"></i> Creators', ['/content-creator'], ['class' => 'dl-quick-btn']) ?>
      <?= Html::a('<i class="fa fa-cog"></i> Settings', ['/dreamland-settings'], ['class' => 'dl-quick-btn']) ?>
    </div>
  </div>

  <div class="dl-section-label">Platform overview</div>

  <div class="row dl-stats-row">
  <?php 
        if(Yii::$app->authPermission->can(Yii::$app->authPermission::USER)){
        ?>
    <div class="col-lg-3 col-xs-6">

      <div class="info-box">
        <!-- Apply any bg-* class to to the icon to color it -->
        
        <span class="info-box-icon bg-aqua"><i class="fa fa-user"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Users</span>
          <span class="info-box-number">
            <?= $userCount ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/user']); ?>" class="info-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>
    <?php 
        }
        ?>
    <!-- ./col -->
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::POST)){
    ?>
    <div class="col-lg-3 col-xs-6" >
      <div class="info-box">
        <span class="info-box-icon bg-red"><i class="fa fa-video-camera"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Posts</span>
          <span class="info-box-number">
            <?= $totalPost ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/post']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->

    </div>
    <?php 
    }
    ?>
    <!-- ./col -->
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::REEL)){
    ?>
    <div class="col-lg-3 col-xs-6">




      <div class="info-box">
        <span class="info-box-icon bg-green"><i class="fa  fa-play-circle"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Reels</span>
          <span class="info-box-number">
            <?= $reelCount ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/audio/post-reels']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->

    </div>
    <?php 
    }
    ?>
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::CLUB)){
    ?>
    <div class="col-lg-3 col-xs-6">

      <div class="info-box">
        <span class="info-box-icon bg-yellow"><i class="fa  fa-users"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Clubs</span>
          <span class="info-box-number">
            <?= $clubCount ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/club']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->


    </div>
    <?php 
    }
    ?>
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::EVENT)){
    ?>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6">
      <!-- small box -->

      <div class="info-box">
        <!-- Apply any bg-* class to to the icon to color it -->
        <span class="info-box-icon bg-yellow"><i class="fa fa-list-alt"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Events</span>
          <span class="info-box-number">
            <?= $eventCount ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/event']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->


    </div>
    <?php 
    }
    ?>
    <!-- ./col -->
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::COMPETITION)){
    ?>
    <div class="col-lg-3 col-xs-6">
      <div class="info-box">
        <!-- Apply any bg-* class to to the icon to color it -->
        <span class="info-box-icon bg-green"><i class="fa fa-trophy"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Competitions</span>
          <span class="info-box-number">
            <?= $totalCompetition ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/competition']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->

    </div>
    <?php 
    }
    ?>
    <!-- ./col -->
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::COUPON)){
    ?>
    <div class="col-lg-3 col-xs-6">

      <div class="info-box">

        <span class="info-box-icon bg-aqua"><i class="fa fa-gift"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Coupons</span>
          <span class="info-box-number">
            <?= $couponCount ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/coupon']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->
    </div>
    <?php 
    }
    ?>
    <!-- ./col -->
    <?php 
    if(Yii::$app->authPermission->can(Yii::$app->authPermission::STORY)){
    ?>
    <div class="col-lg-3 col-xs-6">

      <div class="info-box">

        <span class="info-box-icon bg-red"><i class="fa fa-history"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Story</span>
          <span class="info-box-number">
            <?= $totalStory ?>
          </span>
          <span class="info-box-link">
            <a href="<?= Url::to(['/story']); ?>" class="small-box-footer">More info <i
                class="fa fa-arrow-circle-right"></i></a>
          </span>
        </div><!-- /.info-box-content -->
      </div><!-- /.info-box -->

    </div>
    <?php 
    }
    ?>
    <!-- ./col -->
  </div>
  <!--old -->


  <div class="body-content">

    <div class="dl-section-label">Analytics &amp; trends</div>

    <div class="row">
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
          <i class="fa fa-bar-chart-o"></i>
            <h3 class="box-title">Annual Reports</h3>

            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
              </button>

            </div>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
            <div class="row">

              <!--start -->
             
              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::POST)) ?'block':'none'?>">
                <!-- AREA CHART -->
                <div class="box box-primary">
                  <div class="box-header with-border">
                    <h3 class="box-title">Posts</h3>

                  
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="areaChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->



              </div>
             

              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::USER)) ?'block':'none'?>">

                <!-- BAR CHART -->
                <div class="box box-success">
                  <div class="box-header with-border">
                    <h3 class="box-title">Users</h3>

                    <!--  <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>-->
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="barChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->

              </div>

              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::CLUB)) ?'block':'none'?>">
                <!-- AREA CHART -->
                <div class="box box-primary">
                  <div class="box-header with-border">
                    <h3 class="box-title">Clubs</h3>

                    <!--<div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>-->
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="clubareaChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->



              </div>

              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::PAYMENT)) ?'block':'none'?>">

                <!-- BAR CHART -->
                <div class="box box-success">
                  <div class="box-header with-border">
                    <h3 class="box-title">Payments</h3>

                    <!--  <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>-->
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="paymentbarChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->

              </div>

              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::REEL)) ?'block':'none'?>">
                <!-- AREA CHART -->
                <div class="box box-primary">
                  <div class="box-header with-border">
                    <h3 class="box-title">Reels</h3>

                    <!--<div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>-->
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="reelareaChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->



              </div>

              <div class="col-md-4" style="display:<?= (Yii::$app->authPermission->can(Yii::$app->authPermission::STORY)) ?'block':'none'?>">

                <!-- BAR CHART -->
                <div class="box box-success">
                  <div class="box-header with-border">
                    <h3 class="box-title">Story</h3>

                    <!--  <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>-->
                  </div>
                  <div class="box-body">
                    <div class="chart">
                      <canvas id="storybarChart" style="height:250px"></canvas>
                    </div>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->

              </div>




              <!-- /.col -->
            </div>
            <!-- /.row -->
          </div>
          <!-- ./box-body -->

          <!-- /.box-footer -->
        </div>
        <!-- /.box -->
      </div>
      <!-- /.col -->
    </div>

  



    <div class="dl-section-label">Recent activity</div>

    <div class="row">
        <?php 
        if(Yii::$app->authPermission->can(Yii::$app->authPermission::POST)){
        ?>
        <div class="col-md-8">
             <!-- TABLE: LATEST ORDERS -->
          <?= $this->render('_latestPost', [
                    'postLatest'=>$postLatest
                    
                ]) ?>
          <!-- /.box -->
        </div>
        <?php 
        }
        ?>
        <!-- /.col -->
        <?php 
        if(Yii::$app->authPermission->can(Yii::$app->authPermission::USER)){
        ?>

        <div class="col-md-4">
           

       

          <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa  fa-envelope-o"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">Support Requests</span>
              <span class="info-box-number"><?=$support['totalSupport']?></span>

              <div class="progress">
                <div class="progress-bar" style="width: <?=$support['percentage']?>%"></div>
              </div>
              <span class="progress-description">
             <b><?=$support['totalPendingSupport']?></b> Requests is pending for reply
                  </span>
            </div>
            <!-- /.info-box-content -->
          </div>

          <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-file-video-o"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">Users Live History</span>
              <span class="info-box-number"><?=$liveHistory['totallive']?></span>

              <div class="progress">
                <div class="progress-bar" style="width:  <?=$liveHistory['percentage']?>%"></div>
              </div>
              <span class="progress-description">
              <b><?=$liveHistory['totalCurrentLive']?> Users live now</b>
                  </span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa  fa-usd"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">Payments</span>
              <span class="info-box-number">$<?=$earnings['totalEarning']?></span>

              <div class="progress">
                <div class="progress-bar" style="width: <?=$earnings['lastMonthPercentage']?>%"></div>
              </div>
              <span class="progress-description">
                $<?=$earnings['totalEarningLastMonth']?> in 30 Days
                  </span>
            </div>
            <!-- /.info-box-content -->
          </div>

          <?= $this->render('_latestUsers', [
                'latestUsers'=>$latestUsers
                    
            ]) ?>
       
        </div>
        <?php 
        }
        ?>
        <!-- /.col -->
    </div>


  </div>
</div>

<?php

//print_r($adGraph['data']);
?>

<!-- page script -->
<script>
  $(function () {

    var dlChartFill = 'rgba(255, 0, 85, 0.35)';
    var dlChartStroke = 'rgba(255, 0, 85, 0.95)';
    var dlChartPoint = '#ff0055';
    var dlChartBar = '#ff0055';

    var dataArrRow = <?php echo json_encode($firstGraph['data']); ?>;
    var dataLable = <?php echo json_encode($firstGraph['dataCaption']); ?>;
    dataAdsArr = [];
    dataArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataAdsArr.push(value);
    });

    /* ChartJS
     * -------
     * Here we will create a few charts using ChartJS
     */

    //--------------
    //- AREA CHART -
    //--------------

    // Get context with jQuery - using jQuery's .get() method.
    var areaChartCanvas = $('#areaChart').get(0).getContext('2d')

    var areaChart = new Chart(areaChartCanvas);


    //dataArr           = [28, 48, 40, 19, 50, 27, 90];
    //console.log(dataArr);

    var areaChartData = {
      labels: dataLable,
      datasets: [

        {
          label: 'Posts',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataAdsArr


        }
      ]
    }

    var areaChartOptions = {
      //Boolean - If we should show the scale at all
      showScale: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: false,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - Whether the line is curved between points
      bezierCurve: true,
      //Number - Tension of the bezier curve between points
      bezierCurveTension: 0.3,
      //Boolean - Whether to show a dot for each point
      pointDot: false,
      //Number - Radius of each point dot in pixels
      pointDotRadius: 4,
      //Number - Pixel width of point dot stroke
      pointDotStrokeWidth: 1,
      //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
      pointHitDetectionRadius: 20,
      //Boolean - Whether to show a stroke for datasets
      datasetStroke: true,
      //Number - Pixel width of dataset stroke
      datasetStrokeWidth: 2,
      //Boolean - Whether to fill the dataset with a color
      datasetFill: true,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
      maintainAspectRatio: true,
      //Boolean - whether to make the chart responsive to window resizing
      responsive: true
    }

    //Create the line chart
    areaChart.Line(areaChartData, areaChartOptions)

    //-------------
    //- Story CHART -  Story
    //-------------


    var dataStoryArrRow =<?php echo json_encode($storyGraph['data']); ?>;
    var dataStoryLable =<?php echo json_encode($storyGraph['dataCaption']); ?>;
    dataStoryArr = [];
    dataStoryArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataStoryArr.push(value);
    });

    var barChartData = {
      labels: dataStoryLable,
      datasets: [

        {
          label: 'Data',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataStoryArrRow
        }
      ]
    }


    var barChartCanvas = $('#storybarChart').get(0).getContext('2d')
    var barChart = new Chart(barChartCanvas)
    var barChartData = barChartData
    barChartData.datasets[0].fillColor = dlChartBar
    barChartData.datasets[0].strokeColor = dlChartBar
    barChartData.datasets[0].pointColor = dlChartBar
    var barChartOptions = {
      //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
      scaleBeginAtZero: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: true,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - If there is a stroke on each bar
      barShowStroke: true,
      //Number - Pixel width of the bar stroke
      barStrokeWidth: 2,
      //Number - Spacing between each of the X value sets
      barValueSpacing: 5,
      //Number - Spacing between data sets within X values
      barDatasetSpacing: 1,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to make the chart responsive
      responsive: true,
      maintainAspectRatio: true
    }

    barChartOptions.datasetFill = false;
    barChart.Bar(barChartData, barChartOptions)

    

    //-------------
    //- BAR CHART -  Payment
    //-------------


    var dataPaymentArrRow =<?php echo json_encode($paymentGraph['data']); ?>;
    var dataPaymentLable =<?php echo json_encode($paymentGraph['dataCaption']); ?>;
    dataPaymentArr = [];
    dataPaymentArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataPaymentArr.push(value);
    });

    var barChartData = {
      labels: dataPaymentLable,
      datasets: [

        {
          label: 'Data',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataPaymentArrRow
        }
      ]
    }


    var barChartCanvas = $('#paymentbarChart').get(0).getContext('2d')
    var barChart = new Chart(barChartCanvas)
    var barChartData = barChartData
    barChartData.datasets[0].fillColor = dlChartBar
    barChartData.datasets[0].strokeColor = dlChartBar
    barChartData.datasets[0].pointColor = dlChartBar
    var barChartOptions = {
      //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
      scaleBeginAtZero: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: true,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - If there is a stroke on each bar
      barShowStroke: true,
      //Number - Pixel width of the bar stroke
      barStrokeWidth: 2,
      //Number - Spacing between each of the X value sets
      barValueSpacing: 5,
      //Number - Spacing between data sets within X values
      barDatasetSpacing: 1,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to make the chart responsive
      responsive: true,
      maintainAspectRatio: true
    }

    barChartOptions.datasetFill = false;
    barChart.Bar(barChartData, barChartOptions)

    // users data 
    var dataUserArrRow =<?php echo json_encode($userGraph['data']); ?>;
    var dataUserLable =<?php echo json_encode($userGraph['dataCaption']); ?>;
    dataUserArr = [];
    dataUserArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataUserArr.push(value);
    });

    var barChartData = {
      labels: dataUserLable,
      datasets: [

        {
          label: 'Data',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataUserArrRow
        }
      ]
    }


    var barChartCanvas = $('#barChart').get(0).getContext('2d')
    var barChart = new Chart(barChartCanvas)
    var barChartData = barChartData
    barChartData.datasets[0].fillColor = dlChartBar
    barChartData.datasets[0].strokeColor = dlChartBar
    barChartData.datasets[0].pointColor = dlChartBar
    var barChartOptions = {
      //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
      scaleBeginAtZero: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: true,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - If there is a stroke on each bar
      barShowStroke: true,
      //Number - Pixel width of the bar stroke
      barStrokeWidth: 2,
      //Number - Spacing between each of the X value sets
      barValueSpacing: 5,
      //Number - Spacing between data sets within X values
      barDatasetSpacing: 1,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to make the chart responsive
      responsive: true,
      maintainAspectRatio: true
    }

    barChartOptions.datasetFill = false;
    barChart.Bar(barChartData, barChartOptions)

    // club data

    var dataClubArrRow =<?php echo json_encode($clubGraph['data']); ?>;
    var dataLable =<?php echo json_encode($clubGraph['dataCaption']); ?>;
    dataAdsArr = [];
    dataClubArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataAdsArr.push(value);
    });

    /* ChartJS
     * -------
     * Here we will create a few charts using ChartJS
     */

    //--------------
    //- AREA CHART -
    //--------------

    // Get context with jQuery - using jQuery's .get() method.
    var areaChartCanvas = $('#clubareaChart').get(0).getContext('2d')

    var areaChart = new Chart(areaChartCanvas);


    //dataArr           = [28, 48, 40, 19, 50, 27, 90];
    //console.log(dataArr);

    var areaChartData = {
      labels: dataLable,
      datasets: [

        {
          label: 'Posts',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataAdsArr


        }
      ]
    }

    var areaChartOptions = {
      //Boolean - If we should show the scale at all
      showScale: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: false,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - Whether the line is curved between points
      bezierCurve: true,
      //Number - Tension of the bezier curve between points
      bezierCurveTension: 0.3,
      //Boolean - Whether to show a dot for each point
      pointDot: false,
      //Number - Radius of each point dot in pixels
      pointDotRadius: 4,
      //Number - Pixel width of point dot stroke
      pointDotStrokeWidth: 1,
      //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
      pointHitDetectionRadius: 20,
      //Boolean - Whether to show a stroke for datasets
      datasetStroke: true,
      //Number - Pixel width of dataset stroke
      datasetStrokeWidth: 2,
      //Boolean - Whether to fill the dataset with a color
      datasetFill: true,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
      maintainAspectRatio: true,
      //Boolean - whether to make the chart responsive to window resizing
      responsive: true
    }

    //Create the line chart
    areaChart.Line(areaChartData, areaChartOptions)

    /**
     * Reels Data line chart
     */

    var dataReelsArrRow =<?php echo json_encode($reelsGraph['data']); ?>;
    var dataLable =<?php echo json_encode($reelsGraph['dataCaption']); ?>;
    dataAdsArr = [];
    dataReelsArrRow.forEach(function (item) {
      var value = parseInt(item);
      dataAdsArr.push(value);
    });

    // Get context with jQuery - using jQuery's .get() method.
    var areaChartCanvas = $('#reelareaChart').get(0).getContext('2d')

    var areaChart = new Chart(areaChartCanvas);

    var areaChartData = {
      labels: dataLable,
      datasets: [

        {
          label: 'Posts',
          fillColor: dlChartFill,
          strokeColor: dlChartStroke,
          pointColor: dlChartPoint,
          pointStrokeColor: dlChartStroke,
          pointHighlightFill: '#fff',
          pointHighlightStroke: dlChartStroke,
          data: dataAdsArr


        }
      ]
    }

    var areaChartOptions = {
      //Boolean - If we should show the scale at all
      showScale: true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines: false,
      //String - Colour of the grid lines
      scaleGridLineColor: 'rgba(255,255,255,.08)',
      //Number - Width of the grid lines
      scaleGridLineWidth: 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines: true,
      //Boolean - Whether the line is curved between points
      bezierCurve: true,
      //Number - Tension of the bezier curve between points
      bezierCurveTension: 0.3,
      //Boolean - Whether to show a dot for each point
      pointDot: false,
      //Number - Radius of each point dot in pixels
      pointDotRadius: 4,
      //Number - Pixel width of point dot stroke
      pointDotStrokeWidth: 1,
      //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
      pointHitDetectionRadius: 20,
      //Boolean - Whether to show a stroke for datasets
      datasetStroke: true,
      //Number - Pixel width of dataset stroke
      datasetStrokeWidth: 2,
      //Boolean - Whether to fill the dataset with a color
      datasetFill: true,
      //String - A legend template
      legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
      maintainAspectRatio: true,
      //Boolean - whether to make the chart responsive to window resizing
      responsive: true
    }

    //Create the line chart
    areaChart.Line(areaChartData, areaChartOptions)

  })


</script>
