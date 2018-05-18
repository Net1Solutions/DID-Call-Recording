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

use app\sms\model\SmsBlacklistModel;
use app\sms\model\SmsMessageModel;
use app\sms\model\SmsSessionModel;
use cmf\controller\UserBaseController;

class BlacklistController extends UserBaseController
{
    public function index()
    {
        $blacklistModel = new SmsBlacklistModel();
        $numbers        = $blacklistModel->order('create_time DESC')->paginate();

        $this->assign('numbers', $numbers);
        $this->assign('page', $numbers->render());
        return $this->fetch();
    }

    public function messages()
    {
        $number       = $this->request->param('number');
        $messageModel = new SmsMessageModel();

        $messages = $messageModel->where(['from_number' => $number])->order('sent_time DESC')->paginate();

        $this->assign('messages', $messages);
        $this->assign('page', $messages->render());
        return $this->fetch();

    }

    public function add()
    {

        $number = $this->request->param('number');

        $blacklistModel = new SmsBlacklistModel();

        $findNumberCount = $blacklistModel->where('number', $number)->count();

        $sessionModel = new SmsSessionModel();

        $sessionModel->where(['to_number' => $number])->update(['status' => 0]);

        if ($findNumberCount == 0) {
            $blacklistModel->insert(['number' => $number, 'create_time' => time()]);
        }

        $this->success('The number blocked success!');

    }

    public function cancel()
    {
        $number = $this->request->param('number');

        $blacklistModel = new SmsBlacklistModel();

        $blacklistModel->where('number', $number)->delete();

        $sessionModel = new SmsSessionModel();

        $sessionModel->where(['to_number' => $number])->update(['status' => 1]);


        $this->success('The number unblocked success!');
    }

}
