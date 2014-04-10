<?php defined('SYSPATH') or die('No direct script access.');

class Model_User extends Model_Auth_User
{
    protected $_table_name = 'users';

    protected $_belongs_to = array(
        'group' => array('model' => 'Group', 'foreign_key' => 'group_id'),
    );
    protected $_has_one = array(
        'agent' => array('model' => 'Agent', 'foreign_key' => 'user_id'),
        'client' => array('model' => 'Client', 'foreign_key' => 'user_id'),
    );
    protected $_has_many = array(
        'user_tokens' => array('model' => 'User_Token'),
        'roles'       => array('model' => 'Role', 'through' => 'roles_users'),
        'cart' => array('model' => 'Cart'),
        'inbox' => array('model' => 'Inbox', 'foreign_key' => 'user_id'),
        'likes' => array('model' => 'Likes', 'foreign_key' => 'user_id'),
    );



    public function rules()
    {
        return array(
            'email' => array(
                array('not_empty'),
                array('email'),
                array(array($this, 'unique'), array('email', ':value')),
            ),
            'contact_email' => array(
                array('email'),
            ),
        );
    }
    
    public function get_likes()
    {
        return $this->likes->limit(3)->find_all();
    }

    public function getUserByKeyAndStatus($key, $status)
    {
        return ORM::factory('User')->where('key','=',$key)->and_where('status','=',$status)->find();
    }

    public function getUserByEmail($email)
    {
        return ORM::factory('User')->where('email', '=', $email)->find();
    }

    public function setUser($param)
    {
        return ORM::factory('User')->values($param)->save();
    }

    public function editUserById($user_id, $param)
    {
        return ORM::factory('User')->where('id', '=', $user_id)->values($param)->save();
    }

}