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
use cmf\controller\UserBaseController;

class CustomerController extends UserBaseController
{
    public function index()
    {
        $customerModel = new SmsCustomerModel();

        $data = $this->request->param();

        $where = [];
        if (!empty($data['name'])) {
            $where['name'] = ['like', "%{$data['name']}%"];
        }

        if (!empty($data['phone'])) {
            $where['mobile'] = ['like', "%{$data['phone']}%"];
        }

        $customers = $customerModel->where($where)->order('create_time desc')->paginate();

        $this->assign('customers', $customers);
        $this->assign('page', $customers->render());

        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $data = $this->request->param();

        $result = $this->validate($data, 'Customer');

        if ($result !== true) {
            $this->error($result);
        }

        $customerModel = new SmsCustomerModel();

        $customerModel->save($data);

        $this->success('created success!');
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        $customerModel = new SmsCustomerModel();
        $customer      = $customerModel->where('id', $id)->find();

        if (empty($customer)) {
            $this->error('This customer not found!');
        }
        $this->assign('customer', $customer);

        return $this->fetch();
    }

    public function editPost()
    {
        $data = $this->request->param();

        $result = $this->validate($data, 'Customer');

        if ($result !== true) {
            $this->error($result);
        }

        $customerModel = new SmsCustomerModel();

        $customerModel->save($data, ['id' => $data['id']]);

        $this->success('save success!');
    }

    public function delete()
    {

        $id = $this->request->param('id', 0, 'intval');

        $customerModel = new SmsCustomerModel();

        $customerModel->where('id', $id)->delete();

        $this->success('Delete success!');

    }
}
