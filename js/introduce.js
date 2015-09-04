/**
 * Created by kimbeomjun on 15. 8. 20..
 */

// run after document loading...
$(document).ready(function () {
    $('.hasHover').hover(function () {
        $(this).attr('src', $(this).attr('src').replace('.png', '_hover.png'));
    }, function () {
        $(this).attr('src', $(this).attr('src').replace('_hover.png', '.png'));
    });

    $('#more_preparer, #more_inspector_list').hide();

    var more_preparer_opened = false;
    $('#more_preparing_people').click(function () {
        $('#more_preparer').toggle(!more_preparer_opened);
        more_preparer_opened = !more_preparer_opened;
        if ( !more_preparer_opened ) {
            $('#more_preparing_people').html("더보기 <img src='/images/base/arrow_down.png' alt=''/>");
        } else {
            $('#more_preparing_people').html("접기 <img src='/images/base/arrow_up2.png' alt=''/>");
        }
    });

    var more_inspector_list_opened = false;
    $('#more_inspector').click(function () {
        $('#more_inspector_list').toggle(!more_inspector_list_opened);
        more_inspector_list_opened = !more_inspector_list_opened;
        if ( !more_inspector_list_opened ) {
            $('#more_inspector').html("더보기 <img src='/images/base/arrow_down.png' alt=''/>");
        } else {
            $('#more_inspector').html("접기 <img src='/images/base/arrow_up2.png' alt=''/>");
        }
    });
});