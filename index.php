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
        $this->display();
    }

    public function pwd_user_reset(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $user = $check_token['username'];
            $encrypt = M('member')->where("username = '$user'")->getField('encrypt');
            if(I('post.password')){    
                $password = M('member')->where("username = '$user'")->getField('password');
                if(md5(I('post.password').$encrypt) == $password){
                    $data['password'] = md5(I('post.newpassword').$encrypt);
                    $ret = M('member')->where("username = '$user'")->save($data);
                    $return['msg'] = '密码已重置';
                }
            }else{
                $rand = rand(10000,99999);
                $data['password'] = md5(md5($rand).$encrypt);
                $ret = M('member')->where("username = '$user'")->save($data);
                if($ret){
                    $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$user."&content=".rawurlencode("您的密码已重置，新密码为：".$rand."。");
                    $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
                    $gets =  xml_to_array(http($post_data, $target));
                    $return['data'] = $gets;
                    if($gets['SubmitResult']['code']==2){
                        $flag = 1;
                        $return['msg'] = '密码已重置,请等待含有新密码的短信通知';
                    }else{
                        $ret = 0;
                        $return['msg'] = $gets['SubmitResult']['msg'];
                    }
                }
            }
            if($ret){
                $return['status'] = 1;
            }else{
                $return['msg'] = '数据写入失败，请稍后重试';
            }

        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    public function uploadify()
    {
        if (!empty($_FILES)) {
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     5*1024*1024 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './Public/upload/file/'; // 设置附件上传根目录
            // 上传文件 
            $info   =   $upload->upload();

            if(!$info) {// 上传错误提示错误信息
                $data['error'] = $upload->getError();
                $data['status'] = 0;

            }else{// 上传成功 获取上传文件信息
                $data['picurl'] = './Public/upload/file/'.$info['Filedata']['savepath'].$info['Filedata']['savename'];
                $data['status'] = 1;

                $image = new \Think\Image(); 
                $image->open($data['picurl']);
                // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image->thumb(130, 130)->save('./Public/upload/images/'.$info['Filedata']['savename']);   
                $data['data'] = '/beidou/Public/upload/images/'.$info['Filedata']['savename'];
                $data['picurl'] = '/beidou/Public/upload/file/'.$info['Filedata']['savepath'].$info['Filedata']['savename'];
            }          

            $this->response($data,$this->defaultType);
        }
    }

    public function web_reg(){
        $return = array(
            'status'=>0,
            'msg'=>'参数非法',
            'data'=>I('post.mobile'),
            'verson'=>cookie('rand')
        );
        $rand_set = I('verson_code');
        
        $ip = get_client_ip();
        $verson_code_last = M('reg_log')->where("ip = '$ip' and status = 1")->order('id desc')->limit(1)->select();
        $rand_get = $verson_code_last['0']['code'];
        $return['verson'] = $rand_get;
        $return['versoncheck'] = is_numeric($rand_set);
        if($rand_get == $rand_set && is_numeric($rand_set)){
            $userinfo['code'] = I('post.code');
            $userinfo['mobile'] = I('post.mobile');
            $userinfo['pwd'] = I('post.password');
            $userinfo['nick'] = I('post.nickname');
            $ret = D('Member')->useradd($userinfo);
            if($ret['status'] == 1){
                //$this->success('注册成功',U('index'));
                $return['status'] = 1;
            }else{
                $return['status'] = $ret['status'];
                $return['msg'] = $ret['msg'];
                $return['data'] = $ret;
            }
        }
        $this->response($return,$this->defaultType);
    }

    /**
    *   @param mobile 手机号码
    *   @param type 验证码类型 1注册 2登录
    */

    public function mobile_code(){
        $return = array(
            'status'=>0,
            'msg'=>'参数非法',
            'data'=>array()
        );
        $mobile = I('post.mobile');
        $pattern = "/^1\d{10}$/";
        if(preg_match($pattern,$mobile)){
            $mobile_code = random(4,1);
            $type = I('post.type');
            /*
            if($type == 1){
            $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$mobile."&content=".rawurlencode("您正在注册，验证码是：".$mobile_code."。为了您的账户安全，请不要把验证码泄露给其他人。");
            }else if($type == 2){
            $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$mobile."&content=".rawurlencode("您正在登录，验证码是：".$mobile_code."。为了您的账户安全，请不要把验证码泄露给其他人。");    
            }*/
            $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$mobile."&content=".rawurlencode("您的验证码是：".$mobile_code."。请不要把验证码泄露给其他人。");
            $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
            $gets =  xml_to_array(http($post_data, $target));
            if($gets['SubmitResult']['code']==2){
                //session(array("$mobile"=>$mobile_code,'expire'=>300));
                $data = array(
                    'mobile'=>$mobile,
                    'code'=>$mobile_code,
                    'expire'=>time() + 300, 
                    'type'=>$type
                    );
                M('user_mobile_code')->add($data);
                $return['status'] = 1;
                $return['msg'] = "验证码已发送，请在5分钟内输入";
            }else{
                $return['status'] = -1;
                $return['msg'] = "验证码短信系统繁忙，请稍后再试";
                //$return['data'] = null;
            }
        }else{
            $return['msg'] = '请输入正确的手机号';
        }

        $this->response($return,$this->defaultType);
    }

    /**
    *   注册接口
    */
    public function reg(){
        
        //$verify = new \Think\Verify();
        $get_code = I('post.code');
        $return = array(
            'status'=>0,
            'msg'=>'注册失败',
            'data'=>array()
        );
        $username = I('post.mobile');
        $pattern = "/^1\d{10}$/";
        if (preg_match( $pattern, $username)){
            $map_code['mobile'] = $username;
            $map_code['expire'] = array('gt',time());
            $map_code['type'] = 1;
            $codeinfo = M('user_mobile_code')->where($map_code)->order('id desc')->limit(1)->select();
            $code = $codeinfo['0']['code'];
            if($code == $get_code){ 
                $usercheck = M('member')->where("username = '$username'")->find();
                if($usercheck){
                    $return['status'] = -3;
                    $return['msg'] = '手机号已被注册，如忘记密码可使用短信验证登录';
                }else{
                    $encrypt = rand(100000,999999);
                    $data['username'] = $username;
                    $data['encrypt'] = $encrypt;
                    $data['password'] = md5(I('post.password').$encrypt);
                    $data['lastloginip'] = get_client_ip();
                    $data['lastlogintime'] = time();
                    $data['regip'] = get_client_ip();
                    $data['regtime'] = time();
                    $data['nick'] = I('post.nickname');
                    $ret = M('member')->add($data);
                    if($ret){
                        $return['status'] = 1;
                        $return['msg'] = '注册成功，请前往登录';
                        $data['id'] = $ret;
                        $return['data'] = $data;
                    }else{
                        $return['msg'] = '注册失败，错误代码(HRDE001)';
                    }
                }
            }else{
                $return['status'] = -1;
                $return['msg']='验证码错误或已过期，请重新申请短信验证码';
            }
        }else{
            $return['status'] = -2;
            $return['msg'] = '请输入正确的手机号码';
        }
        $this->response($return,$this->defaultType);
        
    }

    public function login_check(){
        $username = I("post.mobile");
        $password = I("post.password");
        $get_code = I("post.code");
        $token = I('post.token');
        $all_data['data'] = json_encode(I());
        $all_data['model'] = 'app_login';
        M('request_log')->add($all_data);
        //要返回的数据
        $return = array(
            'status'=>0,
            'msg'=>'请输入用户名和密码',
            'data'=>array()
        );
        if($password){ 
            //密码登录
            $pattern = "/^1\d{10}$/";
            if (preg_match( $pattern, $username) || strlen($username) == 14 || strlen($username) == 15){
                $userinfo = D('Member')->where("username = '$username'")->find();
                $encrypt = $userinfo['encrypt'];
                if($userinfo['password'] == md5($password.$encrypt)){
                    $return['status'] = 1;
                    $return['msg'] = '确认登录状态成功';
                  
                }else{
                    $return['status'] = -5;
                    $return['msg'] = '密码错误请重试';
                }
            }else{
                $return['status'] = -9;
                $return['msg'] = '请输入正确的手机号';
            }
        }else{
            $return = array(
            'status'=>0,
            'msg'=>'无效请求，请确认您输入的账号密码',
            'data'=>$all_data['data']
            );
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户登录
     * request mehtod GET/POST
     * @param username string 用户手机号
     * @param password string 登录密码
     * @param code     string 短信验证码
     * @param token    steing 免密码登录令牌
     */
    public function login(){

        $username = I("post.mobile");
        $password = I("post.password");
        $get_code = I("post.code");
        $token = I('post.token');
        $all_data['data'] = json_encode(I());
        $all_data['model'] = 'app_login';
        M('request_log')->add($all_data);
        //要返回的数据
        $return = array(
            'status'=>0,
            'msg'=>'请输入用户名和密码',
            'data'=>array()
        );
        $type = I('post.type',0);
        if($type == 1){
            $data_login['type'] = 1;
        }
        if($password){ 
            //密码登录
            $pattern = "/^1\d{10}$/";
            if (preg_match( $pattern, $username) || strlen($username) == 14 || strlen($username) == 15){
	            $userinfo = D('Member')->where("username = '$username'")->find();
                $encrypt = $userinfo['encrypt'];
                if($userinfo['password'] == md5($password.$encrypt)){
                    
                    $userinfo['lastlogintime'] = time();
                    M('member')->where("username = '$username'")->save($userinfo);
                    M('login_token')->where("username = '$username' and type = '$type'")->delete();
                    
                    $data_login['date'] = time();
                    $data_login['currtime'] = date('Y-m-d H:i:s',$data_login['date']);
                    $data_login['expert'] = $data_login['date'] + 15 * 24 * 60 * 60;//默认登录有效时间30分钟
                    $data_login['token'] = md5($username.$data_login['currtime']);  
                    $data_login['username'] = $username;
                    $ret_login = M('login_token')->add($data_login);

                    $token = $data_login['token'];
                    
                    if($token){
                        $return['status'] = 1;
                        $return['msg'] = '登录成功';
                        $return['data']['token'] = $data_login['token'];
                        $return['data']['mobile'] = $username;
                        $return['data']['nickname'] = M('member')->where("username = '$username'")->getField('nick');
                        $return['data']['userId'] = M('member')->where("username = '$username'")->getField('memberid');
                        // $return['data']['shopId'] = M('business')->where("uid = '".$return['data']['userId']."'")->getField('id');
                        cookie('token',$return['data']['token'],3600);
                        cookie('nickname',$return['data']['nickname'],3600);
                        $userid = M('member')->where("username = '$username'")->getField('memberid');
                        if($userinfo['power']){
                            $user_shops = M('business')->where('id ='.$userinfo['operatetype'])->select();
                        }else{
                            $user_shops = M('business')->where("uid = '$userid'")->select();
                        }
                        
                        if($user_shops){
                            for($i=0;$i<count($user_shops);$i++){
                                $return['data']['shopinfo']["$i"]['shopid'] = $user_shops["$i"]['id'];
                                $return['data']['shopinfo']["$i"]['shopname'] = $user_shops["$i"]['name'];
                                $temp_shop_id = $user_shops["$i"]['id'];
                                //支付宝当面付开通测试检查
                                //$alipay_f2f_status_2 = M('business_detail')->where("shopid = '$temp_shop_id' and status = 2")->find();
                                $alipay_f2f_status_2 = M('orderlog')->where("status = 2 and shopid = '$temp_shop_id' and goodcategory = 9999")->find();
                                if($alipay_f2f_status_2){
                                    $return['data']['shopinfo']["$i"]['alipay_check'] = 1;
                                }else{
                                    $return['data']['shopinfo']["$i"]['alipay_check'] = 0;
                                }

                                $shopowner = $user_shops['0']['uid'];
                                $wechatpaycheck = M('member')->where("memberid = '$shopowner'")->find();
                                if($wechatpaycheck['wechat_shop_id']){
                                    $return['data']['shopinfo']["$i"]['wechatpay_check'] = 1;
                                }else{
                                    $return['data']['shopinfo']["$i"]['wechatpay_check'] = 0;
                                }

                                $shopset = M('shopset')->where("shopid = '$temp_shop_id' and status = 1")->find();
                                if($shopset){
                                    $return['data']['shopinfo']["$i"]['calculatetype'] = $shopset['calculatetype'];
                                }else{
                                    $return['data']['shopinfo']["$i"]['calculatetype'] = 0;
                                }

                            }
                        }
                    }else{
                        $return['status'] = -6;
                        $return['msg'] ='token生成失败，请稍后重试';
                    }
                }else{
                    $return['status'] = -5;
                    $return['msg'] = '密码错误请重试';
                }
            }else{
                $return['status'] = -9;
                $return['msg'] = '请输入正确的手机号';
            }
        }else if($token){
            //token令牌登录
            $pattern = "/^1\d{10}$/";
            if (preg_match( $pattern, $username)  || strlen($username) == 14 || strlen($username) == 15){
                $token_info = M('login_token')->where("username = '$username'")->find();
                M('login_token')->where("username = '$username'")->delete();
                if(time()<$token_info['expert'] && $token == $token_info['token']){
                    if($token == $token_info['token']){
                        $userinfo = D('member')->where("username = '$username'")->find();
                        $userinfo['lastlogintime'] = time();
                        M('member')->where("username = '$username'")->save($userinfo);
                         M('login_token')->where("username = '$username' and type = '$type'")->delete();
                        $data_login['date'] = time();
                        $data_login['currtime'] = date('Y-m-d H:i:s',$data_login['date']);
                        $data_login['expert'] = $data_login['date'] + 15 * 24 * 60 * 60;//默认登录有效时间30分钟
                        $data_login['token'] = md5($username.$data_login['currtime']);  
                        $data_login['username'] = $username;
                        $ret_login = M('login_token')->add($data_login);
                        if($ret_login){
                            $return['status'] = 1;
                            $return['msg'] = '登录成功';
                            $return['data']['token'] = $data_login['token'];
                            $return['data']['mobile'] = $username;
                            $return['data']['nickname'] = M('member')->where("username = '$username'")->getField('nick');
                            $return['data']['userId'] = M('member')->where("username = '$username'")->getField('memberid');
                            cookie('token',$return['data']['token']);
                            cookie('nickname',$return['data']['nickname']);
                            $userid = M('member')->where("username = '$username'")->getField('memberid');
                            if($userinfo['power']){
                                $user_shops = M('business')->where('id ='.$userinfo['operatetype'])->select();
                            }else{
                                $user_shops = M('business')->where("uid = '$userid'")->select();
                            }
                            if($user_shops){
                                for($i=0;$i<count($user_shops);$i++){
                                    $return['data']['shopinfo']["$i"]['shopid'] = $user_shops["$i"]['id'];
                                    $return['data']['shopinfo']["$i"]['shopname'] = $user_shops["$i"]['name'];
                                    $temp_shop_id = $user_shops["$i"]['id'];
                                    //支付宝当面付开通测试检查
                                    //$alipay_f2f_status_2 = M('business_detail')->where("shopid = '$temp_shop_id' and status = 2")->find();
                                    $alipay_f2f_status_2 = M('orderlog')->where("status = 2 and shopid = '$temp_shop_id' and goodcategory = 9999")->find();
                                    if($alipay_f2f_status_2){
                                        $return['data']['shopinfo']["$i"]['alipay_check'] = 1;
                                    }else{
                                        $return['data']['shopinfo']["$i"]['alipay_check'] = 0;
                                    }

                                    $shopowner = $user_shops['0']['uid'];
                                    $wechatpaycheck = M('member')->where("memberid = '$shopowner'")->find();
                                    if($wechatpaycheck['wechat_shop_id']){
                                        $return['data']['shopinfo']["$i"]['wechatpay_check'] = 1;
                                    }else{
                                        $return['data']['shopinfo']["$i"]['wechatpay_check'] = 0;
                                    }

                                    $shopset = M('shopset')->where("shopid = '$temp_shop_id' and status = 1")->find();
                                    if($shopset){
                                        $return['data']['shopinfo']["$i"]['calculatetype'] = $shopset['calculatetype'];
                                    }else{
                                        $return['data']['shopinfo']["$i"]['calculatetype'] = 0;
                                    }
                                }
                            }
                        }else{
                            $return['status'] = -6;
                            $return['msg'] ='token生成失败，请稍后重试';
                        }
                    }else{
                        $return['status'] = -7;
                        $return['msg'] = '无效登录，请重新登录';
                    }
                }else{
                    $return['status'] = -8;
                    $return['msg'] = '登录已超时，请重新登录';
                }
            }else{
                $return['status'] = -9;
                $return['msg'] = '请输入正确的手机号';
            }
        }else if($get_code){
            //手机短信验证码登录
            $map_code['mobile'] = $username;
            $map_code['expire'] = array('gt',time());
            $map_code['type'] = 2;
            $codeinfo = M('user_mobile_code')->where($map_code)->order('id desc')->limit(1)->select();
            $code = $codeinfo['0']['code'];
            if($code == $get_code){ 
                $userinfo = D('member')->where("username = '$username'")->find();
                $userinfo['lastlogintime'] = time();
                M('member')->where("username = '$username'")->save($userinfo);
                $data_decode['type'] = -2;
                M('user_mobile_code')->where($map_code)->save($data_decode);
                M('login_token')->where("username = '$username' and type = '$type'")->delete();
                $data_login['date'] = time();
                $data_login['currtime'] = date('Y-m-d H:i:s',$data_login['date']);
                $data_login['expert'] = $data_login['date'] + 15 * 24 * 60 * 60;//默认登录有效时间30分钟
                $data_login['token'] = md5($username.$data_login['currtime']);  
                $data_login['username'] = $username;
                $ret_login = M('login_token')->add($data_login);
                if($ret_login){
                    $return['status'] = 1;
                    $return['msg'] = '登录成功';
                    $return['data']['token'] = $data_login['token'];
                    $return['data']['mobile'] = $username;
                    $return['data']['nickname'] = M('member')->where("username = '$username'")->getField('nick');
                    $return['data']['userId'] = M('member')->where("username = '$username'")->getField('memberid');
                    cookie('token',$return['data']['token']);
                    cookie('nickname',$return['data']['nickname']);
                    $userid = M('member')->where("username = '$username'")->getField('memberid');
                    $user_shops = M('business')->where("uid = '$userid'")->select();
                    if($user_shops){
                        for($i=0;$i<count($user_shops);$i++){
                            $return['data']['shopinfo']["$i"]['shopid'] = $user_shops["$i"]['id'];
                            $return['data']['shopinfo']["$i"]['shopname'] = $user_shops["$i"]['name'];

                            $temp_shop_id = $user_shops["$i"]['id'];
                            //支付宝当面付开通测试检查
                            //$alipay_f2f_status_2 = M('business_detail')->where("shopid = '$temp_shop_id' and status = 2")->find();
                            $alipay_f2f_status_2 = M('orderlog')->where("status = 2 and shopid = '$temp_shop_id' and goodcategory = 9999")->find();
                            if($alipay_f2f_status_2){
                                $return['data']['shopinfo']["$i"]['alipay_check'] = 1;
                            }else{
                                $return['data']['shopinfo']["$i"]['alipay_check'] = 0;
                            }

                            $shopowner = $user_shops['0']['uid'];
                            $wechatpaycheck = M('member')->where("memberid = '$shopowner'")->find();
                            if($wechatpaycheck['wechat_shop_id']){
                                $return['data']['shopinfo']["$i"]['wechatpay_check'] = 1;
                            }else{
                                $return['data']['shopinfo']["$i"]['wechatpay_check'] = 0;
                            }

                            $shopset = M('shopset')->where("shopid = '$temp_shop_id' and status = 1")->find();
                            if($shopset){
                                $return['data']['shopinfo']["$i"]['calculatetype'] = $shopset['calculatetype'];
                            }else{
                                $return['data']['shopinfo']["$i"]['calculatetype'] = 0;
                            }
                        }
                    }
                }else{
                    $return['status'] = -6;
                    $return['msg'] ='token生成失败，请稍后重试';
                }

            }else{
                $return['status'] = -10;
                $return['msg']='验证码错误或已过期，请重新申请短信验证码';
            }
        }else{
            $return = array(
            'status'=>0,
            'msg'=>'无效登录请求，请确认您输入的账号密码',
            'data'=>array()
            );
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    public function user_photo_upload_file(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $category = I('category');
            $name = I('name');
            $file = I('request.file');
            $orderid = I('orderid',0);
            $upfile = autoupload();
            if($upfile['status']){
                //$data['id'] = $no + 100000;
                $data_n['no'] = date('YmdH',time());
                $data_n['orderid'] = $orderid;
                $data_n['shopid'] = 100000;
                $data_n['status'] = 1;
                $data_n['name'] = $name;
                $data_n['photo'] = '/beidou'.$upfile['picurl'];
                $data_n['intro'] = '';
                $data_n['price'] = '0.00';
                $data_n['category'] = $category;
                $ret = M('goods')->add($data_n);
                if($ret){
                    $return['status'] = 1;
                    $return['msg'] = '商品添加成功';
                }else{
                    $return['status'] = -9;
                    $return['msg'] = '商品添加失败';
                    $return['data'] = $data_n;
                }
            }else{
                $return['status'] = -5;
                $return['msg'] = $autoupload['error'];
            }


            //$return['status'] = 1;
            //$return['msg'] = '图片上传';
            $return['data'] = $upfile;//"picurl": "./Public/upload/file/2016-07-12/5784e886c308d.jpg","status": 1
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }
    


    /**
     * 用户图片上传
     * request mehtod POST
     */

    public function user_photo_upload(){
        
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $type = I('type');
            $file = I('photo');
            $upfile = D('User')->upBase64($file);
            $newFilePath = $upfile['url'];
            $username = $check_token['username'];
            $ret = 0;
            
            switch ($type) {
                case 1://头像
                    $datas['head'] = $newFilePath;
                    $ret = M('member')->where("username = '$username'")->save($datas);
                    break;
                case 2://身份证照片
                    $temp_data['status'] = 0;
                    M('users')->where("username = '$username'")->save($temp_data);
                    $datas['photo'] = $newFilePath;
                    $datas['username'] = $username;
                    $datas['status'] = 1;
                    $ret = M('users')->add($datas);
                    break; 
                case 3://营业执照100004
                    //$data['license'] = $newFilePath;
                    //$ret = M('member')->where("uid = '$username'")->save($data);
                    $no = M('pic_log')->count();
                    $datas['id'] = $no + 100000;
                    $datas['type'] = 3;
                    $datas['picurl'] = $newFilePath;
                    $datas['status'] = 1;
                    $ret = M('pic_log')->add($datas);
                    break;
                case 4://商品图片
                    $no = M('pic_log')->count();
                    $datas['id'] = $no + 100000;
                    $datas['type'] = 4;
                    $datas['picurl'] = $newFilePath;
                    $datas['status'] = 1;
                    $ret = M('pic_log')->add($datas);
                    break;    
                case 5://企业logo100005
                    $shopid = I('post.shopid');
                    $datas['logo'] = $newFilePath;
                    $ret = M('business')->where("id = '$shopid'")->save($datas);
                    break;   
                default:
                    # code...
                    break;
            }
            if($ret){
                $return['status'] = 1;
                $return['msg'] = '图片上传成功';
                $return['data'] = $upfile;
                //$return['photo'] = $file;
                if($type == 3 || $type == 4){
                    $return['data'] = $datas['id'];
                }
            } else {
                $return['status'] = -10;
                $return['msg'] = '图片上传失败';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户登出
     * request mehtod GET/POST
     * @param access_token 登录成功返回的数据认证签名
     * @param userid 登录成功返回的用户id
     */
    public function logout(){
        $token = I('post.token');
        $map_token['token'] = $token;
        $ret = M('login_token')->where($map_token)->delete();
        //要返回的数据
        $return = array(
                'status'=>1,
                'msg'=>'退出登录成功',
                'data'=>array()
        );
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户店铺信息获取
     * request mehtod POST
     * @param token  令牌
     */
    public function shop_info_get(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $map['uid'] = $userid;
            $map['status'] = 1;
            $shops = M('business')->where($map)->select();
            $data = array();
            for($i=0;$i<count($shops);$i++){
                $data["$i"]['shopid'] = $shops["$i"]["id"];
                $data["$i"]['name'] = $shops["$i"]["name"];
                $data["$i"]['address'] = $shops["$i"]["province"]." ".$shops["$i"]["city"]." ".$shops["$i"]["address"];
                $data["$i"]['logo'] = $shops["$i"]["logo"];
                if($shops["$i"]["authentication"] == 1){
                    $data["$i"]["log"] = "认证店铺";
                }else{
                    $data["$i"]["log"] = "未认证店铺";
                }
            }
            $num = count($data);
            if($num){
                $return['status'] = 1;
                $return['msg'] = "店铺信息获取成功";
                $return['data'] = $data;
            }else{
                $return['status'] = -9;
                $return['msg'] = "用户未拥有任何店铺";
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户申请开店（商户申请）
     * request mehtod POST
     * @param token  令牌
     * @param mobile 用户手机号
     * @param array  表单信息
     */
    public function shop_apply(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $username = I('post.mobile');
            $userid = M('member')->where("username = '$username'")->getField('memberid');
            $shopinfo = M('business')->where("uid = '$userid'")->select();
            if($userid && !$shopinfo){
                $no = M('business')->count();
                $data['id'] = $no + 100000;
                $data['uid'] = $userid;
                $data['no'] = date('YmdH',time());
                $data['name'] = I('post.shopname');
                $data['province'] = I('post.province');
                $data['city'] = I('post.city');
                $data['address'] = I('post.address');
                $data['identification'] = I('post.identification');
                $data['authentication'] = 0;
                $data['license'] = I('post.license');
                $data['status'] = 1;

                $ret = M('business')->add($data);
                if($ret){
                    $return['status'] = 1;
                    $return['msg'] = '商户申请已成功提交';
                }else{
                    $return['status'] = -9;
                    $return['msg'] = '数据写入失败，请稍后重试';
                }
            }else{
                if($shopinfo){
                    $return['status'] = -10;
                    $return['msg'] = '您已经申请过店铺';
                }else{
                    $return['status'] = -10;
                    $return['msg'] = '手机账号不存在！';
                }
                
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户（非店主）商品信息种类信息获取
     * request mehtod POST
     * @param token  令牌
     * @param count  每页数据数量
     * @param page   页码
     * @param order  排序方式1，id升序；2，id降序；3，待添加
     */
    public function goods_category_get(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $count = I('post.count',20);
            $page = I('post.page',1);
            $order = I('order');
            switch ($order) {
                case 1:
                    $order_detail = 'id asc';
                    break;
                case 2:
                    $order_detail = 'id desc';
                    break;
                case 3:
                    $order_detail = 'id asc';
                    break;
                default:
                    $order_detail = 'id asc';
                    break;
            }
            $limit = "".(($page - 1) * $count).",".$count."";
            $data = M('goods_category')->where("status = 1")->order($order_detail)->limit($limit)->select();
            if($data){
                $return['status'] = 1;
                $return['msg'] = '商铺种类信息获取成功';
                $return['data'] = $data;
                $return['count'] = $count;
                $return['page'] = $page;
                $return['allcount'] = M('goods_category')->where("status = 1")->count();
            }else{
                $return['status'] = -2;
                $return['msg'] = '暂无更多商品种类信息';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户（非店主）商品信息信息获取
     * request mehtod POST
     * @param token  令牌
     * @param category 商铺种类id
     * @param count  每页数据数量
     * @param page   页码
     * @param order  排序方式1，id升序；2，id降序；3，待添加
     */
    public function goods_auto_get(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>array()
        );
        if($check_token['status']){
            $count = I('post.count',20);
            $page = I('post.page',1);
            $order = I('order');
            switch ($order) {
                case 1:
                    $order_detail = 'gid asc';
                    break;
                case 2:
                    $order_detail = 'gid desc';
                    break;
                case 3:
                    $order_detail = 'gid asc';
                    break;
                default:
                    $order_detail = 'gid asc';
                    break;
            }
            $map['status'] = 1;
            if(I('category')){
                $map['category'] = I('category');
                $category = I('category');
                $return['category'] = M('goods_category')->where("id = '$category'")->find();
            }
            if(I('shopid')){
                $map['shopid'] = I('shopid');
            }
            $limit = "".(($page - 1) * $count).",".$count."";
            $data = M('goods')->where($map)->order($order_detail)->limit($limit)->select();
            if($data){
                for($i=0;$i<count($data);$i++){
                    $temp = $data["$i"]["photo"];
                    if(is_numeric($temp)){
                        $data["$i"]["picurl"] = M('pic_log')->where("id = '$temp'")->getField('picurl');
                    }else{
                        $data["$i"]["picurl"] = $temp;
                    }
                }
                $return['status'] = 1;
                $return['msg'] = '商品信息获取成功';
                $return['data'] = $data;
                $return['count'] = $count;
                $return['page'] = $page;
                $return['allcount'] = M('goods')->where($map)->count();
            }else{
                $return['status'] = -2;
                $return['msg'] = '暂无更多商品信息';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户货物种类信息获取（店主）
     * request mehtod POST
     * @param token  令牌
     * @param shopid   用户店铺id
     */

    public function shop_category_info_get(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>''
        );
        if($check_token['status']){
            $shopid = I('post.shopid');
            $shopowner = M('business')->where("id = '$shopid'")->getField('uid');
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $user_power = M('member')->where("username = '$user'")->getField('power');
            $user_shop = M('member')->where("username = '$user'")->getField('operatetype');
            if($userid == $shopowner ||($user_shop == $shopid && ($user_power > 0) )){
                $return['data'] = M('goods_category')->where("shopid = '$shopid' and status = 1")->select();
                if($return['data']){
                    $return['status'] = 1;
                    $return['msg'] = '店铺商品种类信息获取成功';
                }else{
                    $return['status'] = -9;
                    $return['msg'] = '信息获取失败';
                }
            }else{
                $return['status'] = -10;
                $return['msg'] = '店铺不存在';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户货物信息获取
     * request mehtod POST
     * @param token  令牌
     * @param shop   用户店铺id
     * @param array  表单信息
     */
     public function goods_info_get(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>''
        );
        if($check_token['status']){
            $shopid = I('post.shopid');
            $shopowner = M('business')->where("id = '$shopid'")->getField('uid');
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $user_power = M('member')->where("username = '$user'")->getField('power');
            $user_shop = M('member')->where("username = '$user'")->getField('operatetype');
            if($userid == $shopowner||($user_shop == $shopid && ($user_power > 0) ) ){
                $type = I('post.type',1);
                switch ($type) {
                    case 1:
                        $order = 'orderid asc';
                        break;
                    case 2:
                        $order = 'gid desc';
                        break;
                    case 3:
                        $order = 'price asc';
                        break;
                    case 4:
                        $order = 'price desc';
                        break;
                    default:
                        $order = 'gid asc';
                        break;
                }
                if(I('category')){
                    $map['category'] = I('category');
                }
                $count = I('count',20);
                $page = I('page',1);
                $limit = "".(($page - 1) * $count).",".$count."";
                $map['status'] = 1;
                $map['shopid'] = $shopid;
                if(!I('post.all')){
                    $map['isbar'] = 0;
                }
                $goods = M('goods')->where($map)->order($order)->limit($limit)->select();
                $data = array();
                for($i=0;$i<count($goods);$i++){
                    $data["$i"]['goods'] = $goods["$i"]["gid"];
                    $data["$i"]['orderid'] =$goods["$i"]['orderid'];
                    $data["$i"]['no'] = $goods["$i"]["no"];
                    $data["$i"]['name'] = $goods["$i"]["name"];
                    $data["$i"]['intro'] = $goods["$i"]["intro"];
                    $data["$i"]['price'] = $goods["$i"]["price"];
                    $category = $goods["$i"]["category"];
                    if($category){
                        $temp_category = M('goods_category')->where("id = '$category'")->find();
                        $data["$i"]['category']['id'] = $temp_category['id'];
                        $data["$i"]['category']['name'] = $temp_category['name'];
                        $data["$i"]['category']['notes'] = $temp_category['notes'];
                        $data["$i"]['category']['shopid'] = $temp_category['shopid']; 
                    }
                    $picid = $goods["$i"]["photo"];
                    if(is_numeric($picid)){
                        $data["$i"]["picurl"] = M('pic_log')->where("id = '$picid'")->getField('picurl');
                    }else{
                        $data["$i"]["picurl"] = $picid;
                    }
                    $data["$i"]['stock'] = $goods["$i"]['stock'];
                    $data["$i"]['discount1'] = $goods["$i"]['discount1'];
                    if($goods["$i"]['discount2']){
                        $data["$i"]['discount2'] = $goods["$i"]['discount2'];
                    }else{
                        $data["$i"]['discount2'] = 0;
                    }
                    if($goods["$i"]['discount3']){
                        $data["$i"]['discount3'] = $goods["$i"]['discount3'];
                    }else{
                        $data["$i"]['discount3'] = 0;
                    }
                    $data["$i"]['bargoods'] = $goods["$i"]['isbar'];
                    $data["$i"]['intime'] = $goods["$i"]["intime"];
                    $data["$i"]['checkstatus'] = $goods["$i"]["checkstatus"];
                    $data["$i"]['method'] = $goods["$i"]['method'];
                    $data["$i"]['unit'] = $goods["$i"]['unit'];
                    //$data["$i"]['picurl'] = M('pic_log')->where("id = '$picid'")->getField('picurl');
                }
                $num = count($data);
                if($num){
                    $return['status'] = 1;
                    $return['msg'] = "店铺货物获取成功";
                    $return['data'] = $data;
                    $return['count'] = $count;
                    $return['page'] = $page;
                    $return['allcount'] = M('goods')->where($map)->count();

                }else{
                    $return['status'] = -9;
                    $return['msg'] = "店铺内暂无任何货物";
                }
            }else{
                $return['status'] = -10;
                $return['msg'] = '店铺不存在';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }
    /**
     * 用户货物入库
     * request mehtod POST
     * @param token  令牌
     * @param shop   用户店铺id
     * @param array  表单信息
     */
    public function goods_upload(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>''
        );
        if($check_token['status']){
            $shopid = I('post.shopid');
            $shopowner = M('business')->where("id = '$shopid'")->getField('uid');
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $user_power = M('member')->where("username = '$user'")->getField('power');
            $user_shop = M('member')->where("username = '$user'")->getField('operatetype');
            if($userid == $shopowner ||($user_shop == $shopid && ($user_power > 1) )){
                $no = M('goods')->count();
                $data['id'] = $no + 100000;
                $data['no'] = date('YmdH',time());
                $data['shopid'] = $shopid;
                $data['status'] = 1;
                $data['name'] = I('post.name');
                $data['photo'] = I('post.photo');
                $data['intro'] = I('post.intro');
                $data['price'] = I('post.price');
                if(I('post.discount1')){
                    $data['discount1'] = I('post.discount1');
                }
                if(I('post.discount2')){
                    $data['discount2'] = I('post.discount2');
                }
                if(I('post.discount3')){
                    $data['discount3'] = I('post.discount3');
                }
                if(I('post.method')){
                    $data['method'] = I('post.method');
                    if($data['method'] == 1)
                        $data['unit'] = I('post.unit','个');
                }
                if(I('post.cost')){
                    $data['cost'] = I('post.cost');
                }
                $data['category'] = I('post.category');
                $ret = M('goods')->add($data);
                if($ret){
                    $goodsinfo = M('goods')->where("gid = '$ret'")->find();
                    $return['status'] = 1;
                    $return['msg'] = '商品添加成功';
                    $return['data']['gid'] = $goodsinfo['gid'];
                    $return['data']['name'] = $goodsinfo['name'];
                    $return['data']['price'] = $goodsinfo['price'];
                }else{
                    $return['status'] = -9;
                    $return['msg'] = '商品添加失败';
                }
            }else{
                $return['status'] = -10;
                $return['msg'] = '店铺不存在';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户货物信息修改
     * request mehtod POST
     * @param token  令牌
     * @param shop   用户店铺id
     * @param goods  用户货物id
     * @param type   操作种类1：修改，2：删除
     * @param array  表单信息
     */
    public function goods_edit(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>''
        );
        if($check_token['status']){
            $shopid = I('post.shopid');
            $shopowner = M('business')->where("id = '$shopid'")->getField('uid');
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $user_power = M('member')->where("username = '$user'")->getField('power');
            $user_shop = M('member')->where("username = '$user'")->getField('operatetype');
            if($userid == $shopowner ||($user_shop == $shopid && ($user_power > 1) )){
                $operatetype = I('post.type');
                $gid = I('post.goods');
                $goodsinfo = M('goods')->where("gid = '$gid'")->find();
                //if($goodsinfo['shopid'] == $shopid){
                    
                    if($operatetype == 1){
                        if(I('post.name')){
                            $data['name'] = I('post.name');
                        }
                        if(I('post.category')){
                            $data['category'] = I('post.category');
                        }
                        if(I('post.photo')){
                            $data['photo'] = I('post.photo');
                        }
                        if(I('post.intro')){
                            $data['intro'] = I('post.intro');
                        }
                        $thisprice = I('post.price');
                        if(isset($thisprice) || $thisprice == 0){
                            $data['price'] = I('post.price');
                        }
                        if(I('post.cost')){
                            $data['cost'] = I('post.cost');
                        }
                        if(I('post.stock')){
                            $data['stock'] = I('post.stock');
                        }
                        if(I('post.discount1')){
                            $data['discount1'] = I('post.discount1');
                        }
                        if(I('post.discount2')){
                            $data['discount2'] = I('post.discount2');
                        }
                        if(I('post.discount3')){
                            $data['discount3'] = I('post.discount3');
                        }
                        $method = I('post.method');
                        if(isset($method)){
                            $data['method'] = I('post.method');
                            if(I('post.unit'))
                                $data['unit'] = I('post.unit','个');
                        }
                        $ret = M('goods')->where("gid = '$gid'")->save($data);
                        if($ret){
                            $return['status'] = 1;
                            $return['msg'] = '商品信息修改成功';
                        }else{
                            $return['status'] = -9;
                            $return['msg'] = '商品信息修改失败';
                        }
                    }else if($operatetype == 2){
                        $data['status'] = 0;
                        $ret = M('goods')->where("gid = '$gid'")->save($data);
                        if($ret){
                            $return['status'] = 1;
                            $return['msg'] = '商品删除成功';
                        }else{
                            $return['status'] = -9;
                            $return['msg'] = '商品删除失败';
                        }
                    }else if($operatetype == 3){
                        $data['stock'] = I('post.stock') + $goodsinfo['stock'];
                        $percent = I('post.stockpercent',0.2);
                        $data['noticenum'] = $data['stock'] * $percent;
                        if(I('post.cost')){
                            $data['cost'] = I('post.cost');
                        }
                        $thisprice = I('post.price');
                        if(isset($thisprice) || $thisprice == 0){
                            $data['price'] = I('post.price');
                        }
                        if(I('post.discount1')){
                            $data['discount1'] = I('post.discount1');
                        }
                        if(I('post.discount2')){
                            $data['discount2'] = I('post.discount2');
                        }
                        if(I('post.discount3')){
                            $data['discount3'] = I('post.discount3');
                        }
                        if(I('post.orderprice')){
                            $orderprice = I('post.orderprice');
                        }
                        if(I('post.method')){
                            $data['method'] = I('post.method');
                            if($data['method'] == 1 && I('post.unit'))
                                $data['unit'] = I('post.unit','个');
                        }
                        $data['intime'] = date('Y-m-d',time());
                        $ret = M('goods')->where("gid = '$gid'")->save($data);
                        $good_log['no'] = date('YmdHis',time()).rand(1000,9999);
                        $good_log['gid'] = $gid;
                        $good_log['uid'] = $check_token['userid'];
                        $good_log['price'] = isset($data['price'])?$data['price']:$goodsinfo['price'];
                        $good_log['cost'] = 1;
                        $good_log['orderprice'] = isset($orderprice)?$orderprice:($data['stock'] * $good_log['price']);
                        $good_log['number'] = $data['stock'];
                        $good_log['type'] = 1;
                        $good_log['day'] = date('Y-m-d',time());
                        $good_log['date'] = date('Y-m-d H:i:s',time());
                        $good_log['status'] = 1;
                        M('good_log')->add($good_log);
                        $return['status'] = 1;
                        $return['msg'] = '商品'.$goodsinfo['name'].'入库成功。当前剩余库存:'.$data['stock'];
                    }else if($operatetype == 4){

                        $data_orgin = I('post.data');
                        $data_orgin = htmlspecialchars_decode($data_orgin);
                        $data = json_decode($data_orgin,true);
                        if($data){
                            for($i=0;$i<count($data);$i++){
                                $temp_gid = $data["$i"]['gid'];
                                $datas['price'] = $data["$i"]['price'];
                                $check_price = M('goods')->where("gid = '$temp_gid' and price = ".$datas['price'])->find();
                                if(!$check_price){
                                    $ret = M('goods')->where("gid = '$temp_gid'")->save($datas);
                                }
                            }
                            
                            if($ret){
                                $return['status'] = 1;
                                $return['msg'] = '商品价格更新成功';
                            }else{
                                $return['status'] = -9;
                                $return['msg'] = '商品价格更新失败';
                                $return['data'] = $data;
                                $return['data_origin'] = $data_orgin;
                            }
                        }else{
                            $return['status'] = 0;
                            $return['msg'] = '数据解析失败';
                        }
                    }else if($operatetype == 5){
                        if(I('post.category')){
                            $maps['category'] = I('post.category');
                        }
                        $maps['status'] = 1;
                        $maps['shopid'] = $shopid;
                        $bargood = I('post.bargoods');
                        if($bargood){
                            $maps['isbar'] = 0;
                        }
                        $goodsinfo = M('goods')->where($maps)->select();
                        $data = array();
                        for($i=0;$i<count($goodsinfo);$i++){
                            $data["$i"]["gid"] = $goodsinfo["$i"]['gid'];
                            $data["$i"]["name"] = $goodsinfo["$i"]['name'];
                            $data["$i"]["price"] = $goodsinfo["$i"]['price'];
                            $picid = $goodsinfo["$i"]["photo"];
                            if(is_numeric($picid)){
                                $data["$i"]["picurl"] = M('pic_log')->where("id = '$picid'")->getField('picurl');
                            }else{
                                $data["$i"]["picurl"] = $picid;
                            }
                        }

                        if($data){
                            $return['status'] = 1;
                            $return['msg'] = '商品信息获取成功';
                            $return['data'] = $data;
                            $return['bar_category'] = M('goods_category')->where("shopid = '1000' and status = 1")->select();
                        }else{
                            $return['status'] = -9;
                            $return['msg'] = '暂无更多商品信息';
                        }
                    }else if($operatetype == 6){
                            $data_orgin = I('post.data');
                            $data_orgin = htmlspecialchars_decode($data_orgin);
                            $data = json_decode($data_orgin,true);
                            if($data){
                                $flag = 0;
                                for($i=0;$i<count($data);$i++){
                                    $temp_gid = $data["$i"]['gid'];
                                    $datas['orderid'] = $data["$i"]['orderid'];
                                    
                                    $ret = M('goods')->where("gid = '$temp_gid'")->save($datas);  
                                    if($ret){
                                        $flag = 1;
                                    } 
                                }
                                if($flag){
                                    $return['status'] = 1;
                                    $return['msg'] = '商品排序更新成功';
                                }else{
                                    $return['status'] = -9;
                                    $return['msg'] = '商品排序更新无效';
                                    $return['data'] = $data;
                                    $return['data_origin'] = $data_orgin;
                                }
                            }else{
                                $return['status'] = 0;
                                $return['msg'] = '数据解析失败';
                            }
                    }else{
                         $return['status'] = -7;
                        $return['msg'] = '无效请求';
                    }
                /*
                }else{
                    $return['status'] = -8;
                    $return['msg'] = '商品不存在';
                }*/
            }else{
                $return['status'] = -10;
                $return['msg'] = '无操作权限';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
     * 用户商品种类增删改
     * request mehtod POST
     * @param token  令牌
     * @param shop   用户店铺id
     * @param category  用户商品种类id（删除修改时需传入）
     * @param type   操作种类1：新增，2：修改，3：删除
     * @param array  表单信息
     */
    public function goods_category(){
        $token = I('token');
        $check_token = D('User')->check_token($token);
        $return = array(
            'status'=>0,
            'msg'=>'令牌失效',
            'data'=>''
        );
        if($check_token['status']){
            $shopid = I('post.shopid');
            $shopowner = M('business')->where("id = '$shopid'")->getField('uid');
            $user = $check_token['username'];
            $userid = M('member')->where("username = '$user'")->getField('memberid');
            $user_power = M('member')->where("username = '$user'")->getField('power');
            $user_shop = M('member')->where("username = '$user'")->getField('operatetype');
            if($userid == $shopowner ||($user_shop == $shopid && ($user_power > 1) )){
                $operatetype = I('type');
                switch ($operatetype) {
                    case 1:
                        //$data['id'] = M('goods_category')->count() + 100000;
                        $data['name'] = I('name');
                        $data['notes'] = I('notes');
                        $data['shopid'] = I('shopid');
                        $data['status'] = 1;
                        $ret = M('goods_category')->add($data);
                        break;
                    case 2:
                        $categoryid = I('id');
                        if(I('name')){
                            $data['name'] = I('name');
                        }
                        if(I('notes')){
                            $data['notes'] = I('notes');
                        }
                        $ret = M('goods_category')->where("id = '$categoryid'")->save($data);
                        break;
                    case 3:
                        $categoryid = I('id');
                        $data['status'] = 0;
                        $ret = M('goods_category')->where("id = '$categoryid'")->save($data);
                        break;
                    default:
                        $return['status'] = -11;
                        $return['msg'] = '无效操作';
                        break;
                }
                if($ret){
                    $return['status'] = 1;
                    $return['msg'] = '修改成功';
                }else{
                    if($return['status'] != -11){
                        $return['status'] = -2;
                        $return['msg'] = '数据写入失败，请稍后重试';
                    }
                }
            }else{
                $return['status'] = -10;
                $return['msg'] = '无操作权限';
            }
        }else{
            $return['status'] = $check_token['status'];
            $return['msg'] = $check_token['msg'];
        }
        //响应返回json数据
        $this->response($return,$this->defaultType);
    }

    /**
    *   商家获取订单列表接口
    */
    public function getOrderList()
    {
        $shopId = I('post.shopId', null);
        $from = I('post.from');
        $size = I('post.size');
        if($from == null) {
            $from = 0;
        }

        if($size == null) {
            $size = 10;
        }

        $orderIds = array();

        $orderIds = M('user_order')
        ->where("shopid= '$shopId' ")
        ->distinct(true)->field('no')
        ->order("createtime desc")
        ->limit("$from, $size")->select();

        foreach ($orderIds as $key => $value) {
            $orderNo = $value['no'];
            $orderListItem['orderNo'] = $orderNo;
            $orderListItem['createTime'] = M('user_order')->where("no = '$orderNo'")->getField('createtime');
            $orderListItem['status'] = M('user_order')->where("no = '$orderNo'")->getField('status');
            $orderListItem['totalPrice'] = M('user_order')->where("no = '$orderNo'")->getField('total_price');
            $shopId = M('user_order')->where("no = '$orderNo'")->getField('shopid');
            $orderListItem['shopId'] = $shopId;
            $orderListItem['logo'] = M('business')->where(" id = '$shopId' ")->getField('logo');
            $orderListItem['shopName'] = M('business')->where(" id = '$shopId' ")->getField('name');
            $orderList[$key] = $orderListItem;
        }

        $this->response(getResponseMessage(1, '获取成功', $orderList), 'json');
    }

    public function getOrderDetail()
    {
             $orderNo = I('post.orderNo');
            if($orderNo == null) {
                $this->response(getResponseMessage(-2, '参数错误', ''), 'json');
            }

            $orderDetailList = M('user_order')->where(" no = '$orderNo' ")->select();

            foreach ($orderDetailList as $key => $orderDetail) {
                $goodId = $orderDetail['goodid'];
                $orderDetailList["$key"]['goodName'] = M('goods')->where(" gid = '$goodId' ")->getField('name');
            }

            $this->response(getResponseMessage(1, '查询成功', $orderDetailList), 'json');
    }

}