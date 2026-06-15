<?php
use yii\helpers\Html;
use yii\helpers\Url;

//print_r($postResult);

?>
<script src="https://js.stripe.com/v3/"></script>

<style>
    /* Add some basic styling to the form */
    .form-container {
        max-width: 400px;
        margin: 0 auto;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="phone"] {
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 5px;
        font-size: 15px;
        border: 1px solid #dfd9d9;
        padding: 5px;
    }

    #country_code {
        width: 100px !important;
        font-size: 15px;
        border: 1px solid #dfd9d9;


    }

    .country_code_container {
        border-radius: 0px;
        padding: 5px;
        margin-right: 3px;
        height: 36px;
        background-color: #f5f5f5;
    }

    


    .event-image {
        width: 100%;
        max-height: 750px;
        object-fit: cover;
        border-radius: 5px;
        margin-bottom:5px;
    }

    .separator {
        display: flex;
        align-items: flex-start;
        margin-bottom: 25px;
        border-bottom: 1px solid #eee;
        padding-bottom: 25px;
    }

    .event-card-detail span {


        width: 50%;

    }

    .center {
        width: 150px;
        margin: 20px auto;

    }

    .left_heading {
        font-size: 16px;
        font-weight: bold;
    }

    #card-element {
        background-color: whitesmoke;
        padding: 9px;
        margin-top: 10px;
        margin-bottom: 20px;

    }


    .btn:focus,
    .btn:active,
    button:focus,
    button:active {
        outline: none !important;
        box-shadow: none !important;
    }

    #image-gallery .modal-footer {
        display: block;
    }

    .thumb {
        margin-top: 15px;
        margin-bottom: 15px;
    }
</style>

<div class="container mt-5">
    <h3><?= $model->name ?></h3>
    <div class="row mt-3">

        <div class="col-md-8">
        
            <img class="event-image mb-3"
                src="<?=$model->imageUrl?>"
                alt="Event Image">

            <p>
                <?php echo nl2br($model->description)?>
            </p>
            <br>
            <?php
            
            if(count($model->gallaryImages)>0){?>
            <div><h3>Gallary</h3></div>
            <div class="row ml-1 mb-5">
               
                <div class="row ">
                    

                    <?php foreach($model->gallaryImages as $img){
                     
                        ?>
                    <div class="col-lg-3 col-md-4 col-xs-6 thumb">
                        <a class="thumbnail" href="#" data-image-id="" data-toggle="modal" data-title=""
                            data-image="<?=$img->imageUrl?>"
                            data-target="#image-gallery">
                            <img class="img-thumbnail"
                                src="<?=$img->imageUrl?>"
                                alt="">
                        </a>
                    </div>
                    
                    <?php } ?>
                    
                   
                </div>


                <div class="modal fade" id="image-gallery" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="image-gallery-title"></h4>
                                <button type="button" class="close" data-dismiss="modal"><span
                                        aria-hidden="true">×</span><span class="sr-only">Close</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <img id="image-gallery-image" class="img-responsive col-md-12" src="">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary float-left" id="show-previous-image"><i
                                        class="fa fa-arrow-left"></i>
                                </button>

                                <button type="button" id="show-next-image" class="btn btn-secondary float-right"><i
                                        class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php }?>

        </div>
        <div class="col-md-4">
            <div class="card mb-5">
                <div class="card-header">
                    <h5>Summary</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <div class="d-flex w-100">

                            <span class="left_heading"><i class="fa fa-calendar" aria-hidden="true"></i> Date &
                                Time</span>
                        </div>
                        <p class="pt-2">
                            <?php echo Yii::$app->formatter->format($model->start_date, 'datetime'); ?></span>
                        </p>

                    </li>
                    <li class="list-group-item">
                        <div class="d-flex w-100">

                            <span class="left_heading"><i class="fa fa-map-marker" aria-hidden="true"></i>
                                Location</span>
                        </div>
                        <p class="pt-2">
                            <?php echo $model->place_name; ?>, <?php echo $model->address; ?>
                        </p>

                    </li>

                    <?php if($model->isTicketAllow){?>
                    <li class="list-group-item">
                        <div class="d-flex w-100">
                            <span class="left_heading"><i class="fa fa-money" aria-hidden="true"></i> Price</span>
                        </div>
                        <p class="pt-2">
                            <?php echo $model->eventPrice ?>
                        </p>
                    </li>
                    <?php } ?>

                    <li class="list-group-item">
                        <div class="d-flex w-100">
                            <span class="left_heading"><i class="fa fa-building-o" aria-hidden="true"></i>
                                Organizor</span>
                        </div>
                        <p class="pt-2">
                            <?php echo $model->eventOrganisor->name ?>
                        </p>
                    </li>

                    <?php if($model->isTicketAllow){?>
                    <li class="list-group-item">

                        
                        <div class="center">
                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#buyTicketModal">
                                Buy Ticket
                            </button>

                        </div>


                    </li>
                    <?php }?>
                </ul>
            </div>


        </div>
    </div>
