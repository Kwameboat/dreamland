<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use sjaakp\loadmore\LoadMorePager;
//print_r($bannerResult); 

?>

<section class="featured_ads p-b-40">
  <div class="container"> 
    <!-- Row  -->
    <div class="row justify-content-center">
      <div class="col-md-7 text-center">
        <h2 class="title"><?=Yii::t('app','Featured Ads')?></h2>
        
      </div>
    </div>
    <!-- Row  -->
    <div class="row ">

      <?= $this->render('_loadmore.php', [
        'dataProvider' => $dataProvider
      ]) ?>

       <?php
       /*
        foreach($featuedAdResult as $model){
           echo  $this->render('../ad/_ad.php',['model'=>$model,'type'=>'feature']);

        }
                     
        echo ListView::widget([
           'dataProvider' => $featuedAdResult,
           'itemView' => '../ad/_ad',
           'viewParams' => ['type' => 'feature'],
           'summaryOptions'=>['class' => 'summary col-xl-12'],
           'id' => 'myGrid',
           'pager' => [
            'class' => LoadMorePager::class,
            'label' => 'Show more data',
            'id' => 'myPager'
          ],
           'itemOptions' => [
               'tag' => false
           ],
           'options' => [
               'class' => 'row col-md-12 col-sm-12 col-xs-12',
               'id' => false
           ],
           'summary' => 'Showing {begin}-<span class="summary-end">{end}</span> of {totalCount} items',
           
           /*'pager' => [
               'maxButtonCount' => 4,
       
               // Customzing options for pager container tag
               'options' => [
                   'tag' => 'ul',
                   'class' => 'pagination justify-content-center col-xl-12 m-t-20 m-b-50',
                   'id' => 'pager-container',
               ],
       
               // Customzing CSS class for pager link
               'linkOptions' => ['class' => 'page-link'],
               'activePageCssClass' => 'page-item active',
               'disabledPageCssClass' => 'hide_paginaton',
       
               // Customzing CSS class for navigating link
               'prevPageCssClass' => 'page-item',
               'nextPageCssClass' => 'page-item',
               'firstPageCssClass' => 'page-item',
               'lastPageCssClass' => 'page-item',
           ],
       
       ]);
       */

       
       
       ?> 
    </div>
  </div>

</section>

