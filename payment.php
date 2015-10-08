<?php
/**
 * 결제 처리
 */
date_default_timezone_set('Asia/Seoul');

// 아임포트 SDK 로드
require_once('iamport.php');

// Parse SDK 로드
require 'autoload.php';
require_once 'mandrill-api-php/src/Mandrill.php'; //Not required with Composer

use Parse\ParseClient;
use Parse\ParseObject;

// API 크레덴셜 정리
define('IMP_STORE_ID', $_SERVER['IMP_STORE_ID']?: '');
define('IMP_API_KEY', $_SERVER['IMP_API_KEY']?: '');
define('IMP_API_SECRET', $_SERVER['IMP_API_SECRET']?: '');

$api_payload = array(
    'token' => '',        // onetime()에서 생성된 token
    'merchant_uid' => '', // 주문번호
    'amount' => '',       // 총 결제요청금액
    'vat' => '',          // 결제요청금액 중 부가세 (NULL또는 누락되면 amount의 1/11로 자동계산, 0이면 면세)
    'card_number' => '',  // 카드번호
    'expiry' => '',       // 유효기간(YYYY-MM)
    'birth' => '',        // 생년월일 6자리
    'pwd_2digit' => '',   // 카드 비밀번호 앞2자리
    'buyer_name' => '',     // 구매자 성함
    'buyer_email' => '',    // 구매자 이메일
    'buyer_tel' => '',      // 구매자 연락처
    'name' => '',           // 상품명
);

foreach ($api_payload as $key => $value) {
  if (!empty($_POST[$key])) {
    $api_payload[ $key ] = $_POST[ $key];
  }
}

// 이메일 형식 검사 추가
if ( !empty($save_data['buyer_email']) && !filter_var($save_data['buyer_email'], FILTER_VALIDATE_EMAIL) ) {
    exit( json_encode( array( 'success' => false, 'message' => "이메일 주소가 올바르지 않습니다." ) ) );
}

