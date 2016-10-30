<?php
/**
 * Created by PhpStorm.
 * User: malil
 * Date: 2016/10/10
 * Time: 10:42
 */

namespace app\controllers;

use app\models\LoginForm;
use app\models\Session;
use app\models\SignupForm;
use app\models\Tool;
use app\models\User;
use Yii;
use yii\web\Controller;


class UserController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * 用户个人中心
     *
     * @return string
     */
    public function actionDefault(){
        //是否已经登陆

        $user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';
        if(!$user_id){
            Tool::Redirect("/user/login");
        }

        return $this->render('default');
    }

    public function actionUserCourse(){
        $user_id = $_COOKIE['user_id'];
        if(!$user_id){
            Tool::Redirect("/user/login");
        }

        return $this->render('user_course');
    }

    /**
     * Login action.用户登录
     *
     * @return string
     */
    public function actionLogin()
    {
        $this->layout = false;
        $phone = Yii::$app->request->post('phone');
        if ($phone) {
            $member = new User();
            $user = array('phone' => $_POST['phone'], 'password' => $_POST['password']);
            if (!$member->login($user)) {
                Tool::Redirect("/user/login", '登陆失败！', 'error');
            } else {
                //echo Session::Get('phone');die;
               Tool::Redirect("/user/default", '登陆成功！');
            }
        }
        return $this->render('login');
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionSignup()
    {
        $this->layout = false;
        if ($_POST['phone']) {
            $member = new User();
            $user = array('phone' => $_POST['phone'], 'password' => $_POST['password']);

            if (!$member->signup($user)) {
                Tool::Redirect("/user/signup", '注册失败！', 'error');
            } else {
                //echo Session::Get('phone');die;
               Tool::Redirect("/user/default", '注册成功！');
            }
        }
        return $this->render('signup');
    }


    /*
    * 退出登录
    * @param int $_SESSION['user_id'] 登录入口
    * @access public
    */
    public function actionlogout()
    {
        //if(isset($_SESSION['user_id'])) {
        session_unset();
        unset($_SESSION['user_id']);
        unset($_SESSION['phone']);
        User::NoRemember(user_id);
        User::NoRemember(phone);
        //unset($_SESSION['oauth']);
        Tool::Redirect('/index.htm');
    }

    /**
     * 重设密码(修改密码)
     * @param int $user_id 登录入口
     * @access public
     */
    public function actionResetPassword()
    {
        $user_id = $_GET['user_id'];
        $user = User::GetUserById($user_id);
        if ($_POST) {
            $user_id = $_POST['user_id'];
            if ($_POST['password'] == $_POST['password2']) {
                $password = User::GenPassword($_POST['password']);
                $passwordold = User::getUsercheck(array('user_id' => $_POST['user_id'], 'password' => User::GenPassword($_POST['passwordold'])));
                if (!$passwordold) {
                    Tool::Redirect("user-reset-password?user_id={$user_id}", '旧密码有误！', 'error');
                }
                $sql = "update user set password='{$password}' WHERE user_id = '{$user_id}'";

                $res = Yii::$app->db->createCommand($sql)->query();
                if ($res) {
                    Tool::Redirect('user-reset-password?user_id={$user_id}', '密码修改成功', 'success');
                } else {
                    Tool::Redirect("user-reset-password?user_id={$user_id}", '修改密码失败！', 'error');
                }
            }
            Tool::Redirect("user-reset-password?user_id={$user_id}", '两次输入的密码不匹配，请重新设置', 'error');
        }
        return $this->render('resetpassword', [
            'user' => $user,
        ]);
    }

    /**
     * 发送短信
     * @return string
     */

    public function actionSend()
    {
        if ($_GET['phone']) {
            $mobile = $_GET['phone'];
            $verifyCode = rand(1000, 9999);
            //Yii::$app->session->set('code', $verifyCode);
            // Session::Set('code', $verifyCode);
            //Tool::cookieset("code", $verifyCode, "600");

            $content = "【宝宝玩英语】您的验证码为：{$verifyCode}此验证码10分钟内有效，请尽快使用！";
            $result = Tool::Send($mobile, $content);
            if ($result) {
                //发送成功，保存session
                //检查session是否打开
                if (!Yii::$app->session->isActive) {
                    Yii::$app->session->open();
                }
                //验证码和短信发送时间存储session
                Yii::$app->session->set('login_sms_code', $verifyCode);
                Yii::$app->session->set('login_sms_time', time());
                //
                $return = array(
                    'cood' => 200,
                    // 'verifyCode' => Session::Get('code'),
                    'message' => '短信发送成功',
                );
            } else {
                $return = array(
                    'cood' => 0,
                    'message' => '短信发送失败',
                );
            }
        } else {
            $return = array(
                'cood' => 0,
                'message' => '没有获取手机号',
            );
        }
        die(json_encode($return));
    }

    /**
     * 验证码是否有效
     */
    public function actionCheckCode()
    {
        if ($_GET['code']) {
            //检查session是否打开
            if (!Yii::$app->session->isActive) {
                Yii::$app->session->open();
            }
            //取得验证码和短信发送时间session
            $signup_sms_code = intval(Yii::$app->session->get('login_sms_code'));
            $signup_sms_time = Yii::$app->session->get('login_sms_time');
            if (time() - $signup_sms_time < 600) {
                if ($_GET['code'] != $signup_sms_code) {
                    $result = array('cood' => 0, 'message' => '验证码输入有误');
                }
            } else {
                $result = array('cood' => 0, 'message' => '验证码已经失效');
            }
        }echo $signup_sms_code;
        die(json_encode($result));
    }


}