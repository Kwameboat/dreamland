<?php

/* @var $this \yii\web\View */
/* @var $content string */
//use yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;



use yii\web\JsExpression;
//use common\widgets\Alert;
use kartik\alert\Alert;

if(Yii::$app->language=='ar-AE'){
  $direction = 'rtl';
  //$cssFile = 'style-rtl.css';
}else{

  $direction = 'ltr';
 // $cssFile = 'style.css';
}



//$this->registerCssFile(Url::base().'/css/'.$cssFile,['position' => \yii\web\View::POS_BEGIN]); 
AppAsset::register($this);

//$this->registerJsFile(Url::base().'/js/custom.js',['position' => \yii\web\View::POS_END]); 





?>

<script>


var baseurl="<?php echo Yii::$app->request->baseUrl;?>";
var isGuest  =  "<?php echo Yii::$app->user->isGuest?>";


</script>

<?php $this->beginPage();

?>
<!DOCTYPE html>
<html dir="<?=$direction?>"  lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<?php 

$controller = Yii::$app->controller;
$default_controller = Yii::$app->defaultRoute;
$isHome = (($controller->id === $default_controller) && ($controller->action->id === $controller->defaultAction)) ? true : false;

?>

<body  class="<?=($isHome) ? '':'iner_page'?>">
<?php $this->beginBody() ?>




  <!-- Header  -->

  <?php 
  if(!$isHome){
   echo $this->render('header.php',['isHome'=>$isHome]);
  }
   ?>


  <?php if(isset($this->params['breadcrumbs'])) { ?>

  <div class="iner_breadcrumb bg-light p-t-20 p-b-20">
  <div class="container">
    <nav aria-label="breadcrumb">
      <?php //= Breadcrumbs::widget([  
      //    'itemTemplate' => "<li class=\"breadcrumb-item\">{link}</li>\n", 
      //   'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : []
      
      // ]) 
      echo Yii::$app->name;
      ?>
        
        <div class="pull-right">
                                <?php
                                if(@Yii::$app->user->identity->id){ ?>
                            <?= Html::a(
                                    'Logout',
                                    ['/site/logout'],
                                    ['data-method' => 'post', 'class' => 'btn btn-danger btn-flat']
                                ) 
                                ?>
                              <?php  }
                                ?>
                            </div>
    </nav>
  </div>
</div>
  <?php } ?>

  
  
  <?php 
  if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissable text-center">
         <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
         <?= Yii::$app->session->getFlash('success') ?>
    </div>
<?php endif; ?>


<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissable text-center">
         <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
         <?= Yii::$app->session->getFlash('error') ?>
    </div>
<?php endif; 
      
  
  //  echo Alert::widget() 
?>
  
<section id="main-content" >
<?=$content?>


</section>

<!-- End Testimonial --> 

<!-- Footer -->


<?php 
  if(!$isHome){
   echo $this->render('footer.php',['isHome'=>$isHome]);
  }
   ?>
    
<?php $this->endBody() ?>


<?php
yii\bootstrap4\Modal::begin([
   // 'header' => '<span id="modalHeaderTitle"></span>',
    'headerOptions' => ['id' => 'modalHeader'],
    'title' => 'Hello world',
    'id' => 'modal',
    'size' => 'modal-lg',
   // 'class' =>'modal-lg',
    //keeps from closing modal with esc key or by clicking out of the modal.
    // user must click cancel or X to close
    'clientOptions' => ['backdrop' => 'static', 'keyboard' => true]
]);
echo "<div id='modalContent'></div>";
yii\bootstrap4\Modal::end();
?>

<script type="text/javascript">

    //get the click of modal button to create / update item
    //we get the button by class not by ID because you can only have one id on a page and you can
    //have multiple classes therefore you can have multiple open modal buttons on a page all with or without
    //the same link.
//we use on so the dom element can be called again if they are nested, otherwise when we load the content once it kills the dom element and wont let you load anther modal on click without a page refresh
      $(document).on('click', '.showModalButton', function(){
        
        if(this.title==""){
          $('#modal').find('#modalHeader').css("border-bottom", "none");
          $('#modal').find('#modalHeader').css("padding", "8");
          $('#modal').find('#modalHeader').find('.modal-title').hide();
        }else{
          $('#modal').find('#modalHeader').find('.modal-title').text(this.title);
        }
        
        
        //check if the modal is open. if it's open just reload content not whole modal
        //also this allows you to nest buttons inside of modals to reload the content it is in
        //the if else are intentionally separated instead of put into a function to get the 
        //button since it is using a class not an #id so there are many of them and we need
        //to ensure we get the right button and content. 
       // console.log($('#modal').data('bs.modal').isShown);

        
  
        if ($('#modal').data('bs.modal').isShown) {
            $('#modal').find('#modalContent')
                    .load($(this).attr('value'));
            //dynamiclly set the header for the modal via title tag
            
           // document.getElementById('modalHeaderTitle').innerHTML = '<h4>' + $(this).attr('title') + '</h4>';
        } else {
            //if modal isn't open; open it and load content
            $('#modal').modal('show')
                    .find('#modalContent')
                    .load($(this).attr('value'));
             //dynamiclly set the header for the modal via title tag
          //   document.getElementById('modalHeaderTitle').innerHTML = '<h4>' + $(this).attr('title') + '</h4>';
        }
    });


</script>    

</body>
</html>
<?php $this->endPage() ?>

