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

use cmf\controller\UserBaseController;

class NumberController extends UserBaseController
{
    public function fetchAll()
    {

        set_time_limit(0);
        //ignore_user_abort(true);
        db('sms_number')->execute('TRUNCATE ' . config('database.prefix') . 'sms_number');
        $this->fetchOneUrl('https://api.apeiron.io/v2/sms', 1);
        return 'j';
    }

    private function fetchOneUrl($url, $page)
    {
        echo "ddd";
        $result = sms_curl_get($url);
        echo $result;
        $result = json_decode($result, true);


        if (!empty($result['results'])) {
            $numbers = [];
            foreach ($result['results'] as $numberData) {
                array_push($numbers, ['number' => $numberData['number']]);
            }
            db('sms_number')->insertAll($numbers);
            unset($numbers);
        }

        file_put_contents('log.txt', $result['next'] . "\n", FILE_APPEND);
        if (!empty($result['next'])) {
            $page++;
            $this->fetchOneUrl('https://api.apeiron.io/v2/sms?page=' . $page,$page);
        }

    }




}