if (!empty( IMP_STORE_ID ) && !empty( IMP_API_KEY ) && !empty( IMP_API_SECRET )) {
  $iamport = new Iamport(IMP_API_KEY, IMP_API_SECRET);
  $result = $iamport->sbcr_onetime( $api_payload ); //REST API로 결제요청 후 결과 수신

  header('Content-Type: application/json');
  if ( $result->success ) {

      ParseClient::initialize( $_SERVER['P_APP_ID']?: '', $_SERVER['P_REST_KEY']?: '', $_SERVER['P_MASTER_KEY']?: '' );

      // 파스에 결제 정보를 저장합니다.
      $paidInfo = ParseObject::create("PaidInfo");
      $objectId = $paidInfo->getObjectId();
      $php = $paidInfo->get("elephant");

      foreach($api_payload as $key => $value) {
          if ( !in_array($key, ['token', 'vat', 'card_number', 'expiry', 'birth', 'pwd_2digit']) ) {
              if ( strcmp($key, 'amount') == 0 ) {
                  $paidInfo->set($key, (int)$value);
              } else {
                  $paidInfo->set($key, $value);
              }
          }
      }

      $paidInfo->set('buyer_eng_name', $_POST["buyer_eng_name"]);
      $paidInfo->set('buyer_addr', $_POST["buyer_addr"]);
      $paidInfo->set('buyer_launch', $_POST["buyer_postcode"]);

      // 3일차 참가 여부 추가
      if (!empty( $_POST['join_third'] ) && 'true' === $_POST['join_third'] ) {
        $paidInfo->set('join_third', true);
      } else {
        $paidInfo->set('join_third', false);
      }

      $paidInfo->save();

      $split_name = explode('_', $api_payload['name']);
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
          $joinType = '학생, 비영리단체';
      } elseif ( strcmp($split_name[0], 'ordinary') == 0 ) {
          $joinType = '일반';
      } else {
          $joinType = 'CC 후원회원';
      }

      $launch_arr = explode('-', $_POST["buyer_postcode"]);
      $launch_15 = '';
      $launch_16 = '';
      if ( (int)$launch_arr[0]%150 == 1 ) {
          $launch_15 = '15일 점심 비빔밥';
      } elseif ( (int)$launch_arr[0]%150 == 2 ) {
          $launch_15 = '15일 점심 우거지탕';
      } elseif ( (int)$launch_arr[0]%150 == 3 ) {
          $launch_15 = '15일 점심 유기농두부구이 (도시락)';
      } elseif ( (int)$launch_arr[0]%150 == 9 ) {
          $launch_15 = '15일 점심 식사 안함';
      }

      if ( (int)$launch_arr[1]%160 == 1 ) {
          $launch_16 = '16일 점심 비빔밥';
      } elseif ( (int)$launch_arr[1]%160 == 2 ) {
          $launch_16 = '16일 점심 우거지탕';
      } elseif ( (int)$launch_arr[1]%160 == 3 ) {
          $launch_16 = '16일 점심 유기농두부구이 (도시락)';
      } elseif ( (int)$launch_arr[1]%160 == 9 ) {
          $launch_15 = '16일 점심 식사 안함';
      }

      $results = array(
        'name' => $api_payload['buyer_name'],
        'email' => $api_payload['buyer_email'],
        'tel' => $api_payload['buyer_tel'],
        'organize' => $_POST['buyer_addr'],
        'joinDate' => $joinDate,
        'joinType' => $joinType,
        'launch' => $launch_15 . ' ' . $launch_16,
        'price' => $api_payload['amount'],
      );

      $mandrill = new Mandrill($_SERVER['MAIL_API_KEY']?: '');

      $mail_content = '<meta charset="UTF-8">';
      $mail_content .= '<div style="width: 800px;">';
      $mail_content .= '<img src="http://summit.cckorea.org/images/paidmail_head.png" alt="head" style="width: 800px;"/>';
      $mail_content .= '<div style="width:100%;text-align:center;">';
      $mail_content .= '<div style="width: 500px;margin: 0 auto;text-align: left; font-size:14px; padding: 40px 0;">';
      $mail_content .= '환영합니다! <br/><br/>';
      $mail_content .= $api_payload['buyer_name'] . '님의 CC Global Summit 2015 참가신청이 성공적으로 완료되었습니다. <br/>';
      $mail_content .= '다음의 참가 신청 내용을 확인해주시고, <br/>';
      $mail_content .= '참가 당일 <span style="color:#ef513c;">국립중앙박물관 대강당 앞 로비</span>에서 이름표를 받아가세요! <br/>';
      $mail_content .= '<div style="border-bottom:1px solid #a0a0a0; border-top:1px solid #a0a0a0; margin: 20px 0; padding: 30px;">';
      $mail_content .= '<table style="font-size: 13px;">';
      $mail_content .= '<tr>';
      $mail_content .= '<td>참가자명</td>';
      $mail_content .= '<td>'. $api_payload['buyer_name'] .'/'. $_POST['buyer_eng_name'] .'</td>';
      $mail_content .= '</tr>';
      $mail_content .= '<tr>';
      $mail_content .= '<td>이메일</td>';
      $mail_content .= '<td>' . $api_payload['buyer_email'] . '</td>';
      $mail_content .= '</tr>';
      $mail_content .= '<tr>';
      $mail_content .= '<td>연락처</td>';
      $mail_content .= '<td>' . $api_payload['buyer_tel'] . '</td>';
      $mail_content .= '</tr>';
      $mail_content .= '<tr>';
      $mail_content .= '<td>소속</td>';
      $mail_content .= '<td>' . $_POST['buyer_addr'] . '</td>';
      $mail_content .= '</tr>';
      $mail_content .= '<tr>';
      $mail_content .= '<td>참가날짜</td>';
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
      $mail_content .= '<span style="font-size:16px;font-weight: bold;">총 티켓 금액    '. $api_payload['amount'] .'원</span><br/><br/>';
      $mail_content .= '<span style="font-size:12px;color:#a0a0a0"> *참가 신청 내용 변경 및 취소는 10월 12일 금요일까지인 점 유의해주세요!</span><br/>';
      $mail_content .= '</div>';
      $mail_content .= '<a href="https://summit.cckorea.org" target="_blank" style="color:#ef513c;">CC Global Summit 2015</a>는<br/>';
      $mail_content .= '80여 개국의 다양한 참가자들이<br/>';
      $mail_content .= '여러분과 공통의 관심사를 가지고 있는 사람들과 생각을 나눌 수 있는 기회를 만들고자,<br/>';
      $mail_content .= 'Slack에 팀을 만들어 참가자 간의 활발한 교류를 기대하고 있습니다.<br/><br/>';
      $mail_content .= '여러분도 슬랙의 CC Global Summit 2015팀에 가입하여<br/>';
      $mail_content .= '써밋 행사가 지난 후에도 이어질 수 있는 네트워킹에 참여해 보세요!<br/><br/>';
      $mail_content .= '<a href="http://blog.hivearena.com/archives/3396" target="_blank" style="color:#ef513c;">Slack을 어떻게 이용할 수 있는지 살펴볼까요?</a><br/>';
      $mail_content .= '<a href="https://ccglobalsummit2015.herokuapp.com/" target="_blank" style="color:#ef513c;">슬랙 CC Global Summit 2015팀에 가입하러 가기</a><br/><br/><br/>';
      $mail_content .= '10월 16일 금요일 저녁 5시<br/>';
      $mail_content .= '전길남 박사님의 키노트 강연 후<br/>';
      $mail_content .= '40분간 진행될 전길남 박사님, Yochai Benkler(요하이 벤클러)의 대담에<br/>';
      $mail_content .= '여러분께서 두분께 하고 싶은 질문을 미리 등록할 수 있습니다.<br/><br/>';
      $mail_content .= '<a href="https://www.facebook.com/events/1455321544773645/" target="_blank" style="color:#ef513c;">질문 등록하러 가기</a><br/><br/><br/>';
      $mail_content .= '써밋 기간 중 16일 금요일 저녁엔 성수동 SAI에서 CC파티가 열립니다!<br/>';
      $mail_content .= '조금 더 캐주얼한 분위기에서 써밋의 열기를 마음껏 발산하며 교류의 시간을 가져요~<br/><br/>';
      $mail_content .= '<a href="https://docs.google.com/forms/d/1dztoUzY0vG8rTMXpGLOzCEa0OQlepEUtU1e6XtYLHkg/viewform" target="_blank" style="color:#ef513c;">CC파티 신청하러 가기</a><br/><br/><br/>';
      $mail_content .= '여러분이 참여하는 써밋, 지금부터 시작합니다~!<br/><br/><br/>';
      $mail_content .= '<a href="https://docs.google.com/forms/d/1TbYcyjGhpwpyDKQQJQIu-bqDQDTW0ACyqR8vUelW1yA/viewform?usp=send_form" target="_blank" style="color:#ef513c;">* 참가 신청 / 취소 / 변경은 10월 12일 금요일까지 받습니다. 이 점 유의해주세요!</a><br/>';
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
                      'email' => $api_payload['buyer_email'],
                      'name' => $api_payload['buyer_name'],
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


      exit( json_encode( array( 'success' => true, 'results' => $results ) ) );
  } else {
      exit( json_encode( array( 'success' => false, 'message' => sprintf("카드결제실패 : [%s]%s", $result->error['code'], $result->error['message'] ) ) ) );
  }
}
