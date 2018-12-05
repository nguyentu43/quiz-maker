<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use User\Form\UserForm;
use User\Form\LoginForm;
use User\Auth\AuthManager;
use User\Model\User;
use User\Model\UserTable;
use Zend\Mvc\MvcEvent;
use Zend\Validator\Callback;
use Zend\Mail;
use Zend\Mime;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Crypt\Password\BCrypt;

class UserController extends AbstractActionController
{
	private $authManager;
    private $table;
    private $globalConfig;

	public function __construct(AuthManager $authManager, UserTable $table, $config)
	{
		$this->authManager = $authManager;
        $this->table = $table;
        $this->globalConfig = $config;
	}

    public function loginAction()
    {
        $request = $this->getRequest();
        $formLogin = new LoginForm();

        if($request->isPost())
        {
            $post = $request->getPost();
            $result = $this->authManager->login($post['username'], $post['password'], $post['remember']);

            if($result->isValid())
            {
                //session_start();
                $user = $this->authManager->getUser();
                if($this->authManager->getRole() == 'teacher')
                {
                    $_SESSION['RF']['quiz_modifier'] = true;
                    $_SESSION['RF']['subfolder'] = $user->user_id.'/';
                    $subfolder = $this->globalConfig['app']['root_path'] . 'public/uploads/' . $user->user_id;
                    if(!is_dir($subfolder))
                        mkdir($subfolder);
                }

                if($this->authManager->getRole() == 'admin')
                {
                    $_SESSION['RF']['quiz_modifier'] = true;
                }

                $this->redirect()->toRoute('home');
            }
            else
            {
                $formLogin->get('username')->setAttribute('value', $post['username']);
                $error_message = $result->getMessages()[0];
                return new ViewModel([
                    'error_message' => $error_message,
                    'formLogin' => $formLogin
                ]);
            }
        }

        return new ViewModel([
            'formLogin' => $formLogin
        ]);
    }

    private function createUser($request, $view)
    {

        $user = new User();
        $formUser = new UserForm();

        $inputFilter = $user->getInputFilter();

        $callback = new Callback(function($username){
            return !$this->table->getByUserName($username);
        });

        $callback_checkemail = new Callback(function($email){
            return !$this->table->getByEmail($email);
        });

        $callback->setOptions([
            'messages' => [
                Callback::INVALID_VALUE => 'Tên tài khoản đã được đăng ký'
            ]
        ]);

        $callback_checkemail->setOptions([
            'messages' => [
                Callback::INVALID_VALUE => 'Địa chỉ email đã được đăng ký'
            ]
        ]);

        $inputFilter->get('username')->getValidatorChain()->attach($callback);
        $inputFilter->get('email')->getValidatorChain()->attach($callback_checkemail);

        if($request->isPost())
        {
            $formUser->setInputFilter($inputFilter);
            $formUser->setData($request->getPost());

            if($formUser->isValid())
            {
                $user->exchangeArray($formUser->getData());
                if($this->authManager->isGranted('user.manage'))
                {
                    $user->is_active = 1;
                    $this->table->saveUser($user);
                    $this->redirect()->toRoute('user_manager');
                }
                else
                {
                    $user->is_active = 1;
                    //$user->verification_code = md5(uniqid('quiz'));
                    $this->table->saveUser($user);
                    //$this->sendMail($user);
                    $user = new User();
                    $formUser->setData($user->getArray());
                    $view->setVariable('register', true);
                }
            }
        }

        $view->setVariable('formUser', $formUser);

        return $view;
    }

    public function createAction()
    {
        $request = $this->getRequest();
        $view = new ViewModel();
        $view->setVariable('admin', true);
        return $this->createUser($request, $view);
    }

    public function registerAction()
    {
        $request = $this->getRequest();
        $view = new ViewModel();
        $view->setTemplate('user/user/create');

        return $this->createUser($request, $view);
    }

    public function logoutAction()
    {
        $this->authManager->logout();

        unset($_SESSION['RF']);

        $this->redirect()->toRoute('home');
    }

    public function activeuserAction()
    {
        $uniq = $this->params()->fromRoute('id');
        $user = $this->table->getByCode($uniq);

        if($user)
        {
            if($user->is_active == 0)
            {
                $user->is_active = 1;
                $obj = new User();
                $obj->exchangeArray($user);
                $this->table->saveUser($obj);
                
                return ['msg' => 'Tài khoản của bạn đã được kích hoạt'];
            }
            else
            {
                return ['msg' => 'Tài khoản này đã được kích hoạt'];
            }
            
        }
        else
            return ['msg' => 'Mã xác thực tài khoản không hợp lệ'];
    }

