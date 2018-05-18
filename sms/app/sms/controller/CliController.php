<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\sms\controller;

use app\sms\model\SmsCustomerModel;
use app\sms\model\SmsSessionModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Process;

class CliController extends HomeBaseController
{
    public function index()
    {
        $pid = getmypid();
        file_put_contents('sms_cli_index.log', $pid . ' start ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        set_time_limit(0);

        $numbers = db('sms_number')->order('')->column('number');

        shuffle($numbers);

        foreach ($numbers as $number) {
            file_put_contents('sms_cli_index.log', $pid . ' number ' . $number . ' ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

            $result = sms_curl_get('https://api.apeiron.io/v2/sms/' . $number);
            $result = json_decode($result, true);

            if (!empty($result['results'])) {
                foreach ($result['results'] as $message) {
                    $sentTime    = strtotime($message['date_received']);
                    $findSession = db('sms_session')->where(['from_number' => $message['to_number'], 'to_number' => $message['from_number']])->find();
                    $findMessage = db('sms_message')->where('message_id', $message['message_id'])->find();
                    if ($findSession) {
                        if (empty($findMessage)) {
                            db('sms_session')->where('id', $findSession['id'])->update([
                                'update_time' => $sentTime,
                                'unread'      => ['exp', 'unread+1']
                            ]);
                        }

                        $sessionId = $findSession['id'];

                    } else {
                        $sessionId = db('sms_session')->insertGetId([
                            'from_number' => $message['to_number'],
                            'to_number'   => $message['from_number'],
                            'create_time' => $sentTime,
                            'update_time' => $sentTime,
                            'unread'      => 1,
                            'status'      => 1
                        ]);
                    }

                    if (empty($findMessage)) {
                        db('sms_message')->insert([
                            'session_id'  => $sessionId,
                            'message_id'  => $message['message_id'],
                            'from_number' => $message['from_number'],
                            'to_number'   => $message['to_number'],
                            'content'     => $message['content'],
                            'sent_time'   => $sentTime,
                            'type'        => 2,
                            'status'      => 1
                        ]);
                    }

                }
            }
        }

        file_put_contents('sms_cli_index.log', $pid . ' end ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

        exit('');
    }


    public function test()
    {
        file_put_contents('1.log', date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        echo date('Y-m-d H:i:s') . "\n";
        exit;
    }

    public function groupSendMessages()
    {
        if (!IS_CLI) {
            return;
        }

        $logFile = 'sms_group_send_messages_last_id.log';
        if (file_exists($logFile)) {
            $id = file_get_contents($logFile);
            $id = intval($id);
        } else {
            $id = 0;
        }

        $messages = Db::name('sms_group_message')->where([
            'sent_time' => 0,
            'id'        => ['gt', $id]
        ])->order('id ASC')->limit(50)->select();

        if ($messages->isEmpty()) {
            return;
        }

        $lastId = $messages[count($messages) - 1]['id'];

        file_put_contents($logFile, $lastId);

        foreach ($messages as $message) {
            $message['to_number'] = '1' . preg_replace("/^1/", "", $message['to_number']);

            $form     = $message['from_number'];
            $reqData  = ['to_number' => $message['to_number'], 'message' => $message['content']];
            $response = sms_curl_post('https://api.apeiron.io/v2/sms/' . $form, $reqData);

            file_put_contents('async.log', var_export($response, true), FILE_APPEND);

            $result = json_decode($response['result'], true);

//            if ($response['http_code'] == 404 || $response['http_code'] == 429) {
//                $this->error($result['detail']);
//            }
//
//            if ($response['http_code'] == 400) {
//                $this->error("Bad request!");
//            }
            /*$sessionModel = new SmsSessionModel();


            $findSession = $sessionModel->where([
                'from_number' => $message['from_number'],
                'to_number'   => $message['to_number'],
            ])->find();

            if (empty($findSession)) {
                $sessionModel->isUpdate(false)->save([
                    'from_number' => $message['from_number'],
                    'to_number'   => $message['to_number'],
                    'status'      => 1
                ]);
                $sessionId = $sessionModel->id;
            } else {
                $sessionId = $findSession['id'];
                $sessionModel->isUpdate(true)->save([
                    'update_time' => time()
                ], ['id' => $findSession['id']]);
            }

            $messageStatus = 0;

            if ($response['http_code'] == 201) {
                $messageStatus = 1;
            }

            db('sms_message')->insert([
                'message_id'  => $result['message_id'],
                'from_number' => $message['from_number'],
                'to_number'   => $message['to_number'],
                'content'     => $result['content'],
                'sent_time'   => strtotime($result['date_sent']),
                'type'        => 1,
                'status'      => $messageStatus,
                'session_id'  => $sessionId
            ]);
            */

            $messageId = empty($result['message_id']) ? "" : $result['message_id'];

            if (!empty($result)) {
                Db::name('sms_group_message')->where('id', $message['id'])->update([
                    'sent_time'  => time(),
                    'message_id' => $messageId,
                ]);
            }
        }

    }

    public function addNumberToSmsList()
    {
        $groups         = [];
        $groupCustomers = db('sms_group_customer')->select();

        foreach ($groupCustomers as $groupCustomer) {
            if (empty($groupCustomer['mobile'])) {
                $customer = db('sms_customer')->where('id', $groupCustomer['customer_id'])->find();
                db('sms_group_customer')->where('id', $groupCustomer['id'])->update(['mobile' => $customer['mobile']]);
                $groupCustomer['mobile'] = $customer['mobile'];
            }

            if (empty($groups[$groupCustomer['group_id']])) {
                $groups[$groupCustomer['group_id']] = db('sms_group')->where('id', $groupCustomer['group_id'])
                    ->find();
            }

            echo $groupCustomer['id'] . "\n";

            $response = sms_curl_post('https://api.apeiron.io/v2/sms/lists/' . $groups[$groupCustomer['group_id']]['sms_list_id'], [
                'number' => $groupCustomer['mobile']
            ]);

            $result = json_decode($response['result'], true);

            if (!empty($result['sms_list_id'])) {

            } else {
                if (!empty($result['detail'])) {
                    file_put_contents("addNumberToSmsList.log", $result['detail'] . "\n\n", FILE_APPEND);
                }

            }


        }
    }

}
