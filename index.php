<?php
namespace Api\Controller;
use Think\Controller;

class UserController extends BaseController {
    
    //构造函数
    protected function _initialize(){
        parent::_initialize();
        //加载系统配置信息
        $this->Users = D('User');
    }

    public function index(){
        echo "黄潇is stupid!";
        echo "new barnch";
        echo "create a new branch is array, array";
        $this->display();
    }

}