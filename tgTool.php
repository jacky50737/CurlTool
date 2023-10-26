<?php
/**
 * 開發者 User
 * 創建於 2022/5/22
 * 使用   PhpStorm
 * 專案名稱vpn_post
 */

require_once('class/CurlTool.class.php');
header('Content-type:application/json;charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 隱藏Warning
error_reporting(E_ERROR | E_PARSE);

$status = "暫無狀態";
$message = "暫無訊息";
$json = array("ok" => $status, "message" => $message);

// 讀取tg_update.json，如果檔案不存在，則建立一個
if (!file_exists('tg_setting.json')) {
    $fileContent = [
        "groupSetting"=>[
            "normal"=>
            [
                "id"=>1,
                "name"=>"普通群",
                "chat_id"=>"-1002007199214",
            ],
        ],
        "adminAccount"=>[
            "jacky"=>
            [
                "id"=>1,
                "name"=>"Jacky",
                "chat_id"=>"1129399459",
                "traget_chat_id"=>"1129399459",
            ],
            "Ming"=>
            [
                "id"=>2,
                "name"=>"Ming",
                "chat_id"=>"5699809960",
                "traget_chat_id"=>"5699809960",
            ],
        ],
        "botAccount"=>[
            [
                "id"=>1,
                "name"=>"普通",
                "accountToken"=>"6419780092:AAHC8M0IHJbM_7NAyEiV5wRDYyWEH6bUr8c",
                "update_id"=>"159955016"
            ],
        ]
    ];
    $file = fopen('tg_setting.json', 'w');
    fwrite($file, json_encode($fileContent));
    fclose($file);
}

$file = json_decode(file_get_contents('tg_setting.json'), true);
//驗證通過 開始主程式
$curl = new CurlTool();
try {
    $result = [];
    $jacky  = [];
    $ming   = [];
    // 依照帳號逐一勞取更新
    foreach ($file['botAccount'] as $botKey => $botAccount){
        // 初始化部分參數
        $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["count"] = 0;
        $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["gruoup_count"] = 0;

        $last_update_id = 0;

        // 紀錄更新ID Log
        save_log("INFO", "目前更新ID:".$botAccount['update_id']);

        save_log("INFO", "開始查詢".$botAccount['name']."(".$botAccount['id'].")"."的訊息，從更新ID:".$botAccount['update_id']." 開始");

        // 取得更新
        $magResults = $curl->doGet("https://api.telegram.org/bot".$botAccount['accountToken']."/getUpdates?offset=".$botAccount['update_id']."&limit=100&timeout=10");
        $count = count($magResults->result)??0;
        if($count){
            foreach($magResults->result as $msg){
                // 判斷是否為管理員Jacky的訊息
                if(strval($msg->message->from->id) == $file['adminAccount']['jacky']['chat_id']){
                    $tmpData = [];
                    // 判斷是否為message_id
                    if(isset($msg->message->message_id)){
                        $tmpData['message_id'] = $msg->message->message_id;
                    }

                    // 判斷是否為文字訊息
                    if(isset($msg->message->text)){
                        $tmpData['text'] = $msg->message->text;
                    }

                    // 判斷是否為圖片訊息
                    if(isset($msg->message->photo)){
                        $tmpData['photo'] = $msg->message->photo;
                    }

                    // 判斷是否為檔案訊息
                    if(isset($msg->message->document)){
                        $tmpData['document'] = $msg->message->document;
                    }

                    // 判斷是否為影片訊息
                    if(isset($msg->message->video)){
                        $tmpData['video'] = $msg->message->video;
                    }

                    // 判斷是否為聲音訊息
                    if(isset($msg->message->voice)){
                        $tmpData['voice'] = $msg->message->voice;
                    }

                    // 判斷是否為影片caption
                    if(isset($msg->message->caption)){
                        $tmpData['caption'] = $msg->message->caption;
                    }

                    // 判斷是否有media_group_id
                    if(isset($msg->message->media_group_id)){
                        $tmpData['media_group_id'] = $msg->message->media_group_id;
                    }

                    if(isset($msg->message->forward_from_message_id)){
                        $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["msgs"][] = [
                            "msg" => $tmpData
                        ];
                    }
                }
                $last_update_id = $msg->update_id;
            }

            $gruoup = [];
            $onGroup = [];
            // 將有相同media_group_id的訊息放入$gruoup
            foreach ($result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["msgs"] as $msg){
                if(isset($msg['msg']['media_group_id'])){
                    // 判斷是否已經有media_group_id
                    $gruoup[$msg['msg']['media_group_id']][] = $msg;
                    // 紀錄massage_id
                    $onGroup[] = $msg['msg']['message_id'];
                }
            }

            // 將以放入$gruoup的訊息從$magResults->result [Jacky]中移除
            foreach ($result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["msgs"] as $key => $msg){
                if(in_array($msg['msg']['message_id'], $onGroup)){
                    unset($result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["msgs"][$key]);
                }
            }

            // 計算Jacky的訊息數量
            $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["count"] = count($result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["msgs"]??[]);
            if(!empty($gruoup)){
                $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["gruoup"] = $gruoup;
            }

            // 計算Jacky的Group訊息數量
            $result[$botAccount['name']."(".$botAccount['id'].")"]["Jacky"]["gruoup_count"] = count($gruoup??[]);

            $result[$botAccount['name']."(".$botAccount['id'].")"]["All"] = [
                "start_update_id"=> $botAccount['update_id'], 
                "count"=> $count,
                "msg" => $magResults->result
            ];

            // 更新設定檔的update_id
            $file['botAccount'][$botKey]['update_id'] = $last_update_id;
            // 紀錄更新ID Log
            save_log("INFO", "更新ID:".$last_update_id);

            // 寫入新設定檔
            $fileContent = [
                "groupSetting"=>[
                    "normal"=>
                    [
                        "id"=>1,
                        "name"=>"普通群",
                        "chat_id"=>"-1002007199214",
                    ],
                ],
                "adminAccount"=>[
                    "jacky"=>
                    [
                        "id"=>1,
                        "name"=>"Jacky",
                        "chat_id"=>"1129399459",
                        "traget_chat_id"=>"1129399459",
                    ],
                    "Ming"=>
                    [
                        "id"=>2,
                        "name"=>"Ming",
                        "chat_id"=>"1129399459",
                        "traget_chat_id"=>"1129399459",
                    ],
                ],
                "botAccount"=>[
                    [
                        "id"=>1,
                        "name"=>"普通",
                        "accountToken"=>"6419780092:AAHC8M0IHJbM_7NAyEiV5wRDYyWEH6bUr8c",
                        "update_id"=>$file['botAccount'][0]['update_id']+1
                    ],
                ]
            ];
            $file = fopen('tg_setting.json', 'w');
            fwrite($file, json_encode($fileContent));
            fclose($file);

            $file = json_decode(file_get_contents('tg_setting.json'), true);
        }
    }

    // 處理Jacky的轉發訊息
    foreach ($result as $botKey => $botAccount){
        foreach ($botAccount as $adminKey => $adminAccount){
            if($adminKey == "Jacky"){
                foreach($adminAccount['msgs'] as $msg){
                    $medias = [];
                    if(isset($msg['msg']['photo'])){
                        $medias[] = [
                            "type" => "photo",
                            "media" => $msg['msg']['photo'][0]->file_id,
                            "caption" => $msg['msg']['caption']??null,
                        ];
                    }
                    if(isset($msg['msg']['document'])){
                        $medias[] = [
                            "type" => "document",
                            "media" => $msg['msg']['document']->file_id,
                            "caption" => $msg['msg']['caption']??null,
                        ];
                    }
                    if(isset($msg['msg']['video'])){
                        $medias[] = [
                            "type" => "video",
                            "media" => $msg['msg']['video']->file_id,
                            "caption" => $msg['msg']['caption']??null,
                        ];
                    }
                    if(isset($msg['msg']['voice'])){
                        $medias[] = [
                            "type" => "voice",
                            "media" => $msg['msg']['voice']->file_id,
                            "caption" => $msg['msg']['caption']??null,
                        ];
                    }
                    // 發送訊息
                    sendMediaGroup($file['botAccount'][0]['accountToken'], $file['adminAccount']['jacky']['traget_chat_id'], $medias);
                }

                foreach ($adminAccount['gruoup'] as $groupKey => $group){
                    $medias = [];
                    foreach ($group as $msg){
                        if(isset($msg['msg']['photo'])){
                            $medias[] = [
                                "type" => "photo",
                                "media" => $msg['msg']['photo'][0]->file_id,
                                "caption" => $msg['msg']['caption']??null,
                            ];
                        }
                        if(isset($msg['msg']['document'])){
                            $medias[] = [
                                "type" => "document",
                                "media" => $msg['msg']['document']->file_id,
                                "caption" => $msg['msg']['caption']??null,
                            ];
                        }
                        if(isset($msg['msg']['video'])){
                            $medias[] = [
                                "type" => "video",
                                "media" => $msg['msg']['video']->file_id,
                                "caption" => $msg['msg']['caption']??null,
                            ];
                        }
                        if(isset($msg['msg']['voice'])){
                            $medias[] = [
                                "type" => "voice",
                                "media" => $msg['msg']['voice']->file_id,
                                "caption" => $msg['msg']['caption']??null,
                            ];
                        }
                    }
                    // 發送訊息
                    sendMediaGroup($file['botAccount'][0]['accountToken'], $file['adminAccount']['jacky']['traget_chat_id'], $medias);
                }
            }
        }
    }

    $status = "成功";
    $message = "成功";
    $data = [
        "setting" => $file,
        "result" => $result,
    ];
    sand_msg(json_msg($status, $message, $data));
}catch (Exception $exception){
    $status = "失敗";
    $message = $exception->getMessage();
    sand_msg(json_msg($status, $message));
}

// tg [GET]sendMediaGroup訊息
function sendMediaGroup($token, $chat_id, $medias)
{
    $mediaValue = '[';
    foreach ($medias as $key => $media){
        $mediaValue .= '{"type":"'.$media['type'].'","media":"'.$media['media'].'","caption":"'.$media['caption'].'"},';
    }
    // 移除最後一個逗號
    $mediaValue = substr($mediaValue, 0, -1);
    $mediaValue .= ']';
    save_log("INFO", "發送訊息:".$mediaValue);
    $url = "https://api.telegram.org/bot".$token."/sendMediaGroup?chat_id=".$chat_id."&media=". urlencode($mediaValue);
    $curl = new CurlTool();
    $results = $curl->doGet($url);
    save_log("INFO", "發送訊息結果:".json_encode($results));
}

function json_msg($status, $message, $data=null): array
{
    if(is_null($data)){
        return array("ok" => $status, "message" => $message);
    }else{
        return array("ok" => $status, "message" => $message, "data" => $data);
    }
}

function sand_msg($json)
{
    $response = json_encode($json);
    echo $response;
    die();
}

// 紀錄log到log.txt
function save_log($level, $msg){
    // 如果檔案不存在，則建立一個
    if (!file_exists('log.txt')) {
        $file = fopen('log.txt', 'w');
        fwrite($file, "");
        fclose($file);
    }
    //Log格式 時間:等級:訊息
    $log = date("Y-m-d H:i:s").":".$level.":".$msg."\n";
    
    // 寫入log到log.txt的最後一行
    $file = fopen('log.txt', 'a');
    fwrite($file, $log);
    fclose($file);
}