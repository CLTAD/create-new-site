jQuery(document).ready(function($){

    $('#create-new-site-form').submit(function(){

        // show that we're in progress
        $('#blogs-personal-li').addClass('loading');

        var data = {
            'action': 'create_new_site',
            'nonce': $('#ajax-create-new-site').val(),
            'blogurl': $('#blogurl').val(),
            'blogtitle': $('#blogtitle').val()
        }

        if ( $('#message').length ){
            $("#message").fadeOut(100);
        }

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            dataType: 'json',
            success: function(response) {

                // show message div with appropriate response
                if ( $('#message').length ){
                    $("#message").remove();
                }
                var mess = $('<div/>');
                mess.attr({
                'id' : 'message',
                'class' : 'info'
                }); 
                var para = $('</p>').html(response.message);
                mess.append(para);
                $('#status').append(mess);

                // set display of blog stats
                $('#blogs-personal-li a span').html(response.data.count_all_user_blogs);
                $('#sites-created').html(response.data.count_is_admin_all_sites - response.data.count_is_admin_group_sites);
                $('#sites-remaining').html(response.data.sites_remaining);
                
                if ((response.data.count_is_admin_all_sites - response.data.count_is_admin_group_sites) >= response.data.sites_per_user ){
                    $('#create-new-site-form').hide();
                }

                // show that we're finished processing
                $('#blogs-personal-li').removeClass('loading');
            }
        });

        return false;
    });

});