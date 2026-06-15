//console.log(baseurl);

jQuery(document).ready(function($) {

    //console.log(baseurl);
    
       //$(".saveFavorite").click(function(event) {
        $( ".ad_container" ).on( "click", ".saveFavorite", function(event) {
            
            if(isGuest){
               
               var redirectUrl  = baseurl+"/site/login";
                window.location.href = redirectUrl; 
                
            }
    
            var adId   =$(this).attr("data-adId"); 
            event.preventDefault(); // stopping submitting
            var data = {ad_id: adId};
            var mainThis = this;
             
            //var url = $(this).attr('action');
            var url = baseurl+'/favorite/add';
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: data
            })
            .done(function(response) {
                if (response.data.success == true) {
                    
                    if(response.data.type=='added'){
                        $(mainThis).children("i").removeClass('fa-heart-o');
                        $(mainThis).children("i").addClass('fa-heart');
                    }else{
                        $(mainThis).children("i").removeClass('fa-heart');
                        $(mainThis).children("i").addClass('fa-heart-o');
                    }
                    
                    
                }else{

                    alert(response.data.message);
                }
            })
            .fail(function() {
                console.log("error");
            });
        
        });
    });