<?php defined('SYSPATH') or die('No direct script access.');

class Model_Inbox extends ORM {

    protected $_table_name = 'inbox';

    protected $_belongs_to = array(
        'author' => array(
            'model' => 'User',
            'foreign_key' => 'author_id'
        ),
        'user' => array(
            'model' => 'User',
            'foreign_key' => 'user_id'
        ),
        'service' => array(
            'model' => 'Services',
            'foreign_key' => 'service_id'
        )
    );

    public function getUnreadMessagesCount($user_id) {
        $unread_count = $this->where('user_id','=',$user_id)->and_where('read','=','0')->count_all();
        return $unread_count;
    }

    public function getAllMessagesCount($user_id) {
        $unread_count = $this->where('user_id','=',$user_id)->count_all();
        return $unread_count;
    }
    
    public function getMessagesForAgent($user_id, $recive = false)
    {
        $services = array();
        $all_new = 0;
        
        $inbox = $this->where('user_id', '=', $user_id);
        if($recive == 1)
        {
            $inbox->and_where('read', '=', '0');
        }
        $result = $inbox->find_all();
        
        foreach($result as $message)
        {
            $service_id = ($message->service_id == null) ? 0 : $message->service_id;
            if(!array_key_exists($service_id , $services))
            {
                $services[$service_id]['name'] = $message->service->name;
                $services[$service_id]['inbox_count'] = 0;
                $services[$service_id]['inbox_count_new'] = 0;
            }
            $services[$service_id]['inbox'][] = $message;
            $services[$service_id]['inbox_count']++;
            if($message->read == 0)
            {
                $services[$service_id]['inbox_count_new']++;
                $all_new++;
            }
        }
        return array($services, $all_new);
    }

    public function getMessagesForEvent($event_id)
    {
        $inbox_build = array();
        $all_new = 0;

        $result = $this->where('event_id', '=', $event_id)->find_all();
        foreach($result as $inbox) {
            $service_id = ($inbox->service_id == null) ? 0 : $inbox->service_id;
            if(!array_key_exists($service_id , $inbox_build))
            {
                $services[$service_id]['name'] = $inbox->service->name;
                $services[$service_id]['inbox_count'] = 0;
                $services[$service_id]['inbox_count_new'] = 0;
            }
            $services[$service_id]['inbox'][] = $inbox;
            $services[$service_id]['inbox_count']++;
            if($inbox->read == 0)
            {
                $services[$service_id]['inbox_count_new']++;
                $all_new++;
            }
        }
        return array($services, $all_new);
    }

    public function createInbox($param)
    {
        ORM::factory('Inbox')->values($param)->save();
    }

    public function countEventServiceAgentPropositions($event_id, $service_id)
    {
        return $this->where('event_id', '=', $event_id)->and_where('service_id', '=', $service_id)->group_by('user_id')->count_all();
    }
}