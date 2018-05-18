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

use app\sms\model\SmsMessageModel;
use app\sms\model\SmsSessionModel;
use cmf\controller\UserBaseController;

class SessionController extends UserBaseController
{
    public function index()
    {
        $sessionModel = new SmsSessionModel();

        $data = $this->request->param();

        $where = ['status' => 1];

        if (isset($data['number'])) {
            $where['from_number|to_number'] = ['like', "{$data['number']}%"];
        }

        $id      = $this->request->param('id', 0, 'intval');
        $reading = $this->request->param('reading', 0, 'intval');

        if (!empty($id) && $reading) {
            $sessionModel->where('id', $id)->update(['unread' => 0]);
        }

        $sessions = $sessionModel->where($where)->order('update_time desc')->limit(20)->select();
        if (!$sessions->isEmpty()) {
            //$sessions->load('last_message');
        }

        $this->assign('sessions', $sessions);

        $messageModel = new SmsMessageModel();


        if (empty($id) && !$sessions->isEmpty()) {
            $id = $sessions[0]['id'];
        }

        $messages = $messageModel->where('session_id', $id)->order('sent_time ASC')->select();
        $session  = $sessionModel->where('id', $id)->find();

        $lastMessageId = $messageModel->where('type', 2)->order('id DESC')->value('id');

        $sessionNumbers = $sessionModel->distinct('from_number')->column('from_number');

        $this->assign('form_data', $data);

        $this->assign('last_message_id', $lastMessageId);

        $this->assign('messages', $messages);
        $this->assign('session_numbers', $sessionNumbers);
        $this->assign('session', $session);

        return $this->fetch();
    }

    public function messages()
    {
        $messageModel = new SmsMessageModel();
        $id           = $this->request->param('id', 0, 'intval');

        $messages = $messageModel->where('session_id', $id)->select();

        $this->assign('messages', $messages);

        return $this->fetch();
    }

    public function refresh()
    {
        $sessionModel = new SmsSessionModel();

        $data = $this->request->param();

        $where = ['status' => 1];

        if (isset($data['number'])) {
            $where['from_number|to_number'] = ['like', "%{$data['number']}%"];
        }

        $id      = $this->request->param('id', 0, 'intval');
        $reading = $this->request->param('reading', 0, 'intval');

        if (!empty($id) && $reading) {
            $sessionModel->where('id', $id)->update(['unread' => 0]);
        }

        $sessions = $sessionModel->where($where)->order('update_time desc')->limit(20)->select();
        if (!$sessions->isEmpty()) {
            // $sessions->load('last_message');
        }

        $this->assign('sessions', $sessions);

        $messageModel = new SmsMessageModel();


        if (empty($id) && !$sessions->isEmpty()) {
            $id = $sessions[0]['id'];
        }

        $messages       = $messageModel->where('session_id', $id)->order('sent_time ASC')->select();
        $session        = $sessionModel->where('id', $id)->find();
        $sessionNumbers = $sessionModel->distinct('from_number')->column('from_number');


        $this->assign('session', $session);
        $this->assign('messages', $messages);

        $menu = $this->fetch('menu');
        $chat = $this->fetch('chat');

        $this->success('success', null, ['menu' => $menu, 'chat' => $chat, 'session_numbers' => $sessionNumbers]);
    }

//    public function removeUnread()
//    {
//        $id           = $this->request->param('id', 0, 'intval');
//        $sessionModel = new SmsSessionModel();
//        $sessionModel->where('id', $id)->update(['unread' => 0]);
//        $this->success('success');
//    }

    function hide()
    {
        $id           = $this->request->param('id', 0, 'intval');
        $sessionModel = new SmsSessionModel();
        $sessionModel->where('id', $id)->update(['status' => 0]);
        $this->success('success');
    }

