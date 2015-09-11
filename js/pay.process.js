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
        merchant_uid: performance.now(),
    };

    // validated values
    $(frm).find('input.valid').each(function () {
        params[$(this).attr('id')] = $(this).val();
    });

    params['amount'] = parseInt($('#amount').val().replace(',', ''));
    params['name'] = $("input[name='joinType']:checked").val() + "_" + $("input[name='joinDate']:checked").val();
    params['buyer_postcode'] = $("input[name='day15_launch']:checked").val()? $("input[name='day15_launch']:checked").val():'150' +
            $("input[name='day16_launch']:checked").val()? $("input[name='day16_launch']:checked").val():'160';

    IMP.SBCR.init( 'imp11702026' );
    IMP.SBCR.onetime( params, function (rsp) {
        var that = this; //팝업창을 핸들링할 수 있습니다.
        console.log( params, rsp.token );
        if (rsp.token) { //token이 생성되어야 하며, 서버에서 아임포트 REST API로 결제요청할 때 반드시 필요합니다.
            $.ajax({
                method: 'POST',
                url: '/payment.php', //아임포트 REST API를 호출할 나의 서버 주소
                data: rsp, //rsp를 그대로 post합니다.
                dataType: 'json'
            }).done(function (result) {
                that.close(); //팝업창 닫기
                if (result.success) { //xhr success(payment.php참조)
                    // 결제 정보 저장
                    Parse.initialize("o3zQftNAhRpAPBw8LD49oWfouLVh2fyjBHAXy86k", "WIZfNdTfsI271SP7qgUHICpUQwyixCwzPVZeBP21");
                    var PaidInfo = Parse.Object.extend("PaidInfo");
                    var paidInfo = new PaidInfo();
                    paidInfo.save({
                        amount: params['amount'],
                        name: params['name'],
                        buyer_name: params['buyer_name']
                    }, {
                    success: function(){
                        alert('결제가 완료되었습니다.');
                        console.log(arguments);
                    },
                    error: function() {
                        console.log(arguments);
                    }
                  });

                } else {
                    alert(result.message);
                }
            });
        } else {
            alert(rsp.message);
        }
    });
}