    public function forgotAction()
    { 
        $id = $this->params()->fromRoute('id');

        $request = $this->getRequest();

        if($request->isPost())
        {
            if(isset($request->getPost()['sendMail']) && !empty($request->getPost()['email']))
            {
                $user = $this->table->getByEmail($request->getPost()['email']);

                if($user)
                {
                    $code = uniqid();
                    $this->table->forgotPassword($user['user_id'], $code);
                    $this->sendMailForgot($user, $code);
                }

                return new ViewModel(['msg' => 'Đã gửi yêu cầu khôi phục đến địa chỉ email, có hiệu lực trong 24 giờ']);
            }

            if(isset($request->getPost()['resetPassword']) && !empty($request->getPost()['password']))
            {
                if(strlen($request->getPost()['password']) < 8)
                    return new ViewModel(['forgot' => true, 'forgot_error' => 'Mật khẩu trên 8 kí tự']);
                $result = $this->table->checkForgotCode($id);
                $this->table->updatePassword($result['user_id'], $request->getPost()['password']);
                return new ViewModel(['msg' => 'Đã khôi phục mật khẩu thành công']);

            }
        }

        if(isset($id))
        {
            $result = $this->table->checkForgotCode($id);

            if($result)
            {
                $date = date_create($result['created_date']);
                date_add($date, date_interval_create_from_date_string('24 hours'));

                if($date >= date_create())
                    return new ViewModel(['forgot' => true]);
                else
                {
                    return new ViewModel(['error' => 'Mã khôi phục đã hết hạn']);
                }
            }
            else
            {
                return new ViewModel(['error' => 'Mã khôi phục không tồn tại']);
            }
        }
    }

    public function editinfoAction()
    {

        if(!$this->authManager->isGranted('user.edit'))
        {
            return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
        }

        $request = $this->getRequest();
        $user_id = $this->authManager->getUser()->user_id;
        $user = $this->table->getById($user_id);
        $form = new UserForm();
        $form->add([
            'name' => 'old_password',
            'type' => 'password',
            'options' => [
                'label' => 'Mật khẩu cũ (*)',
            ]
        ]);

        $form->add([
            'name' => 'user_id',
            'type' => 'hidden',
            'value' => $user->user_id
        ]);

        $form->add([
            'name' => 'role_id',
            'type' => 'hidden',
            'value' => $user->role_id
        ]);

        if($request->isPost())
        {
            $inputFilter = (new User())->getInputFilter();
            $inputFilter->remove('password');
            $inputFilter->remove('username');
            $inputFilter->remove('rpassword');
            $data = $request->getPost();
            $crypt = new BCrypt();

            if(!empty($data['old_password']) && !$crypt->verify($data['old_password'], $user->password))
            {
                $msg = "Mật khẩu cũ không chính xác";
                $error = true;
            }

            if(!empty($data['old_password']) && empty($data['password']))
            {
                $msg = "Chưa nhập mật khẩu mới";
                $error = true;
            }

            $form->setInputFilter($inputFilter);

            $form->setData($data);

            if($form->isValid() && !isset($error))
            {
                $user->fullname = $data['fullname'];
                $user->email = $data['email'];

                if(!empty($data['old_password']))
                {
                    $user->password = $crypt->create($data['password']);
                }

                $new = new User();

                $new->exchangeArray($user);

                $this->table->saveUser($new);
                $msg = "Đã thay đổi thành công";
                $error = false;
            }

            if(isset($error))
                return new ViewModel(['form' => $form, 'msg' => $msg, 'error' => $error]);
        }
        else
        {
            $user->password = null;
            $form->setData($user);
        }

        return new ViewModel(['form' => $form]);
    }

    private function configStmpMail()
    {
        $transport = new Smtp();
        $options = new SmtpOptions([
            'name' => '10Quiz',
            'host' => $this->globalConfig['mail']['server'],
            'port' => $this->globalConfig['mail']['port'],
            'connection_class' => 'login',
            'connection_config' => [
                'ssl' => 'tls',
                'username' => $this->globalConfig['mail']['username'],
                'password' => $this->globalConfig['mail']['password']
            ]
        ]);

        /*$phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );*/

        $transport->setOptions($options);
        return $transport;
    }

