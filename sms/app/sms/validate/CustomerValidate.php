<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\sms\validate;

use think\Validate;

class CustomerValidate extends Validate
{
    protected $rule = [
        // 用|分开
        'name'   => 'require',
        'mobile' => 'require|unique:sms_customer'
    ];

    protected $message = [
        'name.require'   => "The customer' full name is required!",
        'mobile.require' => "The customer' phone number is required!",
        'mobile.unique'  => 'The number has exists!'
    ];


}