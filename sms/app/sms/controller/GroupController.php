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
use app\sms\model\SmsGroupCustomerModel;
use app\sms\model\SmsGroupModel;
use app\sms\model\SmsGroupSessionModel;
use app\sms\model\SmsSessionModel;
use cmf\controller\UserBaseController;
use think\Db;

class GroupController extends UserBaseController
{
    public function index()
    {
        $groupModel = new SmsGroupModel();

        $groups = $groupModel->order('create_time desc')->paginate();

        $this->assign('groups', $groups);
        $this->assign('page', $groups->render());

        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $data = $this->request->param();

        $result = $this->validate($data, 'Group');

        if ($result !== true) {
            $this->error($result);
        }

        $reqData = [
            'label'           => $data['name'],
            'customer_number' => '265607'
        ];

        $response = sms_curl_post('https://api.apeiron.io/v2/sms/lists', $reqData);

        $result = json_decode($response['result'], true);

        if (!empty($result['list_id'])) {
            $groupModel          = new SmsGroupModel();
            $data['sms_list_id'] = $result['list_id'];

            $groupModel->save($data);
            $this->success('created success!');
        } else {
            $this->success('created failed!');
        }


    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        $groupModel = new SmsGroupModel();
        $group      = $groupModel->where('id', $id)->find();

        if (empty($group)) {
            $this->error('This customer not found!');
        }
        $this->assign('group', $group);

        return $this->fetch();
    }

    public function editPost()
    {
        $data = $this->request->param();

        $result = $this->validate($data, 'Group');

        if ($result !== true) {
            $this->error($result);
        }

        $groupModel = new SmsGroupModel();

        $groupModel->save($data, ['id' => $data['id']]);

        $this->success('save success!');
    }

    public function delete()
    {

        $id = $this->request->param('id', 0, 'intval');

        $groupModel = new SmsGroupModel();

        $groupModel->where('id', $id)->delete();

//        $groupCustomerModel = new SmsGroupCustomerModel();
//
//        $groupCustomerModel->where('group_id', $id)->delete();

        $this->success('Delete success!');

    }

    public function contacts()
    {
        $id                 = $this->request->param('id', 0, 'intval');
        $data               = $this->request->param();
        $groupCustomerModel = new SmsGroupCustomerModel();

        $this->assign('group_id', $id);

        $groupModel = new SmsGroupModel();
        $group      = $groupModel->where('id', $id)->find();
        $this->assign('group', $group);

        $where = [];
        if (!empty($data['name'])) {
            $where['customer.name'] = ['like', "%{$data['name']}%"];
        }

        if (!empty($data['phone'])) {
            $where['customer.mobile'] = ['like', "%{$data['phone']}%"];
        }

        $customers = $groupCustomerModel->alias('group_customer')
            ->field('customer.*,group_customer.id AS group_customer_id')
            ->join('__SMS_CUSTOMER__ customer', 'group_customer.customer_id=customer.id')
            ->where('group_customer.group_id', $id)
            ->where($where)
            ->paginate();

        $this->assign('customers', $customers);
        $this->assign('page', $customers->render());


        return $this->fetch();
    }

    public function addContact()
    {
        $customerModel = new SmsCustomerModel();

        $data = $this->request->param();
        $id   = $this->request->param('id', 0, 'intval');

        if (empty($id)) {
            $this->error('please select the group!');
        }

        $groupModel = new SmsGroupModel();
        $group      = $groupModel->where('id', $id)->find();
        $this->assign('group', $group);

        $this->assign('group_id', $id);

        $groupCustomerIds = Db::name('sms_group_customer')
            ->where(['group_id' => $id])
            ->column('customer_id');

        $this->assign('group_customer_ids', $groupCustomerIds);

        $where = [];
        if (!empty($data['name'])) {
            $where['name'] = ['like', "%{$data['name']}%"];
        }

        if (!empty($data['phone'])) {
            $where['mobile'] = ['like', "%{$data['phone']}%"];
        }


        $customers = $customerModel->where($where)->order('create_time DESC')->paginate(30);

        $this->assign('customers', $customers);
        $this->assign('page', $customers->render());

        return $this->fetch('add_contact');
    }

