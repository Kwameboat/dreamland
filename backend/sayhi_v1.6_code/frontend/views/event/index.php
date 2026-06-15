<?php
use yii\helpers\Html;
use yii\helpers\Url;

//print_r($postResult);

?>
 


<div class="container p-b-45" >
<h1 class="mt-5 mb-4">Events</h1>    
<div class="mb-4">
    <?php if(count($resultCategory)){
      $btn_class= ($category_id==0)? "btn-primary":"btn-secondary";
      ?>
      <a href="<?= Url::toRoute(['/event'])?>" ><button class="btn category-btn <?=$btn_class?>" data-category="all">All</button></a>
        <?php }?>
        <?php foreach($resultCategory as $category){
          $btn_class= ($category_id==$category->id)? "btn-primary":"btn-secondary";
          
          ?>
        <a href="<?= Url::toRoute(['event/', 'category_id' => $category->id])?>" ><button class="btn category-btn <?=$btn_class?>" data-category="<?=$category->name?>"><?=$category->name?></button></a>
        <?php }
          ?>
       
        <!-- Add more category buttons as needed -->
    </div>
    
    
    <div class="row">
      <?php 
      if(count($resultEvent)>0){
      foreach($resultEvent as $event){ ?>
        <div class="col-md-4 mb-4">
            <div class="card event-card">
                <img src="<?=$event->imageUrl?>" class="card-img-top" width="300" height="300" alt="Event Image">
                <div class="card-body">
                    <h5 class="card-title"><?=$event->name?></h5>
                    <p class="card-text">
                      <i class="fa fa-calendar" aria-hidden="true"></i>
                      <?php echo Yii::$app->formatter->format($event->start_date, 'datetime'); ?>
                    </p>
                    <p class="card-text  p-b-10">
                      <i class="fa fa-map-marker" aria-hidden="true"></i>  
                        <?php  echo $event->place_name;?>
                    </p>
                    <a href="<?= Url::toRoute(['event/view', 'id' => $event->unique_id])?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div> 

      <?php } 
      
      }else{ ?>
        <div class="col-md-4 mb-4">
        No evernt found currently
        </div>


      <?php }
      ?>
       
        
    </div>

</div>



<!-- End Featured_ads -->

