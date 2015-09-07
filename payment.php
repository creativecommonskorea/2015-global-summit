<?php
require_once('iamport.php'); //아임포트 SDK로드 PHP SDK 다운로드

const IMP_API_KEY = '3672305027793712'; //REST API 키
const IMP_API_SECRET = '7ypkFVOV9aLxAGQ7lvnnDnwrPlgNYxF5'; //REST API 시크릿

$api_payload = array(
	'token'=>$_POST['token'],
	'merchant_uid'=>$_POST['merchant_uid'],
	'amount'=>$_POST['amount'],
	'vat'=>NULL,(NULL이면 amount의 1/11로 자동계산, 비과세상품은 0으로 처리)
	'card_number'=>$_POST['card_number'],
	'expiry'=>$_POST['expiry'],
	'birth'=>$_POST['birth'],
	'pwd_2digit'=>$_POST['pwd_2digit']
);

$iamport = new Iamport(IMP_API_KEY, IMP_API_SECRET);
$result = $iamport->sbcr_onetime($api_payload); //REST API로 결제요청 후 결과 수신

header('Content-Type: application/json');
if ( $result->success ) {
	exit(json_encode(array('success'=>true, 'payment'=>$payment)));
} else {
	exit(json_encode(array('success'=>false, 'message'=>sprintf("카드결제실패 : [%s]%s", $result->error['code'], $result->error['message']))));
}
		