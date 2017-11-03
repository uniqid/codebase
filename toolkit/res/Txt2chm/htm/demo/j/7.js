var g_cur_pos,g_timer;
$(function(){
    function jumpto(act){
        var src = '', obj, num = location.href.replace(/.*?(\d+)\.htm$/, '$1');
        num = +num;
        if(isNaN(num)){
            src = home_num + '.htm';
        } else {
            if(act == 'prev'){
                src = (num > min_prev? num-1: min_prev) + '.htm';
            } else if(act == 'next') {
                src = (num < max_next? num+1: max_next) + '.htm';
            } else if(act == 'home') {
                src = home_num + '.htm';
            }
        }

        if(src != ''){
            obj = (src == home_num + '.htm') && window.parent? window.parent: document;
            obj.location = src;
        }
    }

    $('.page_nav').children('img').on('click', function(){
        var index = $(this).index();
        if (index == 0) {
            jumpto('prev');
        } else if (index == 2) {
            jumpto('next');
        } else if (index == 1) {
            jumpto('home');
        }
    });

    $(document).on('dblclick', function(){
        g_timer = setInterval(function(){
            g_cur_pos = $(this).scrollTop();
            window.scroll(0, ++g_cur_pos); 
            if (g_cur_pos != $(this).scrollTop()){
                clearInterval(g_timer);
            }
        }, 50);
    }).on('mousedown', function(){
        clearInterval(g_timer);
    }).on('keydown', function(event){
        if(typeof event == 'undefined'){
            return;
        }
        if (event.keyCode == 37) {
            jumpto('prev');
        }
        else if (event.keyCode == 39) {
            jumpto('next');
        }
        else if (event.keyCode == 13) {
            jumpto('home');
        }
    });
});