    private function sendMail($user)
    {
        $mail = new Mail\Message();
        $msg = "<h4>Xin chào người dùng $user->fullname!</h4>";
        $msg .= "Tài khoản của bạn cần được kích hoạt trên hệ thống 10Quiz. ";
        $msg .= "Nhấp vào đường dẫn sau để kích hoạt tài khoản<br/>";

        $url = $_SERVER['HTTP_HOST'].$this->url()->fromRoute('active', ['id' => $user->verification_code]);

        $msg .= "<a href='$url' title='Kích hoạt tài khoản'><strong>Kích hoạt tài khoản</strong></a><br/>";
        $msg .= "<strong>($url)</strong>";

        $html = new Mime\Part($msg);
        $html->type = "text/html";

        $body = new Mime\Message();
        $body->addPart($html);

        $mail->setBody($body);
        $mail->addTo($user->email);
        $mail->setFrom($this->globalConfig['mail']['username']);
        $mail->setSubject('10Quiz - Kích hoạt tài khoản');

        
        $transport = $this->configStmpMail();
        $transport->send($mail);
    }

    private function sendMailForgot($user, $code)
    {
        $mail = new Mail\Message();
        $msg = "<h4>Xin chào người dùng $user->fullname! (Tài khoản: $user->username)</h4>";
        $msg .= "Nhấp vào đường dẫn sau khôi phục lại mật khẩu. Khôi phục tài khoản có hiệu lực trong 24 giờ.<br/>";

        $url = $_SERVER['HTTP_HOST'].$this->url()->fromRoute('forgot', ['id' => $code]);

        $msg .= "<a href='$url' title='Lấy lại mật khẩu'><strong>Lấy lại mật khẩu</strong></a><br/>";
        $msg .= "<strong>($url)</strong>";

        $html = new Mime\Part($msg);
        $html->type = "text/html";

        $body = new Mime\Message();
        $body->addPart($html);

        $mail->setBody($body);
        $mail->setFrom($this->globalConfig['mail']['username']);
        $mail->addTo($user->email);
        $mail->setSubject('10Quiz - Khôi phục mật khẩu');

        $transport = $this->configStmpMail();
        $transport->send($mail);
    }

    public function indexAction()
    {
        $result = $this->table->getAll();
        $users = [];

        foreach ($result as $u) {
            if($u['role_id'] == 1)
            {
                $u['verify_teacher'] = $this->table->checkActiveTeacher($u['user_id'])['state'];
            }
            $users[] = $u;
        }

        return new ViewModel(['users' => $users]);
    }

    public function edituserAction()
    {

        if(!$this->authManager->isGranted('user.manage'))
        {
            return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
        }

        $id = $this->params()->fromRoute('id');
        $form = new UserForm();
        $view = new ViewModel();
        $view->setTemplate('user/user/edituser');

        $user = $this->table->getById($id);
        $request = $this->getRequest();

        if($request->isPost())
        {
            $form_data = $request->getPost();

            if(isset($form_data['btnDelete']))
            {
                $this->table->deleteUser($form_data['user_id']);
                $this->redirect()->toRoute('user_manager');
            }

            if(isset($form_data['btnActive']))
            {
                $state = $form_data['state'];

                $this->table->activeTeacher($id, $state);

                $form->setData($user);

                $view->setVariable('form', $form);
                $this->redirect()->toRoute('user_manager');
            }
            else
            {
                $user['fullname'] = $form_data['fullname'];
                $user['email'] = $form_data['email'];

                $form->setData($user);

                if($form->isValid())
                {
                    $model = new User();
                    $model->exchangeArray($user);
                    $this->table->saveUser($model);
                    $form->setData($user);
                }
                
                $view->setVariable('form', $form);
                $this->redirect()->toRoute('user_manager');
            }
        }
        else
        {
            $form->setData($user);
            $view->setVariable('form', $form);
        }

        if($user['role_id'] == 1)
        {
            $data = $this->table->checkActiveTeacher($id);
            $view->setVariable('data_active', $data);
        }

        if($user['role_id'] == 3)
        {
            $view->setVariable('isAdmin', true);
        }

        return $view;
    }

}
