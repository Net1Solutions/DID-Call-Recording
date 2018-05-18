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
use cmf\controller\HomeBaseController;
use think\Process;

class WebHookController extends HomeBaseController
{
    public function receive()
    {
        $data = $this->request->param();

        file_put_contents('webhook.txt', var_export($data, true), FILE_APPEND);

        if (isset($data['method']) && $data['method'] == 'ping') {
            exit('{"response": "pong"}');
        }

        if (isset($data['method']) && $data['method'] == 'sms_inbound') {

            $sentTime   = strtotime($data['timestamp']);
            $toNumber   = $data['to_number'];
            $fromNumber = $data['from_number'];
            $content    = $data['content'];
            $messageId  = $data['msg_id'];


            $findSession = db('sms_session')->where([
                'from_number' => $toNumber,
                'to_number'   => $fromNumber
            ])->find();

            $findMessage = db('sms_message')->where('message_id', $messageId)->find();
            if ($findSession) {
                if (empty($findMessage)) {
                    db('sms_session')->where('id', $findSession['id'])->update([
                        'update_time' => $sentTime,
                        'status'      => 1,
                        'unread'      => ['exp', 'unread+1']
                    ]);
                }

                $sessionId = $findSession['id'];

            } else {
                $sessionId = db('sms_session')->insertGetId([
                    'from_number' => $toNumber,
                    'to_number'   => $fromNumber,
                    'create_time' => $sentTime,
                    'update_time' => $sentTime,
                    'unread'      => 1,
                    'status'      => 1
                ]);
            }

            if (empty($findMessage)) {
                db('sms_message')->insert([
                    'session_id'  => $sessionId,
                    'message_id'  => $messageId,
                    'from_number' => $fromNumber,
                    'to_number'   => $toNumber,
                    'content'     => $content,
                    'sent_time'   => $sentTime,
                    'type'        => 2,
                    'status'      => 1
                ]);
            }
        }

        if (isset($data['method']) && $data['method'] == 'sms_outbound') {
            $sentTime   = strtotime($data['timestamp']);
            $toNumber   = $data['to_number'];
            $fromNumber = $data['from_number'];
            $messageId  = $data['msg_id'];


            db('sms_message')->where('message_id', $messageId)->update([
                'sent_time' => $sentTime,
                'status'    => 1
            ]);

            db('sms_group_message')->where('message_id', $messageId)->update([
                'sent_time' => $sentTime,
                'status'    => 1
            ]);


        }

        file_put_contents('webhook.txt', "\n" . date('Y-m-d H:i:s') . "{$data['method']} success \n", FILE_APPEND);

        exit('success');
    }


}