    public function addContactPost()
    {
        $groupId = $this->request->param('group_id', 0);

        if (empty($groupId)) {
            $this->error('The group id is required!');
        }


        $customerId = $this->request->param('customer_id', 0);

        $ids = $this->request->param('ids/a');


        if (empty($customerId) && empty($ids)) {
            $this->error('The customer id is required!');
        }

        $groupModel = new SmsGroupModel();

        $findGroup = $groupModel->where('id', $groupId)->find();

        if (empty($findGroup)) {
            $this->error('group not exists!');
        }

        if (empty($ids)) {
            $ids = [$customerId];
        }

        $customerModel = new SmsCustomerModel();

        foreach ($ids as $customerId) {
            $findCustomer = $customerModel->where('id', $customerId)->find();

            if (!empty($findCustomer)) {
                $findGroupCustomerCount = Db::name('sms_group_customer')
                    ->where(['customer_id' => $customerId, 'group_id' => $groupId])
                    ->count();

                if ($findGroupCustomerCount == 0) {
                    $response = sms_curl_post('https://api.apeiron.io/v2/sms/lists/' . $findGroup['sms_list_id'], [
                        'number' => $findCustomer['mobile']
                    ]);

                    $result = json_decode($response['result'], true);
                    //if (!empty($result['sms_list_id'])) {
                    Db::name('sms_group_customer')->insert([
                        'group_id'    => $groupId,
                        'customer_id' => $customerId,
                        'mobile'      => $findCustomer['mobile']
                    ]);
                    // }
                }
            }

        }

        $this->success('add customer to group success!', url('Group/contacts', ['id' => $groupId]));
    }

    public function deleteContact()
    {
        $id = $this->request->param('id', 0, 'intval');

        $groupCustomerModel = new SmsGroupCustomerModel();

        $findGroupCustomer = $groupCustomerModel->where('id', $id)->find();

        $groupModel = new SmsGroupModel();
        $findGroup  = $groupModel->where('id', $findGroupCustomer['group_id'])->find();

        $response = sms_curl("https://api.apeiron.io/v2/sms/lists/{$findGroup['sms_list_id']}/{$findGroupCustomer['mobile']}", null, 'DELETE');
//        print_r($response);exit;

        $groupCustomerModel->where('id', $id)->delete();

        $this->success('delete success!');
    }

    public function messages()
    {
        $groupId = $this->request->param('id', 0);

        $this->assign('group_id', $groupId);

        $groupSessionModel = new SmsGroupSessionModel();

        $groupModel = new SmsGroupModel();
        $group      = $groupModel->where('id', $groupId)->find();
        $this->assign('group', $group);

        $sessions = $groupSessionModel->where('group_id', $groupId)->order('create_time DESC')->paginate();

        $sessions->each(function ($item, $key) {
            $notSentCount   = Db::name('sms_group_message')->where(['session_id' => $item['id'], 'sent_time' => 0])->count();
            $sentCount      = Db::name('sms_group_message')->where(['session_id' => $item['id'], 'sent_time' => ['gt', 0]])->count();
            $deliveredCount = Db::name('sms_group_message')->where(['session_id' => $item['id'], 'status' => 1])->count();


            $item['not_sent_count']  = $notSentCount;
            $item['sent_count']      = $sentCount;
            $item['delivered_count'] = $deliveredCount;
            return $item;
        });

        $this->assign('sessions', $sessions);
        $this->assign('page', $sessions->render());

        return $this->fetch();
    }

