/**
 * Created by kimbeomjun on 15. 9. 8..
 */
$(document).ready(function () {
    $("input[name='joinDate']").click(function () {
        if ($("input[name='joinType']:checked").val())
            $('#amount').val(calculateAmount($("input[name='joinDate']:checked").val(), $("input[name='joinType']:checked").val()));

        $('.launch_box_info').hide();
        switch ($("input[name='joinDate']:checked").val()) {
            case '15':
                $('#launch_box_15').show();
                $('#launch_box_16').hide();
                break;
            case '16':
                $('#launch_box_15').hide();
                $('#launch_box_16').show();
                break;
            case '1516':
                $('#launch_box_15').show();
                $('#launch_box_16').show();
                break;
        }
        $("input[name='day15_launch']").each(function(){
            $(this).prop('checked', false);
        });
        $("input[name='day16_launch']").each(function(){
            $(this).prop('checked', false);
        });
    });

    $("input[name='joinType']").click(function () {
        if ($("input[name='joinDate']:checked").val())
            $('#amount').val(calculateAmount($("input[name='joinDate']:checked").val(), $("input[name='joinType']:checked").val()));
    });

    jQuery.validator.setDefaults({
        debug: true,
        success: "valid"
    });

    $('#joinform').validate({
        submitHandler: function (form) {
            if ( !$("input[name='joinDate']:checked").length ) {
                alert('참가희망 날짜를 선택해주세요.');
                return false;            }
            if ( !$("input[name='joinType']:checked").length ) {
                alert('참가자의 참가 유형을 선택해주세요.');
                return false;
            }
            if ( $('#launch_box_15').css('display') == 'block'
                && !$("input[name='day15_launch']:checked").val() ){
                alert('15일 점심 식사 메뉴를 선택해주세요.');
                return false;
            }
            if ( $('#launch_box_16').css('display') == 'block'
                && !$("input[name='day16_launch']:checked").val() ){
                alert('16일 점심 식사 메뉴를 선택해주세요.');
                return false;
            }

            if ( !$('#personal_info_agree_checkbox').prop('checked') ) {
              alert('개인정보의 수집/이용에 관한 동의서에 동의해주세요.');
              return false;
            }

            paymentProcess(form);
        },
        rules: {
            buyer_name: {
                required: true,
            },
            buyer_email: {
                required: true,
                email: true
            },
            buyer_tel: {
                required: true
            },
            buyer_addr: {
                required: true
            },
            buyer_eng_addr :{
                required: true
            },
            buyer_eng_name: {
                required: true
            }
        },
        messages: {
            buyer_name: {
                required: '* 이름을 입력해주세요.'
            },
            buyer_email: {
                required: '* 이메일 주소를 입력해주세요.',
                email: '* 이메일 주소가 형식에 맞지 않습니다.'
            },
            buyer_tel: {
                required: '* 전화번호를 입력해주세요.'
            },
            buyer_addr: {
                required: '* 소속을 입력해주세요.'
            },
            buyer_eng_name: {
                required: '* 영문이름을 입력해주세요.'
            },
            buyer_eng_addr: {
                required: '* 영문소속을 입력해주세요.'
            }
        }
    });

});

function calculateAmount(joinDate, joinType) {
    if (joinType == 'ordinary') {
        if (joinDate == '15' || joinDate == '16')
            return '70,000';
        else
            return '100,000';
    } else if (joinType == 'student') {
        if (joinDate == '15' || joinDate == '16')
            return '30,000';
        else
            return '50,000';
    } else if (joinType == 'sponsor') {
        return 0;
    } else {
        return -1;
    }

}

function validation() {
    $('#joinform').submit();
}

function paymentProcess(frm) {
    var params = {
        merchant_uid: new Date().getTime().toString(),
    };

    // validated values
    $(frm).find('input.valid').each(function () {
        params[$(this).attr('id')] = $(this).val();
    });

    params['amount'] = parseInt($('#amount').val().replace(',', ''));
    params['name'] = $("input[name='joinType']:checked").val() + "_" + $("input[name='joinDate']:checked").val();

    var _day15_launch = $("input[name='day15_launch']:checked").val()? $("input[name='day15_launch']:checked").val():'150';
    var _day16_launch = $("input[name='day16_launch']:checked").val()? $("input[name='day16_launch']:checked").val():'160';

    params['buyer_postcode'] = _day15_launch + '-' + _day16_launch;

    join_third = $('#join_third').val();
    if ( join_third == 'true' ) {
        params['join_third'] = join_third;
    };

    if ( params['amount'] == 0 ) {
        $.ajax({
            method: 'POST',
            url: '/apply_sponsor.php',
            data: params,
            dataType: 'json',
            success: function(data){
                console.log(data);return false;
                if ( data.success ) {
                    alert('등록이 완료되었습니다.');
                    move_apply_complete(params);
                } else {
                    alert(data.message);
                }
            }
        });
    } else {
        IMP.SBCR.init( 'imp11702026' );
        IMP.SBCR.onetime( params, function (rsp) {
            var that = this; //팝업창을 핸들링할 수 있습니다.
            if (rsp.token) { //token이 생성되어야 하며, 서버에서 아임포트 REST API로 결제요청할 때 반드시 필요합니다.

                var _keys = Object.keys(params);
                for(var i=0; i< _keys.length; i++){
                    if ( !rsp[_keys[i]] )
                        rsp[_keys[i]] = params[_keys[i]];
                }

                $.ajax({
                    method: 'POST',
                    url: '/payment.php', //아임포트 REST API를 호출할 나의 서버 주소
                    data: rsp, //rsp를 그대로 post합니다.
                    dataType: 'json'
                }).done(function (result) {
                    that.close(); //팝업창 닫기
                    if (result.success) { //xhr success(payment.php참조)
                        alert('결제가 완료되었습니다.');
                        move_apply_complete(params);
                    } else {
                        alert(result.message);
                    }
                }).fail(function(){
                    console.log(arguments);
                });
            } else {
                alert(rsp.message);
            }
        });
    }
}

function move_apply_complete(param) {
    setCookie('user_info', JSON.stringify(param), 1);
    location.href='/apply_complete.html';
}