console.log(baseurl);

jQuery(document).ready(function($) {

    //console.log(baseurl);
    
       $(".d-flexs").click(function(event) {
            
            
            var groupId   =$(this).attr("data-groupId"); 
            event.preventDefault(); // stopping submitting
            var data = {groupId: groupId};
            
            alert(groupId);
            /* 
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
            });*/
        
        });
    });