    public function sendMessageDialog()
    {

        $groupId = $this->request->param('id', 0);

        $this->assign('group_id', $groupId);

        $groupModel = new SmsGroupModel();
        $group      = $groupModel->where('id', $groupId)->find();
        $this->assign('group', $group);

        $numbers = db('sms_number')->select();

        $this->assign('numbers', $numbers);

        return $this->fetch('send_message_dialog');
    }

    public function sendMessage()
    {
        ignore_user_abort(true);
        $data = $this->request->param();

        $result = $this->validate($data, 'GroupSendMessage');

        if ($result !== true) {
            $this->error($result);
        }

        $groupId = intval($data['group_id']);

        $from = $data['from'];

        $newFromNumbers = [];

        foreach ($from as $number) {
            array_push($newFromNumbers, preg_replace("/^1/", '', $number));
        }

        $from = implode(',', $newFromNumbers);


        $groupModel = new SmsGroupModel();

        $findGroup = $groupModel->where('id', $groupId)->find();

        if (empty($findGroup)) {
            $this->error('group not exists!');
        }

        $groupCustomerModel = new SmsGroupCustomerModel();

        $customerIds = $groupCustomerModel->where('group_id', $groupId)->column('customer_id');

        if (empty($customerIds)) {
            $this->error('no contact in this group, please add!');
        }

        //$customerModel = new SmsCustomerModel();

        //$customers = $customerModel->field('id,mobile')->where('id', 'in', $customerIds)->select();

        $currentTime = time();
        $messageIds  = [];

        $response = sms_curl_post("https://api.apeiron.io/v2/sms/campaigns", [
            'customer_number' => 265607,
            'label'           => "From " . $from,
            'body'            => $data['message']
        ]);

        file_put_contents('group_send_message.log', var_export($response, true) . "\n\n\n", FILE_APPEND);

        $result = json_decode($response['result'], true);

        if (!empty($result['campaign_id'])) {
            $groupSessionId = Db::name('sms_group_session')->insertGetId([
                'group_id'        => $groupId,
                'create_time'     => $currentTime,
                'from_number'     => $from,
                'content'         => $data['message'],
                'sms_campaign_id' => $result['campaign_id'],

            ]);

            $runResponse = sms_curl("https://api.apeiron.io/v2/sms/campaigns/{$result['campaign_id']}", [
                'list_id'      => $findGroup['sms_list_id'],
                'from_numbers' => $from,
                'coding'       => 'ASCII'
            ], 'PUT');
            file_put_contents('group_send_message.log', var_export([
                    'list_id'     => $findGroup['sms_list_id'],
                    'from_number' => $from
                ], true) . "\n\n\n", FILE_APPEND);
            file_put_contents('group_send_message.log', var_export($runResponse, true) . "\n\n\n", FILE_APPEND);
            $runResult = json_decode($runResponse['result'], true);

            if (!empty($runResult['run_id'])) {
                Db::name('sms_group_session')->where('id', $groupSessionId)->update([
                    'sms_campaign_run_id' => $runResult['run_id']
                ]);
            }
        }


        $this->success('send message request has created success!', null, ['ids' => $messageIds]);

    }

    public function sessionStatus()
    {
        $sessionId = $this->request->param('id', 0, 'intval');

        $findSession = db('sms_group_session')->where('id', $sessionId)->find();

        if (empty($findSession)) {
            $this->error('error');
        } else {
            if (empty($findSession['sms_campaign_id']) || empty($findSession['sms_campaign_run_id'])) {
                $this->error('error');
            }

            $url      = "https://api.apeiron.io/v2/sms/campaigns/{$findSession['sms_campaign_id']}/{$findSession['sms_campaign_run_id']}";
            $response = sms_curl_get($url);
            file_put_contents('SMS_Group_sessionStatus.log', $url . "\n", FILE_APPEND);
            file_put_contents('SMS_Group_sessionStatus.log', $response . "\n\n\n", FILE_APPEND);
            $result = json_decode($response, true);

            $this->success('success', null, $result);
        }

    }


}