</div>

<!-- Button trigger modal -->


<!-- Modal -->
<div class="modal fade" id="buyTicketModal" tabindex="-1" role="dialog" aria-labelledby="buyTicketModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buyTicketModalTitle">Buy Ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mt-3">
                    <div class="col-md-7">

                        <div id="page1" class="page">

                            <div class="list-group">
                                <?php foreach ($model->eventTicket as $ticket) { ?>
                                    <div class="list-group-item list-group-item-action flex-column align-items-start ">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= $ticket->ticket_type ?></h6>
                                            <h6 class="mb-1">$<?= $ticket->price ?></h6>

                                        </div>
                                        <p class="mb-1">
                                            <b>Total Seats</b>: <?= $ticket->limit ?><br>
                                            <b>Available Seats</b> <?= $ticket->available_ticket ?>
                                            <span class="float-right">
                                                <input type="radio" class="ticket_radio" name="ticket_radio"
                                                    data-price="<?= $ticket->price ?>" value="<?= $ticket->id ?>">
                                            </span>

                                        </p>

                                    </div>
                                <?php } ?>



                            </div>
                            <div class="center">
                                <div class="input-group">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-danger btn-number" data-type="minus"
                                            data-field="quant">
                                            <span> <i class="fa fa-minus" aria-hidden="true"></i></span>

                                        </button>
                                    </span>
                                    <input type="text" name="quant" id="quant" class="form-control input-number"
                                        style="height:30px" value="1" min="1" max="10">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-success btn-number" data-type="plus"
                                            data-field="quant">
                                            <span> <i class="fa fa-plus" aria-hidden="true"></i></span>

                                        </button>
                                    </span>
                                </div>

                            </div>


                            <?php if (count($resultCoupon) > 0) { ?>
                                <div class="list-group mb-3">
                                    <span style="background-color:#dfd9d9; width:65px; padding:5px;">
                                        <h6>Offers</h6>
                                    </span>
                                    <?php foreach ($resultCoupon as $coupon) { ?>
                                        <div class="list-group-item list-group-item-action flex-column align-items-start ">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= $coupon->title ?> ( <?= $coupon->code ?>)</h6>
                                                <h6 class="mb-1">$<?= $coupon->coupon_value ?></h6>
                                                <span><input type="radio" class="coupon_radio" name="coupon_radio"
                                                        data-minprice="<?= $coupon->minimum_order_price ?>"
                                                        data-code="<?= $coupon->code ?>"
                                                        data-price="<?= $coupon->coupon_value ?>"
                                                        value="<?= $coupon->id ?>"></span>

                                            </div>
                                            <p class="mb-1">
                                                <?= $coupon->subtitle ?><br>
                                                <?= $coupon->description ?><br>


                                            </p>

                                        </div>
                                    <?php } ?>



                                </div>
                            <?php } ?>
                            <div class="card">


                                <div class="center">
                                    &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary"
                                        id="nextPageCheckout">Checkout</button>
                                </div>

                            </div>

                            <!--
                            <div class="card">
                                <div class="card-header">
                                    <h5>Order Summary</h5>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">

                                            <span>Order</span>
                                            <span>$<span id="summaryTotalPrice"></span>
                                        </div>

                                    </li>
                                    <li class="list-group-item" id="discount_container">
                                        <div class="d-flex w-100 justify-content-between">

                                            <span>Coupon Discount</span>
                                            <span>-$<span id="summaryCouponDiscountPrice">0</span>
                                        </div>

                                    </li>
                                
                                    <li class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">

                                            <h5>Total</h5>
                                            <h5>$<span id="summaryGrandTotalPrice"></span></h5>
                                        </div>

                                    </li>
                                    <li class="list-group-item">

                                        <div class="center">
                                            &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary" id="nextPageCheckout">Checkout</button>
                                        </div>

                                    </li>
                                </ul>
                            </div>
                                -->
                        </div>
                        <div id="page2" class="page" style="display:none;">
                            <div class="form-container">
                                <form id="payment-form" action="checkout" method="POST">
                                    <input type="text" id="first_name" name="first_name" placeholder="First Name"
                                        required>
                                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                                    <input type="email" id="email" name="email" placeholder="Email">


                                    <div class="input-group">
                                        <span class="input-group-text country_code_container">
                                            <select name="country_code" id="country_code">
                                                <option data-countryCode="US" value="1">USA (+1)</option>
                                                <optgroup label="Other countries">
                                                    <option data-countryCode="DZ" value="213">Algeria (+213)</option>
                                                    <option data-countryCode="AD" value="376">Andorra (+376)</option>
                                                    <option data-countryCode="AO" value="244">Angola (+244)</option>
                                                    <option data-countryCode="AI" value="1264">Anguilla (+1264)</option>
                                                    <option data-countryCode="AG" value="1268">Antigua &amp; Barbuda
                                                        (+1268)</option>
                                                    <option data-countryCode="AR" value="54">Argentina (+54)</option>
                                                    <option data-countryCode="AM" value="374">Armenia (+374)</option>
                                                    <option data-countryCode="AW" value="297">Aruba (+297)</option>
                                                    <option data-countryCode="AU" value="61">Australia (+61)</option>
                                                    <option data-countryCode="AT" value="43">Austria (+43)</option>
                                                    <option data-countryCode="AZ" value="994">Azerbaijan (+994)</option>
                                                    <option data-countryCode="BS" value="1242">Bahamas (+1242)</option>
                                                    <option data-countryCode="BH" value="973">Bahrain (+973)</option>
                                                    <option data-countryCode="BD" value="880">Bangladesh (+880)</option>
                                                    <option data-countryCode="BB" value="1246">Barbados (+1246)</option>
                                                    <option data-countryCode="BY" value="375">Belarus (+375)</option>
                                                    <option data-countryCode="BE" value="32">Belgium (+32)</option>
                                                    <option data-countryCode="BZ" value="501">Belize (+501)</option>
                                                    <option data-countryCode="BJ" value="229">Benin (+229)</option>
                                                    <option data-countryCode="BM" value="1441">Bermuda (+1441)</option>
                                                    <option data-countryCode="BT" value="975">Bhutan (+975)</option>
                                                    <option data-countryCode="BO" value="591">Bolivia (+591)</option>
                                                    <option data-countryCode="BA" value="387">Bosnia Herzegovina (+387)
                                                    </option>
                                                    <option data-countryCode="BW" value="267">Botswana (+267)</option>
                                                    <option data-countryCode="BR" value="55">Brazil (+55)</option>
                                                    <option data-countryCode="BN" value="673">Brunei (+673)</option>
                                                    <option data-countryCode="BG" value="359">Bulgaria (+359)</option>
                                                    <option data-countryCode="BF" value="226">Burkina Faso (+226)
                                                    </option>
                                                    <option data-countryCode="BI" value="257">Burundi (+257)</option>
                                                    <option data-countryCode="KH" value="855">Cambodia (+855)</option>
                                                    <option data-countryCode="CM" value="237">Cameroon (+237)</option>
                                                    <option data-countryCode="CA" value="1">Canada (+1)</option>
                                                    <option data-countryCode="CV" value="238">Cape Verde Islands (+238)
                                                    </option>
                                                    <option data-countryCode="KY" value="1345">Cayman Islands (+1345)
                                                    </option>
                                                    <option data-countryCode="CF" value="236">Central African Republic
                                                        (+236)</option>
                                                    <option data-countryCode="CL" value="56">Chile (+56)</option>
                                                    <option data-countryCode="CN" value="86">China (+86)</option>
                                                    <option data-countryCode="CO" value="57">Colombia (+57)</option>
                                                    <option data-countryCode="KM" value="269">Comoros (+269)</option>
                                                    <option data-countryCode="CG" value="242">Congo (+242)</option>
                                                    <option data-countryCode="CK" value="682">Cook Islands (+682)
                                                    </option>
                                                    <option data-countryCode="CR" value="506">Costa Rica (+506)</option>
                                                    <option data-countryCode="HR" value="385">Croatia (+385)</option>
                                                    <option data-countryCode="CU" value="53">Cuba (+53)</option>
                                                    <option data-countryCode="CY" value="90392">Cyprus North (+90392)
                                                    </option>
                                                    <option data-countryCode="CY" value="357">Cyprus South (+357)
                                                    </option>
                                                    <option data-countryCode="CZ" value="42">Czech Republic (+42)
                                                    </option>
                                                    <option data-countryCode="DK" value="45">Denmark (+45)</option>
                                                    <option data-countryCode="DJ" value="253">Djibouti (+253)</option>
                                                    <option data-countryCode="DM" value="1809">Dominica (+1809)</option>
                                                    <option data-countryCode="DO" value="1809">Dominican Republic
                                                        (+1809)</option>
                                                    <option data-countryCode="EC" value="593">Ecuador (+593)</option>
                                                    <option data-countryCode="EG" value="20">Egypt (+20)</option>
                                                    <option data-countryCode="SV" value="503">El Salvador (+503)
                                                    </option>
                                                    <option data-countryCode="GQ" value="240">Equatorial Guinea (+240)
                                                    </option>
                                                    <option data-countryCode="ER" value="291">Eritrea (+291)</option>
                                                    <option data-countryCode="EE" value="372">Estonia (+372)</option>
                                                    <option data-countryCode="ET" value="251">Ethiopia (+251)</option>
                                                    <option data-countryCode="FK" value="500">Falkland Islands (+500)
                                                    </option>
                                                    <option data-countryCode="FO" value="298">Faroe Islands (+298)
                                                    </option>
                                                    <option data-countryCode="FJ" value="679">Fiji (+679)</option>
                                                    <option data-countryCode="FI" value="358">Finland (+358)</option>
                                                    <option data-countryCode="FR" value="33">France (+33)</option>
                                                    <option data-countryCode="GF" value="594">French Guiana (+594)
                                                    </option>
                                                    <option data-countryCode="PF" value="689">French Polynesia (+689)
                                                    </option>
                                                    <option data-countryCode="GA" value="241">Gabon (+241)</option>
                                                    <option data-countryCode="GM" value="220">Gambia (+220)</option>
                                                    <option data-countryCode="GE" value="7880">Georgia (+7880)</option>
                                                    <option data-countryCode="DE" value="49">Germany (+49)</option>
                                                    <option data-countryCode="GH" value="233">Ghana (+233)</option>
                                                    <option data-countryCode="GI" value="350">Gibraltar (+350)</option>
                                                    <option data-countryCode="GR" value="30">Greece (+30)</option>
                                                    <option data-countryCode="GL" value="299">Greenland (+299)</option>
                                                    <option data-countryCode="GD" value="1473">Grenada (+1473)</option>
                                                    <option data-countryCode="GP" value="590">Guadeloupe (+590)</option>
                                                    <option data-countryCode="GU" value="671">Guam (+671)</option>
                                                    <option data-countryCode="GT" value="502">Guatemala (+502)</option>
                                                    <option data-countryCode="GN" value="224">Guinea (+224)</option>
                                                    <option data-countryCode="GW" value="245">Guinea - Bissau (+245)
                                                    </option>
                                                    <option data-countryCode="GY" value="592">Guyana (+592)</option>
                                                    <option data-countryCode="HT" value="509">Haiti (+509)</option>
                                                    <option data-countryCode="HN" value="504">Honduras (+504)</option>
                                                    <option data-countryCode="HK" value="852">Hong Kong (+852)</option>
                                                    <option data-countryCode="HU" value="36">Hungary (+36)</option>
                                                    <option data-countryCode="IS" value="354">Iceland (+354)</option>
                                                    <option data-countryCode="IN" value="91">India (+91)</option>
                                                    <option data-countryCode="ID" value="62">Indonesia (+62)</option>
                                                    <option data-countryCode="IR" value="98">Iran (+98)</option>
                                                    <option data-countryCode="IQ" value="964">Iraq (+964)</option>
                                                    <option data-countryCode="IE" value="353">Ireland (+353)</option>
                                                    <option data-countryCode="IL" value="972">Israel (+972)</option>
                                                    <option data-countryCode="IT" value="39">Italy (+39)</option>
                                                    <option data-countryCode="JM" value="1876">Jamaica (+1876)</option>
                                                    <option data-countryCode="JP" value="81">Japan (+81)</option>
                                                    <option data-countryCode="JO" value="962">Jordan (+962)</option>
                                                    <option data-countryCode="KZ" value="7">Kazakhstan (+7)</option>
                                                    <option data-countryCode="KE" value="254">Kenya (+254)</option>
                                                    <option data-countryCode="KI" value="686">Kiribati (+686)</option>
                                                    <option data-countryCode="KP" value="850">Korea North (+850)
                                                    </option>
                                                    <option data-countryCode="KR" value="82">Korea South (+82)</option>
                                                    <option data-countryCode="KW" value="965">Kuwait (+965)</option>
                                                    <option data-countryCode="KG" value="996">Kyrgyzstan (+996)</option>
                                                    <option data-countryCode="LA" value="856">Laos (+856)</option>
                                                    <option data-countryCode="LV" value="371">Latvia (+371)</option>
                                                    <option data-countryCode="LB" value="961">Lebanon (+961)</option>
                                                    <option data-countryCode="LS" value="266">Lesotho (+266)</option>
                                                    <option data-countryCode="LR" value="231">Liberia (+231)</option>
                                                    <option data-countryCode="LY" value="218">Libya (+218)</option>
                                                    <option data-countryCode="LI" value="417">Liechtenstein (+417)
                                                    </option>
                                                    <option data-countryCode="LT" value="370">Lithuania (+370)</option>
                                                    <option data-countryCode="LU" value="352">Luxembourg (+352)</option>
                                                    <option data-countryCode="MO" value="853">Macao (+853)</option>
                                                    <option data-countryCode="MK" value="389">Macedonia (+389)</option>
                                                    <option data-countryCode="MG" value="261">Madagascar (+261)</option>
                                                    <option data-countryCode="MW" value="265">Malawi (+265)</option>
                                                    <option data-countryCode="MY" value="60">Malaysia (+60)</option>
                                                    <option data-countryCode="MV" value="960">Maldives (+960)</option>
                                                    <option data-countryCode="ML" value="223">Mali (+223)</option>
                                                    <option data-countryCode="MT" value="356">Malta (+356)</option>
                                                    <option data-countryCode="MH" value="692">Marshall Islands (+692)
                                                    </option>
                                                    <option data-countryCode="MQ" value="596">Martinique (+596)</option>
                                                    <option data-countryCode="MR" value="222">Mauritania (+222)</option>
                                                    <option data-countryCode="YT" value="269">Mayotte (+269)</option>
                                                    <option data-countryCode="MX" value="52">Mexico (+52)</option>
                                                    <option data-countryCode="FM" value="691">Micronesia (+691)</option>
                                                    <option data-countryCode="MD" value="373">Moldova (+373)</option>
                                                    <option data-countryCode="MC" value="377">Monaco (+377)</option>
                                                    <option data-countryCode="MN" value="976">Mongolia (+976)</option>
                                                    <option data-countryCode="MS" value="1664">Montserrat (+1664)
                                                    </option>
                                                    <option data-countryCode="MA" value="212">Morocco (+212)</option>
                                                    <option data-countryCode="MZ" value="258">Mozambique (+258)</option>
                                                    <option data-countryCode="MN" value="95">Myanmar (+95)</option>
                                                    <option data-countryCode="NA" value="264">Namibia (+264)</option>
                                                    <option data-countryCode="NR" value="674">Nauru (+674)</option>
                                                    <option data-countryCode="NP" value="977">Nepal (+977)</option>
                                                    <option data-countryCode="NL" value="31">Netherlands (+31)</option>
                                                    <option data-countryCode="NC" value="687">New Caledonia (+687)
                                                    </option>
                                                    <option data-countryCode="NZ" value="64">New Zealand (+64)</option>
                                                    <option data-countryCode="NI" value="505">Nicaragua (+505)</option>
                                                    <option data-countryCode="NE" value="227">Niger (+227)</option>
                                                    <option data-countryCode="NG" value="234">Nigeria (+234)</option>
                                                    <option data-countryCode="NU" value="683">Niue (+683)</option>
                                                    <option data-countryCode="NF" value="672">Norfolk Islands (+672)
                                                    </option>
                                                    <option data-countryCode="NP" value="670">Northern Marianas (+670)
                                                    </option>
                                                    <option data-countryCode="NO" value="47">Norway (+47)</option>
                                                    <option data-countryCode="OM" value="968">Oman (+968)</option>
                                                    <option data-countryCode="PW" value="680">Palau (+680)</option>
                                                    <option data-countryCode="PA" value="507">Panama (+507)</option>
                                                    <option data-countryCode="PG" value="675">Papua New Guinea (+675)
                                                    </option>
                                                    <option data-countryCode="PY" value="595">Paraguay (+595)</option>
                                                    <option data-countryCode="PE" value="51">Peru (+51)</option>
                                                    <option data-countryCode="PH" value="63">Philippines (+63)</option>
                                                    <option data-countryCode="PL" value="48">Poland (+48)</option>
                                                    <option data-countryCode="PT" value="351">Portugal (+351)</option>
                                                    <option data-countryCode="PR" value="1787">Puerto Rico (+1787)
                                                    </option>
                                                    <option data-countryCode="QA" value="974">Qatar (+974)</option>
                                                    <option data-countryCode="RE" value="262">Reunion (+262)</option>
                                                    <option data-countryCode="RO" value="40">Romania (+40)</option>
                                                    <option data-countryCode="RU" value="7">Russia (+7)</option>
                                                    <option data-countryCode="RW" value="250">Rwanda (+250)</option>
                                                    <option data-countryCode="SM" value="378">San Marino (+378)</option>
                                                    <option data-countryCode="ST" value="239">Sao Tome &amp; Principe
                                                        (+239)</option>
                                                    <option data-countryCode="SA" value="966">Saudi Arabia (+966)
                                                    </option>
                                                    <option data-countryCode="SN" value="221">Senegal (+221)</option>
                                                    <option data-countryCode="CS" value="381">Serbia (+381)</option>
                                                    <option data-countryCode="SC" value="248">Seychelles (+248)</option>
                                                    <option data-countryCode="SL" value="232">Sierra Leone (+232)
                                                    </option>
                                                    <option data-countryCode="SG" value="65">Singapore (+65)</option>
                                                    <option data-countryCode="SK" value="421">Slovak Republic (+421)
                                                    </option>
                                                    <option data-countryCode="SI" value="386">Slovenia (+386)</option>
                                                    <option data-countryCode="SB" value="677">Solomon Islands (+677)
                                                    </option>
                                                    <option data-countryCode="SO" value="252">Somalia (+252)</option>
                                                    <option data-countryCode="ZA" value="27">South Africa (+27)</option>
                                                    <option data-countryCode="ES" value="34">Spain (+34)</option>
                                                    <option data-countryCode="LK" value="94">Sri Lanka (+94)</option>
                                                    <option data-countryCode="SH" value="290">St. Helena (+290)</option>
                                                    <option data-countryCode="KN" value="1869">St. Kitts (+1869)
                                                    </option>
                                                    <option data-countryCode="SC" value="1758">St. Lucia (+1758)
                                                    </option>
                                                    <option data-countryCode="SD" value="249">Sudan (+249)</option>
                                                    <option data-countryCode="SR" value="597">Suriname (+597)</option>
                                                    <option data-countryCode="SZ" value="268">Swaziland (+268)</option>
                                                    <option data-countryCode="SE" value="46">Sweden (+46)</option>
                                                    <option data-countryCode="CH" value="41">Switzerland (+41)</option>
                                                    <option data-countryCode="SI" value="963">Syria (+963)</option>
                                                    <option data-countryCode="TW" value="886">Taiwan (+886)</option>
                                                    <option data-countryCode="TJ" value="7">Tajikstan (+7)</option>
                                                    <option data-countryCode="TH" value="66">Thailand (+66)</option>
                                                    <option data-countryCode="TG" value="228">Togo (+228)</option>
                                                    <option data-countryCode="TO" value="676">Tonga (+676)</option>
                                                    <option data-countryCode="TT" value="1868">Trinidad &amp; Tobago
                                                        (+1868)</option>
                                                    <option data-countryCode="TN" value="216">Tunisia (+216)</option>
                                                    <option data-countryCode="TR" value="90">Turkey (+90)</option>
                                                    <option data-countryCode="TM" value="7">Turkmenistan (+7)</option>
                                                    <option data-countryCode="TM" value="993">Turkmenistan (+993)
                                                    </option>
                                                    <option data-countryCode="TC" value="1649">Turks &amp; Caicos
                                                        Islands (+1649)</option>
                                                    <option data-countryCode="TV" value="688">Tuvalu (+688)</option>
                                                    <option data-countryCode="UG" value="256">Uganda (+256)</option>
                                                    <option data-countryCode="GB" value="44">UK (+44)</option>
                                                    <option data-countryCode="UA" value="380">Ukraine (+380)</option>
                                                    <option data-countryCode="AE" value="971">United Arab Emirates
                                                        (+971)</option>
                                                    <option data-countryCode="UY" value="598">Uruguay (+598)</option>
                                                    <option data-countryCode="US" value="1">USA (+1)</option>
                                                    <option data-countryCode="UZ" value="7">Uzbekistan (+7)</option>
                                                    <option data-countryCode="VU" value="678">Vanuatu (+678)</option>
                                                    <option data-countryCode="VA" value="379">Vatican City (+379)
                                                    </option>
                                                    <option data-countryCode="VE" value="58">Venezuela (+58)</option>
                                                    <option data-countryCode="VN" value="84">Vietnam (+84)</option>
                                                    <option data-countryCode="VG" value="84">Virgin Islands - British
                                                        (+1284)</option>
                                                    <option data-countryCode="VI" value="84">Virgin Islands - US (+1340)
                                                    </option>
                                                    <option data-countryCode="WF" value="681">Wallis &amp; Futuna (+681)
                                                    </option>
                                                    <option data-countryCode="YE" value="969">Yemen (North)(+969)
                                                    </option>
                                                    <option data-countryCode="YE" value="967">Yemen (South)(+967)
                                                    </option>
                                                    <option data-countryCode="ZM" value="260">Zambia (+260)</option>
                                                    <option data-countryCode="ZW" value="263">Zimbabwe (+263)</option>
                                                </optgroup>
                                            </select>

                                        </span>
                                        <input type="text" aria-label="phone" type="phone" id="phone" name="phone"
                                            placeholder="Phone Number" style=" border-radius: 0px;"
                                            class="form-control">
                                    </div>
                                    <input type="hidden" id="amount" name="amount" placeholder="Amount">
                                    <input type="hidden" id="grand_amount" name="grand_amount"
                                        placeholder="Grand Amount">
                                    <input type="hidden" id="coupon_code" name="coupon_code" placeholder="code">
                                    <input type="hidden" id="coupon_discount" name="coupon_discount" placeholder="dis">


                                    <input type="hidden" id="ticket_id" name="ticket_id">
                                    <input type="hidden" id="ticket_qty" name="ticket_qty">
                                    <input type="hidden" id="c_tok" name="c_tok">

                                    <div id="card-element"><!--Stripe.js injects the Card Element--></div>

                                    <button type="submit" id="btnPayment" class="btn btn-primary">Pay $<span
                                            id="payButtonPrice"></span></button>
                                    <button type="button" class="btn btn-gray" id="prevPageTicket">Back</button>
                                    <div id="loader" class="d-none">
                                        <!-- Bootstrap Loader Animation -->
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p>Loading...</p>
                                    </div>
                                </form>
                            </div>
                            <!-- Add your content for the second page here -->

                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5>Order Summary</h5>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">

                                        <span>Order</span>
                                        <span>$<span id="summaryTotalPrice"></span>
                                    </div>

                                </li>
                                <li class="list-group-item" id="discount_container">
                                    <div class="d-flex w-100 justify-content-between">

                                        <span>Coupon Discount</span>
                                        <span>-$<span id="summaryCouponDiscountPrice">0</span>
                                    </div>

                                </li>

                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">

                                        <h5>Total</h5>
                                        <h5>$<span id="summaryGrandTotalPrice"></span></h5>
                                    </div>

                                </li>
                                <!--<li class="list-group-item">

                                    <div class="center">
                                        &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-primary"
                                            id="nextPageCheckout">Checkout</button>
                                    </div>

                                </li>-->
                            </ul>
                        </div>
                    </div>
                </div>






            </div>
            <!--<div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>-->
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.6/inputmask.min.js"></script>
<script>
    // Initialize Stripe.js with your publishable key
    var stripPKey = "<?php echo $setting->stripe_publishable_key ?>";

    var stripe = Stripe(stripPKey);

    // Create an instance of Elements

    //var elements = stripe.elements();

    const appearance = {
        theme: 'flat',
        variables: { colorPrimaryText: '#262626' }
    };
    //var elements = stripe.elements({ appearance });
    const elements = stripe.elements(appearance);

    // Create an instance of the card Element
    var card = elements.create('card');

    // Add an instance of the card Element into the `card-element` div
    card.mount('#card-element');

    // Handle form submission
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var email = $('#email').val();
        var phone = $('#phone').val();

        if (email === '' && phone === '') {
            alert('Please enter either an email or a phone number.');
            return false; // Exit the function if both fields are empty
        }
        /*var phonePattern = /^\d{10}$/;
        if (!phonePattern.test(phone)) {
        $('#phone-error').text('Please enter a valid 10-digit phone number.');
            alert('Please enter either an email or a phone number.');
            return false; 
        }*/


        stripe.createToken(card).then(function (result) {

            $('#btnPayment').prop('disabled', true);
            $('#loader').removeClass('d-none');


            if (result.error) {
                // Inform the user if there was an error
                $('#loader').addClass('d-none');
                alert(result.error.message);
                $('#btnPayment').prop('disabled', false);

            } else {
                // Send the token to your server

                var token = result.token;
                console.log('a');
                console.log(token.id);
                $('#c_tok').val(token.id);


                form.submit();
                // You can now send this token to your backend for further processing
                // For example:
                // fetch('/process-payment', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //     },
                //     body: JSON.stringify({token: token}),
                // })
                // .then(response => response.json())
                // .then(data => {
                //     console.log('Success:', data);
                // })
                // .catch((error) => {
                //     console.error('Error:', error);
                // });
            }
        });
    });
