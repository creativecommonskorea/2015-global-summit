<?php
/**
 * 결제 처리
 */

// 아임포트 SDK 로드
require_once('iamport.php');

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
);

foreach ($api_payload as $key => $value) {
  if (!empty($_POST[$key])) {
    $api_payload[ $key ] = $_POST[ $key];
  }
}

if (!empty( IMP_STORE_ID ) && !empty( IMP_API_KEY ) && !empty( IMP_API_SECRET )) {
  $iamport = new Iamport(IMP_API_KEY, IMP_API_SECRET);
  $result = $iamport->sbcr_onetime( $api_payload ); //REST API로 결제요청 후 결과 수신

  header('Content-Type: application/json');
  if ( $result->success ) {
      exit( json_encode( array( 'success' => true, 'payment' => $payment ) ) );
  } else {
      exit( json_encode( array( 'success' => false, 'message' => sprintf("카드결제실패 : [%s]%s", $result->error['code'], $result->error['message'] ) ) ) );
  }
}
