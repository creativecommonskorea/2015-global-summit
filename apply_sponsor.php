<?php
/**
 * Created by IntelliJ IDEA.
 * User: kimbeomjun
 * Date: 15. 9. 15.
 * Time: 15:04
 */

date_default_timezone_set('Asia/Seoul');
// Parse SDK 로드
require 'autoload.php';
require_once 'mandrill-api-php/src/Mandrill.php'; //Not required with Composer

use Parse\ParseClient;
use Parse\ParseObject;

$save_data = array(
    'amount' => '0',
    'merchant_uid' => '',
    'buyer_name' => '',     // 구매자 성함
    'buyer_email' => '',    // 구매자 이메일
    'buyer_tel' => '',      // 구매자 연락처
    'name' => '',           // 상품명
    'buyer_eng_name' => '',
    'buyer_addr' => '',
    'buyer_launch' => '',
);

foreach ($save_data as $key => $value) {
    if (!empty($_POST[$key])) {
        $save_data[$key] = $_POST[$key];
    } else if ( strcmp($key, 'buyer_launch') == 0 ) {
        $save_data[$key] = $_POST['buyer_postcode'];
    }
}

try {
    ParseClient::initialize($_SERVER['P_APP_ID'] ?: '', $_SERVER['P_REST_KEY'] ?: '', $_SERVER['P_MASTER_KEY'] ?: '');

// 파스에 결제 정보를 저장합니다.
    $paidInfo = ParseObject::create("PaidInfo");
    $objectId = $paidInfo->getObjectId();
    $php = $paidInfo->get("elephant");

    foreach ($save_data as $key => $value) {
        if (strcmp($key, 'amount') == 0) {
            $paidInfo->set($key, (int)$value);
        } else {
            $paidInfo->set($key, $value);
        }
    }

    $paidInfo->save();

    $split_name = explode('_', $save_data['name']);
    $joinType = '';
    $joinDate = '';
    if ( (int)$split_name[1] == 15 ) {
        $joinDate = '2015년 10월 15일 (목)';
    } elseif ( (int)$split_name[1] == 16 ) {
        $joinDate = '2015년 10월 16일 (금)';
    } else {
        $joinDate = '2015년 10월 15,16일 (목, 금)';
    }

    if ( strcmp($split_name[0], 'student') == 0 ) {
        $joinType = '학생';
    } elseif ( strcmp($split_name[0], 'ordinary') == 0 ) {
        $joinType = '일반';
    } else {
        $joinType = 'CC 후원회원';
    }

    $launch_arr = explode('-', $save_data['buyer_launch']);
    $launch_15 = '';
    $launch_16 = '';
    if ( (int)$launch_arr[0]%150 == 1 ) {
        $launch_15 = '15일 점심 비빔밥';
    } elseif ( (int)$launch_arr[0]%150 == 2 ) {
        $launch_15 = '15일 점심 우거지탕';
    } elseif ( (int)$launch_arr[0]%150 == 3 ) {
        $launch_15 = '15일 점심 채식';
    } elseif ( (int)$launch_arr[0]%150 == 4 ) {
        $launch_15 = '15일 점심 선택안함';
    }

    if ( (int)$launch_arr[1]%160 == 1 ) {
        $launch_16 = '16일 점심 비빔밥';
    } elseif ( (int)$launch_arr[1]%160 == 2 ) {
        $launch_16 = '16일 점심 우거지탕';
    } elseif ( (int)$launch_arr[1]%160 == 3 ) {
        $launch_16 = '16일 점심 채식';
    } elseif ( (int)$launch_arr[1]%160 == 4 ) {
        $launch_16 = '16일 점심 선택안함';
    }

    $mandrill = new Mandrill($_SERVER['MAIL_API_KEY']?: '');

    $mail_content = '<meta charset="UTF-8">';
    $mail_content .= '<div style="width: 800px;">';
    $mail_content .= '<img src="http://summit.cckorea.org/images/paidmail_head.png" alt="head" style="width: 800px;"/>';
    $mail_content .= '<div style="width:100%;text-align:center;">';
    $mail_content .= '<div style="width: 500px;margin: 0 auto;text-align: left; font-size:14px; padding: 40px 0;">';
    $mail_content .= '환영합니다! <br/><br/>';
    $mail_content .= $save_data['buyer_name'] . '님의 CC Global Summit 2015 참가신청이 성공적으로 완료되었습니다. <br/>';
    $mail_content .= '다음의 참가 신청 내용을 확인해주시고, <br/>';
    $mail_content .= '참가 당일 <span style="color:#ef513c;">국립중앙박물관 대강당 앞 로비</span>에서 이름표를 받아가세요! <br/>';
    $mail_content .= '<div style="border-bottom:1px solid #a0a0a0; border-top:1px solid #a0a0a0; margin: 20px 0; padding: 30px;">';
    $mail_content .= '<table style="font-size: 13px;">';
    $mail_content .= '<tr>';
    $mail_content .= '<td>참가자명</td>';
    $mail_content .= '<td>'. $save_data['buyer_name'] .'/'. $save_data['buyer_eng_name'] .'</td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>이메일</td>';
    $mail_content .= '<td>' . $save_data['buyer_email'] . '</td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>연락처</td>';
    $mail_content .= '<td>' . $save_data['buyer_tel'] . '</td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>소속</td>';
    $mail_content .= '<td>' . $save_data['buyer_addr'] . '</td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>참가날짜/td>';
    $mail_content .= '<td>' . $joinDate .' </td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>참가유형</td>';
    $mail_content .= '<td>' . $joinType . '</td>';
    $mail_content .= '</tr>';
    $mail_content .= '<tr>';
    $mail_content .= '<td>점심식사</td>';
    $mail_content .= '<td>' . $launch_15 . ' ' . $launch_16 . '</td>';
    $mail_content .= '</tr>';
    $mail_content .= '</table>';
    $mail_content .= '<br>';
    $mail_content .= '<span style="font-size:16px;font-weight: bold;">총 티켓 금액    '. $save_data['amount'] .'원</span><br/><br/>';
    $mail_content .= '<span style="font-size:12px;color:#a0a0a0"> *참가 신청 내용 변경 및 취소는 10월 9일 금요일까지인 점 유의해주세요!</span><br/>';
    $mail_content .= '</div>';
    $mail_content .= '<a href="https://summit.cckorea.org" target="_blank" style="color:#ef513c;">CC Global Summit 2015</a>는<br/>';
    $mail_content .= '80여 개국의 다양한 참가자들이<br/>';
    $mail_content .= '여러분과 공통의 관심사를 가지고 있는 사람들과 생각을 나눌 수 있는 기회를 만들고자,<br/>';
    $mail_content .= 'Slack에 팀을 만들어 참가자 간의 활발한 교류를 기대하고 있습니다.<br/><br/>';
    $mail_content .= '여러분도 슬랙의 CC Global Summit 2015팀에 가입하여<br/>';
    $mail_content .= '서밋 행사가 지난 후에도 이어질 수 있는 네트워킹에 참여해 보세요!<br/><br/>';
    $mail_content .= '<a href="http://blog.hivearena.com/archives/3396" target="_blank" style="color:#ef513c;">Slack을 어떻게 이용할 수 있는지 살펴볼까요?</a><br/>';
    $mail_content .= '<a href="https://ccglobalsummit2015.herokuapp.com/" target="_blank" style="color:#ef513c;">슬랙 CC Global Summit 2015팀에 가입하러 가기</a><br/><br/>';
    $mail_content .= '마지막으로 10월 16일 금요일 저녁 5시<br/>';
    $mail_content .= '전길남 박사님의 키노트 강연 후<br/>';
    $mail_content .= '40분간 진행될 전길남 박사님, Yochai Benkler(요하이 벤클러)의 대담에<br/>';
    $mail_content .= '여러분께서 두분께 하고 싶은 질문을 미리 등록할 수 있습니다.<br/><br/>';
    $mail_content .= '<a href="#" style="color:#ef513c;">질문 등록하러 가기</a><br/><br/>';
    $mail_content .= '참여하는 써밋, 지금부터 시작합니다~!<br/>';
    $mail_content .= '</div>';
    $mail_content .= '</div>';
    $mail_content .= '<img src="https://summit.cckorea.org/images/paidmail_foot.png" alt="" style="width: 800px;"/>';
    $mail_content .= '</div>';

//    echo $mail_content;

    try {
        $message = array(
            'html' => $mail_content,
            'subject' => 'CC Global Summit 2015 참가신청이 성공적으로 완료되었습니다.',
            'from_email' => 'ccsummit2015@cckorea.org',
            'from_name' => 'CC Global Summit',
            'to' => array(
                array(
                    'email' => $save_data['buyer_email'],
                    'name' => $save_data['buyer_name'],
                    'type' => 'to'
                )
            ),
            'headers' => array('Reply-To' => 'ccsummit2015@cckorea.org'),
            'important' => false,
            'track_opens' => null,
            'track_clicks' => null,
            'auto_text' => null,
            'auto_html' => null,
            'inline_css' => null,
            'url_strip_qs' => null,
            'preserve_recipients' => null,
            'view_content_link' => null,
            'tracking_domain' => null,
            'signing_domain' => null,
            'return_path_domain' => null,
            'merge' => true,
            'merge_language' => 'mailchimp',
        );
        $async = false;
        $ip_pool = 'Main Pool';
//        $send_at = 'example send_at';
        $result = $mandrill->messages->send($message, $async, $ip_pool);

    } catch(Mandrill_Error $e) {
        // Mandrill errors are thrown as exceptions
        echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
        // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
        throw $e;
    }

    exit( json_encode( array( 'success' => true ) ) );
} catch (ParseException $ex) {
    exit( json_encode( array( 'success' => false, 'message' => "등록 정보 저장 시 에러가 발생했습니다.", 'detail' => $ex->getMessage() ) ) );
} catch (Exception $e) {
    exit( json_encode( array( 'success' => false, 'message' => "등록 정보 저장 시 에러가 발생했습니다." ) ) );
}