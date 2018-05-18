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
use app\sms\model\SmsGroupModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Process;

class ImportController extends HomeBaseController
{
    public function index()
    {
        if (!$fp = fopen(CMF_ROOT . 'app/sms/data/contacts.csv', 'r')) {
            $this->error('file not exists!');
        }

        $nameIndex   = 2;
        $mobileIndex = 4;
        $emailIndex  = 3;
        $remarkIndex = 6;

        $customerModel = new SmsCustomerModel();
        $groupModel = new SmsGroupModel();

        while (!feof($fp)) {

            $groupId = 4;

            $row = fgetcsv($fp);

            $mobile = trim($row[$mobileIndex]);
            $email  = trim($row[$emailIndex]);

            if (empty($mobile)) {
                continue;
            }

            $findContact = $customerModel->where('mobile', $mobile)->find();

            if (empty($findContact)) {
                $customerId = $customerModel->isUpdate(false)->insertGetId([
                    'name'        => $row[$nameIndex],
                    'mobile'      => $mobile,
                    'email'       => $email,
                    'remark'      => $row[$remarkIndex],
                    'create_time' => time(),
                    'update_time' => time()
                ]);
            } else {
                $customerId = $findContact['id'];
            }


            $findGroupCustomerCount = Db::name('sms_group_customer')->where([
                'group_id' => $groupId, 'customer_id' => $customerId])->count();

            if (empty($findGroupCustomerCount)) {
                Db::name('sms_group_customer')->insert([
                    'group_id'    => $groupId,
                    'customer_id' => $customerId,
                    'mobile'      => $mobile
                ]);

                $findGroup = $groupModel->where('id', $groupId)->find();

                $response = sms_curl_post('https://api.apeiron.io/v2/sms/lists/' . $findGroup['sms_list_id'], [
                    'number' => $mobile
                ]);

                print_r($response);
            }

            print_r($mobile);

        }

        fclose($fp);

    }


}
