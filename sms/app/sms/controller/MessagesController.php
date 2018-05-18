<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\sms\controller;

use app\sms\model\SmsSessionModel;
use cmf\controller\UserBaseController;

class MessagesController extends UserBaseController
{
    public function inbound()
    {
        $messages = db('sms_message')->where(['type' => 2])->order('sent_time DESC')->paginate();

        $this->assign('messages', $messages);

        $this->assign('page', $messages->render());
        return $this->fetch();
    }

    public function outbound()
    {
        $messages = db('sms_message')->where(['type' => 1])->order('sent_time DESC')->paginate();

        $this->assign('messages', $messages);

        $this->assign('page', $messages->render());
        return $this->fetch();
    }

    public function send()
    {
        $basicToken = base64_encode('eden_admin:Tria7aNsmrTq4OZKBhlPOAuE2hiRHHZWSPYplsFHKygLUeczGsr424yK2EDnCgnc');
        $this->assign('basic_token', $basicToken);
        $numbers = db('sms_number')->select();

        $this->assign('numbers', $numbers);
        return $this->fetch();
    }

    public function sendPost()
    {
        ignore_user_abort(true);

        $data = $this->request->param();

        $result = $this->validate($data, 'Send');

        if ($result !== true) {
            $this->error($result);
        }

        $data['to'] = '1' . preg_replace("/^1/", "", $data['to']);

        $form     = $data['from'];
        $reqData  = ['to_number' => $data['to'], 'message' => $data['message']];
        $response = sms_curl_post('https://api.apeiron.io/v2/sms/' . $form, $reqData);

        $result = json_decode($response['result'], true);

        if ($response['http_code'] == 404 || $response['http_code'] == 429) {
            $this->error($result['detail']);
        }

        if ($response['http_code'] == 400) {
            $this->error("Bad request!");
        }

        $sessionModel = new SmsSessionModel();


        $findSession = $sessionModel->where([
            'from_number' => $data['from'],
            'to_number'   => $data['to'],
        ])->find();

        if (empty($findSession)) {
            $sessionModel->isUpdate(false)->save([
                'from_number' => $data['from'],
                'to_number'   => $data['to'],
                'status'      => 1
            ]);
            $sessionId = $sessionModel->id;
        } else {
            $sessionId = $findSession['id'];
            $sessionModel->isUpdate(true)->save([
                'update_time' => time(),
                'status'      => 1
            ], ['id' => $findSession['id']]);
        }

        $messageStatus = 0;

        if ($response['http_code'] == 201) {
            $messageStatus = 1;
        }

        db('sms_message')->insert([
            'message_id'  => $result['message_id'],
            'from_number' => $data['from'],
            'to_number'   => $data['to'],
            'content'     => $result['content'],
            'sent_time'   => strtotime($result['date_sent']),
            'type'        => 1,
            'status'      => $messageStatus,
            'session_id'  => $sessionId
        ]);

        if ($response['http_code'] == 201) {
            $this->success('Message sent!');
        } else {
            $this->error('Message not sent!');
        }

    }

    public function sendDialog()
    {
        $number  = $this->request->param('number', '');
        $numbers = db('sms_number')->select();

        $this->assign('numbers', $numbers);
        $this->assign('number', $number);
        return $this->fetch('send_dialog');
    }

    function test()
    {
        print_r($_SERVER);
    }

    function _sms_format_number($number)
    {
        return '(' . substr($number, 0, 3) . ')' . substr($number, 3, 4) . '-' . substr($number, 7, 3);
    }

    public function getNewMsg()
    {
        $id            = $this->request->param('id', 0, 'intval');
        $lastMessageId = db('sms_message')->where(['type' => 2])->order('id DESC')->value('id');
        if ($lastMessageId > $id) {
            $this->success('success', null, ['has_new' => 1, 'last_message_id' => $lastMessageId]);
        } else {
            $this->success('success', null, ['has_new' => 0]);
        }
    }
}
