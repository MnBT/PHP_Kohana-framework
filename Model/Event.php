<?php defined('SYSPATH') or die('No direct script access.');

class Model_Event extends ORM
{
    protected $_table_name = 'events';
    
    protected $_has_one = array(
        'invites' => array(
            'model' => 'EventInvites',
            'foreign_key' => 'event_id'
        ),
    );

    protected $_belongs_to = array(
        'city' => array('model' => 'City', 'foreign_key' => 'city_id'),
        'currency' => array('model' => 'Currency', 'foreign_key' => 'budget_currency'),
        'client' => array('model' => 'Client', 'foreign_key' => 'client_id'),
    );
    
    protected $_has_many = array(
        'guests_groups' => array(
            'model' => 'EventGuestsGroups',
            'foreign_key' => 'event_id'
        ),
        'guests' => array(
            'model' => 'EventGuests',
            'foreign_key' => 'event_id'
        ),
        'visitors' => array(
            'model' => 'EventVisitors',
            'foreign_key' => 'event_id'
        ),
        'inbox' => array(
            'model' => 'Inbox',
            'foreign_key' => 'event_id'
        ),
        'seatings' => array(
            'model' => 'EventSeatings',
            'foreign_key' => 'event_id'
        ),
        'services' => array(
            'model' => 'EventServices',
            'foreign_key' => 'event_id'
        ),

);
    
    public function rules()
    {
        return array(
            'title' => array(
                array('not_empty'),
                array('min_length', array(':value', 1)),
                array('max_length', array(':value', 100)),
            ),
            'date' => array(
                array('regex', array(':value', '/([0-9]{10})/')),
            ),
            'number_guest' => array(
                array('regex', array(':value', '/([0-9]{1,5})/'))
            ),
            'planned_budget' => array(
                array('regex', array(':value', '/\-?\d+(\.\d{0,})?/'))
            ),
            'city_id' => array(
                array('regex', array(':value', '/([0-9]{1,10})/'))
            ),
            
        );
    }

    public function getEventsForClient($client_id, $order_by = '', $limit = '')
    {
        $query = $this->where('client_id','=',$client_id);
        if ($order_by == 'DESC' || $order_by == 'desc') {
        $query = $query->order_by('id', 'DESC');
        }
        if ($limit) {
            $query = $query->limit($limit);
        }
        return $query->find_all();
    }

    public function getCurrentEventsForClient($client_id, $order_by = '', $limit = '')
    {
        $query = $this->where('client_id','=',$client_id);
        $query = $this->and_where('date','>', time());
        if ($order_by == 'DESC' || $order_by == 'desc') {
            $query = $query->order_by('id', 'DESC');
        }
        if ($limit) {
            $query = $query->limit($limit);
        }
        return $query->find_all();
    }

    public function getPastEventsForClient($client_id, $order_by = '', $limit = '')
    {
        $query = $this->where('client_id','=',$client_id);
        $query = $this->and_where('date','<', time());
        if ($order_by == 'DESC' || $order_by == 'desc') {
            $query = $query->order_by('id', 'DESC');
        }
        if ($limit) {
            $query = $query->limit($limit);
        }
        return $query->find_all();
    }

    public function getInformationPastClientEvent($client_id, $limit = '')
    {
        $pastEvents = $this->getPastEventsForClient($client_id, 'desc');
        $pastEventsInformation = array();
        foreach ($pastEvents as $event)
        {
           $services = $event->services->find_all();
            foreach ($services as $service)
            {
                $photos = $service->photos->find_all();
                foreach ($photos as $i => $photo)
                {
                    $pastEventsInformation[$i]['photo'] = $photo->link;
                    $pastEventsInformation[$i]['event_id'] = $event->id;
                    $pastEventsInformation[$i]['event_title'] = $event->title;
                }
                if (!empty($limit) && count($pastEventsInformation) == $limit) {
                    break 2;
                }
            }

        }
        return $pastEventsInformation;
    }

    public function countEventsForClient($client_id)
    {
        return $this->where('client_id','=',$client_id)->count_all();
    }
    
    public function updateSubscribeEvents($events_array, $client_id)
    {
        $events = $this->where('client_id','=',$client_id)->find_all();
        foreach($events as $event)
        {
            if(!array_key_exists($event->id, $events_array))
            {
                $event->set('subscribe', '0')->save();
            }
            else
            {
                $event->set('subscribe', '1')->save();
            }
        }
    }

    public function getNextEventForClient($client_id) {
        $new_data = strtotime(date('d-m-Y'));
        $event = $this->where('date','>',$new_data)->and_where('client_id','=',$client_id)->and_where('status','=','1')->order_by('date')->find();
        return $event;
    }

    public function getEventCountByStatus($status)
    {
        $status = (int)$status;
        return ORM::factory('Event')->where('status', '=', $status)->count_all();
    }

    public function saveLogoByEventId($event_id, $filename)
    {
        $event = ORM::factory('Event', $event_id);
        $event->logo = $filename;
        $event->save();
    }

    public function createEvent($param)
    {
        return ORM::factory('Event')->values($param)->save();
    }

    public function getNearestEvents()
    {
        return ORM::factory('Event')->where('date', '>', time())->and_where('status', '=', 1)->order_by('date', 'DESC')->limit(6)->find_all();
    }

    public function getEventById($event_id)
    {
        return ORM::factory('Event')->where('id', '=', $event_id)->find();
    }

    public function setEventViews($event_id, $views)
    {
        $event = ORM::factory('Event')->where('id', '=', $event_id)->find();
        $event->views = $views;
        $event->save();
    }

}