    public function fetchNumberNewMsg()
    {
        ignore_user_abort(true);
        session_write_close();
        $number  = $this->request->param('number');
        $numbers = explode(',', $number);

        foreach ($numbers as $number) {
            $number = substr($number, 1, 10);

            $findNumber = db('sms_number')->where('number', $number)->count();

            if ($findNumber == 0) {
                continue;
            }

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

        $this->success('success');
    }

    function test()
    {
        exit;
        $sessions = db('sms_session')->order('id DESC')->select();

        foreach ($sessions as $session) {
            $number          = preg_replace("/^1/", '', $session['to_number']);
            $findNumberCount = db('sms_number')->where('number', $number)->count();

            if ($findNumberCount > 0) {
                db('sms_session')->where('id', $session['id'])->update([
                    'to_number'   => $session['from_number'],
                    'from_number' => $session['to_number'],
                ]);
            }
        }
    }

    function test2()
    {
        $sessions = db('sms_session')->field('id,to_number,from_number')->where(['to_number' => '15713889978', 'from_number' => '17022536969'])->order('id DESC')->select();


        print_r($sessions);
    }

    function test3()
    {
        exit;
        echo db('sms_message')->where('session_id', 'in', '331,330,329,328')->update(['session_id' => 332]);
        echo "<br>";
        echo db('sms_session')->where('id', 'in', '331,330,329,328')->delete();
    }

    function test4()
    {
        exit;
        $sessions = db('sms_session')->field('id,to_number,from_number')->order('id DESC')->select();

        $sessionsNew = [];
        foreach ($sessions as $session) {
            $key = $session['from_number'] . '|' . $session['to_number'];
            if (isset($sessionsNew[$key])) {
                $sessionsNew[$key] = $sessionsNew[$key] + 1;
            } else {
                $sessionsNew[$key] = 1;
            }
        }

        foreach ($sessionsNew as $key => $count) {
            if ($count > 1) {
                echo $key . "\n";
//                $numbers    = explode('|', $key);
//                $fromNumber = $numbers[0];
//                $toNumber   = $numbers[1];
//                $sessions   = db('sms_session')->field('id,to_number,from_number')->where([
//                    'from_number' => $fromNumber,
//                    'to_number'   => $toNumber
//                ])->order('id DESC')->column('id,to_number,from_number', 'id');
//
//
//                $sessionIds = array_keys($sessions);
//
//                $sessionId = array_shift($sessionIds);
//
//                echo db('sms_message')->where('session_id', 'in', $sessionIds)->update(['session_id' => $sessionId]);
//               echo "\n";
//                echo db('sms_session')->where('id', 'in', $sessionIds)->delete();

            }
        }


    }

    function test5()
    {
        $result = sms_curl_get("https://api.apeiron.io/v2/customers");

        print_r($result);
    }

    function test6()
    {
        $result = sms_curl_get("https://api.apeiron.io/v2/sms/campaigns/7/8262");

        print_r($result);
    }

    function test7()
    {
        $result = sms_curl_get("https://api.apeiron.io/v2/sms/lists/17");

        $result = json_decode($result, true);

        $numbers = [];
        foreach ($result['numbers'] as $number) {
            array_push($numbers, $number['number']);
        }

        $notIn = db('sms_group_customer')->where([
            'group_id' => 4,
            'mobile'   => ['not in', $numbers]
        ])->select();

        print_r($notIn);
    }


    function getDidName()
    {
        $dids = $this->request->param('dids');

        $url = 'https://98.158.155.201/get_did.php?dids=' . $dids;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 检查证书中是否设置域名
        }
        $content = curl_exec($ch);
        curl_close($ch);
        $this->success('success', null, json_decode($content, true));
    }

    function getSessionsDidNames()
    {
        $sessionModel = new SmsSessionModel();
        $dids         = $sessionModel->column('distinct from_number');
        $dids         = join(',', $dids);
        $url          = 'https://98.158.155.201/get_did.php?';
        $ch           = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['dids' => $dids]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 检查证书中是否设置域名
        }
        $content = curl_exec($ch);
//        $info   = curl_getinfo($ch);
//        print_r($info);
//        print_r($content);
        curl_close($ch);
        $this->success('success', null, json_decode($content, true));

    }

}
