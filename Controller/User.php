<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User extends Controller_Template {

    public function before() {

        $this->accessRules = array(
            'any' => array('addLike','remove'), //url, которые доступны всем.
            'guest' => array(
                'activateUser',
                'forgetPass',
                'login'
            ),                   //url, которые доступны для гостей
            'client' => array('logout'), //url, которые доступны для клиента
            'agent' => array('logout'),  //url, которые доступны для агента
            'admin' => array('logout','remove')   //url, которые доступны для админа
        );

        parent::before();
    }

    public function action_activateUser() {
        if($this->request->post() || $this->request->query('key')){
            $key = $this->request->post('key');
            if (!isset($key)) {
                $key = $this->request->query('key');
            }
            $user = ORM::factory('User')->getUserByKeyAndStatus($key, 0);
            if ($user->loaded()) {
                $user->status = 1;
                $generator = new Helper_Generator();
                $password = $generator->generateString(12);
                $user->password = $password;
                $user->registr_date = strtotime(date('d-m-Y'));
                $user->save();
                $email = new Helper_Email();
                $message = "Вот ваш пароль - ".$password;
                $email->send('bloommy@info.com',$user->email,'Регистрация на Bloommy',$message);
                $this->redirect(URL::base());
            }
        }
        $user = ORM::factory('User',$this->request->param('id'));
        if ($user->loaded()) {
            $this->template->title = 'Подтверждение регистрации пользователя';
            $this->template->content = View::factory('layout/confirm_registration')
                ->set('key',$user->key);
            $this->template->top_footer = '';
        } else {
            $this->redirect('404');
        }
    }

    public function action_login(){
        if($this->request->post()){
            $post = $this->request->post();
            $success = Auth::instance()->login(trim($post['email']), trim($post['pass']),true);
            if($success){
                $this->redirect(URL::base());
            }else{
                $this->redirect(URL::base());
            }
        }
    }

    public function action_logout() {
        Auth::instance()->logout();
        $this->redirect(Kohana::$base_url);
    }
    
    public function action_forgetPass() {
        if($this->request->post()) {
            $post = $this->request->post();
            $user = ORM::factory('User')->getUserByEmail($post["email"]);
            if ($user->loaded()) {
                $generator = new Helper_Generator();
                $password = $generator->generateString(12);
                $user->password = $password;
                $user->save();
                $email = new Helper_Email();
                $message = "Ваш новый пароль - ".$password;
                $email->send('bloommy@info.com',$user->email,'Восстановление пароля на Bloommy',$message);
                $this->redirect(URL::base());
            }
        }
    }

    public function action_remove() {
        $email = $this->request->query('email');
        $user = ORM::factory('User')->getUserByEmail($email);
        if ($user->loaded()) {
            $user_group = $user->group->name;
            switch($user_group) {
                case 'agent':
                    $agent = $user->agent;
                    $agent->delete();
                    break;
                case 'client':
                    $client = $user->client;
                    $client->delete();
                    break;
            }
            $user->delete();
            echo 'success';
            die;
        }
        echo 'error';
        die;
    }

    public function action_addLike() {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
            $id = $post['id'];
            try {
                if(ORM::factory('Likes')->getLikes($this->user->id, $id)->loaded())
                {
                    echo 'isset';
                    die;
                }
                $like_array = array();
                $like_array['author_id'] = $this->user->id;
                $like_array['user_id'] = $id;
                $like_array['date'] = time();
                ORM::factory('Likes')->setLike($like_array);
                    echo ORM::factory('Likes')-> getCountLikesByUserId($id);
            } catch (ORM_Validation_Exception $e) {
                echo 0;
            }
            die;
        }
    }
    
} // End User
