<?php
/**
 * 開發者 User
 * 創建於 2022/5/22
 * 使用   PhpStorm
 * 專案名稱vpn_post
 */

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;

require_once('class/CurlTool.class.php');
header('Content-type:application/json;charset=utf-8');
header('Access-Control-Allow-Origin: *');
$status = "暫無狀態";
$message = "暫無訊息";
$json = array("ok" => $status, "message" => $message);

// 若讀取失敗
if (empty(json_decode(file_get_contents('php://input'), true))) {
    $status = "錯誤";
    $message = "POST沒有內容";
    sand_msg(json_msg($status, $message));
}

//拿取資料
$data = json_decode(file_get_contents('php://input'), true);

//密碼判斷
if (empty($data['PASSWORD']) or $data['PASSWORD'] != 'a8508123') {
    $status = "錯誤";
    $message = "密碼錯誤";
    sand_msg(json_msg($status, $message));
}
if (empty($data['HEADER']) or empty($data['BODY'])) {
    $status = "錯誤";
    $message = "缺少 HEADER 或 BODY";
    sand_msg(json_msg($status, $message));
}

if (empty($data['TYPE']) or ($data['TYPE'] != "GET" and $data['TYPE'] != "POST")) {
    $status = "錯誤";
    $message = "僅支援GET或POST方法!";
    sand_msg(json_msg($status, $message));
}

if (empty($data['URL'])) {
    $status = "錯誤";
    $message = "缺少URL!";
    sand_msg(json_msg($status, $message));
}

//驗證通過 開始主程式
$curl = new CurlTool();
try {
    switch ($data['TYPE']){
        case 'GET':

            $results = $curl->doGet($data['URL'], $data['HEADER']);
            $status = "成功";
            $message = $results;
            sand_msg(json_msg($status, $message));
        case 'POST':
            $results = $curl->doPost($data['URL'], $data['HEADER'], $data['BODY']);
            $status = "成功";
            $message = $results;
            sand_msg(json_msg($status, $message));
    }
}catch (Exception $exception){
    $status = "失敗";
    $message = $exception->getMessage();
    sand_msg(json_msg($status, $message));
}


#[ArrayShape(["ok" => "", "message" => ""])] function json_msg($status, $message): array
{
    return array("ok" => $status, "message" => $message);
}

#[NoReturn] function sand_msg($json)
{
    $response = json_encode($json);
    echo $response;
    die();
}