</script>



<script>


    $(document).ready(function () {


        $('#buyTicketModal').on('show.bs.modal', function (event) {

            var $radio = $('.ticket_radio');
            $radio[0].checked = true;
            currentPage = 1;
            $('.page').hide(); // Hide all pages
            $('#page' + currentPage).show(); // Show current page
            $('#quant').val(1);
            setTotalPrice();


            $('#first_name').val('');
            $('#last_name').val('');
            $('#email').val('');
            $('#phone').val('');
            

        });

        setTotalPrice();

        $('.ticket_radio').click(function () {
            $('#quant').val(1);
            setTotalPrice();

        })
        $('.coupon_radio').click(function () {

            setTotalPrice();

        })



        function setTotalPrice() {
            var $radio = $('.ticket_radio');
            if (!$radio.filter(':checked').length) {
                $radio[0].checked = true;
            }

            var selectedTicket = $('.ticket_radio:checked');
            var price = $(selectedTicket).attr('data-price');

            var selectedTicketId = $(selectedTicket).val();

            var qty = $('#quant').val();
            var totalAmount = price * qty;

            var couponDiscount = 0;
            var couponCode = '';
            var $couponradio = $('.coupon_radio');
            if ($couponradio.filter(':checked').length) {

                var selectedCoupon = $('.coupon_radio:checked');
                var couponPrice = $(selectedCoupon).attr('data-price');
                couponCode = $(selectedCoupon).attr('data-code');
                var orderMinPrice = $(selectedCoupon).attr('data-minprice');
                if (totalAmount >= orderMinPrice) {
                    couponDiscount = couponPrice;
                }
            }
            var grandTotal = totalAmount;
            if (couponDiscount > 0) {
                grandTotal = totalAmount - couponDiscount;
            }



            $('#summaryTotalPrice').html(totalAmount);
            $('#summaryCouponDiscountPrice').html(couponDiscount);
            $('#summaryGrandTotalPrice').html(grandTotal);
            $('#payButtonPrice').html(grandTotal);


            $('#ticket_id').val(selectedTicketId);
            $('#ticket_qty').val(qty);
            $('#amount').val(totalAmount);
            $('#grand_amount').val(grandTotal);
            $('#coupon_code').val(couponCode);
            $('#coupon_discount').val(couponDiscount);





        }

        var currentPage = 1;
        // Next page button click
        $('#nextPageCheckout').click(function () {


            if (currentPage < 2) {
                currentPage++;
                updatePages();
            }
        });

        // Previous page button click
        $('#prevPageTicket').click(function () {
            if (currentPage > 1) {
                currentPage--;
                updatePages();
            }
        });

        // Function to update visible page
        function updatePages() {
            $('.page').hide(); // Hide all pages
            $('#page' + currentPage).show(); // Show current page
        }









        //plugin bootstrap minus and plus
        //http://jsfiddle.net/laelitenetwork/puJ6G/
        $('.btn-number').click(function (e) {
            e.preventDefault();

            fieldName = $(this).attr('data-field');
            type = $(this).attr('data-type');
            var input = $("input[name='" + fieldName + "']");
            var currentVal = parseInt(input.val());
            if (!isNaN(currentVal)) {
                if (type == 'minus') {

                    if (currentVal > input.attr('min')) {
                        input.val(currentVal - 1).change();
                    }
                    if (parseInt(input.val()) == input.attr('min')) {
                        $(this).attr('disabled', true);
                    }

                } else if (type == 'plus') {

                    if (currentVal < input.attr('max')) {
                        input.val(currentVal + 1).change();
                    }
                    if (parseInt(input.val()) == input.attr('max')) {
                        $(this).attr('disabled', true);
                    }

                }
                setTotalPrice();
            } else {
                input.val(0);
            }
        });
        $('.input-number').focusin(function () {
            $(this).data('oldValue', $(this).val());
        });
        $('.input-number').change(function () {

            minValue = parseInt($(this).attr('min'));
            maxValue = parseInt($(this).attr('max'));
            valueCurrent = parseInt($(this).val());

            name = $(this).attr('name');
            if (valueCurrent >= minValue) {
                $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr('disabled')
            } else {
                alert('Sorry, the minimum value was reached');
                $(this).val($(this).data('oldValue'));
            }
            if (valueCurrent <= maxValue) {
                $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr('disabled')
            } else {
                alert('Sorry, the maximum value was reached');
                $(this).val($(this).data('oldValue'));
            }


        });
        $(".input-number").keydown(function (e) {
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
                // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) ||
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                // let it happen, don't do anything
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    });

    let modalId = $('#image-gallery');

    $(document)
        .ready(function () {

            loadGallery(true, 'a.thumbnail');

            //This function disables buttons when needed
            function disableButtons(counter_max, counter_current) {
                $('#show-previous-image, #show-next-image')
                    .show();
                if (counter_max === counter_current) {
                    $('#show-next-image')
                        .hide();
                } else if (counter_current === 1) {
                    $('#show-previous-image')
                        .hide();
                }
            }

            /**
             *
             * @param setIDs        Sets IDs when DOM is loaded. If using a PHP counter, set to false.
             * @param setClickAttr  Sets the attribute for the click handler.
             */

            function loadGallery(setIDs, setClickAttr) {
                let current_image,
                    selector,
                    counter = 0;

                $('#show-next-image, #show-previous-image')
                    .click(function () {
                        if ($(this)
                            .attr('id') === 'show-previous-image') {
                            current_image--;
                        } else {
                            current_image++;
                        }

                        selector = $('[data-image-id="' + current_image + '"]');
                        updateGallery(selector);
                    });

                function updateGallery(selector) {
                    let $sel = selector;
                    current_image = $sel.data('image-id');
                    $('#image-gallery-title')
                        .text($sel.data('title'));
                    $('#image-gallery-image')
                        .attr('src', $sel.data('image'));
                    disableButtons(counter, $sel.data('image-id'));
                }

                if (setIDs == true) {
                    $('[data-image-id]')
                        .each(function () {
                            counter++;
                            $(this)
                                .attr('data-image-id', counter);
                        });
                }
                $(setClickAttr)
                    .on('click', function () {
                        updateGallery($(this));
                    });
            }
        });

    // build key actions
    $(document)
        .keydown(function (e) {
            switch (e.which) {
                case 37: // left
                    if ((modalId.data('bs.modal') || {})._isShown && $('#show-previous-image').is(":visible")) {
                        $('#show-previous-image')
                            .click();
                    }
                    break;

                case 39: // right
                    if ((modalId.data('bs.modal') || {})._isShown && $('#show-next-image').is(":visible")) {
                        $('#show-next-image')
                            .click();
                    }
                    break;

                default:
                    return; // exit this handler for other keys
            }
            e.preventDefault(); // prevent the default action (scroll / move caret)
        });


</script>