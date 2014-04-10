<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Event extends Controller_Template {

    const EVENT_LOGO_PATH = 'media/event_logos';
    const EVENT_INVITE_LOGO_PATH = 'media/event_invites';
    const TMP_EVENT_INVITE_LOGO_PATH = 'tmp/event_invites';
    
    public function before() {
        $this->accessRules = array(
            'any' => array(), //action, которые доступны всем.
            'guest' => array(),         //action, которые доступны для гостей
            'client' => array(
                'index',
                'profile',
                'create',
                'budget',
                'guests',
                'details',
                'seating',
                'editEvent',
                'uploadLogo',
                'getService',
                'addService',
                'messages',
                'deleteService',
                'getEventServices',
                'invites',
                'addOneGuest',
                'sendMail',
                'getAllGuestGroupByEvent'
            ),               //action, которые доступны для клиента
            'agent' => array('uploadLogo', 'getEventServices'),                //action, которые доступны для агента
            'admin' => array()                 //action, которые доступны для админа
        );
        parent::before();
    }

    public function action_index() {
        $arrProc = array();
        $arrSpent = array();
        $arrDaysExpire = array();
        $client = $this->user->client;
        $events = $client->events->where('status','=','1')->find_all();
        $events_sidebars = array();
        foreach ($events as $i =>$event)
        {
            $events_sidebars[] = Helper_Sidebar::event_sidebar($event,'edit',$i==0, true);
        }
        $this->template->title = 'Мои события';
        $this->template->content = View::factory('event/index')
            ->set('events_sidebars',$events_sidebars)
            ->set('arrProc',$arrProc)
            ->set('arrSpent',$arrSpent)
            ->set('arrDaysExpire',$arrDaysExpire)
            ->set('countries', ORM::factory('Country')->find_all())
            ->set('cities', ORM::factory('City')->find_all());
    }
    
    public function action_messages()
    {
        $id = $this->request->param('id');
        
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        $services = ORM::factory('Services')->find_all();
        foreach($services as $service)
        {
            $data_services[$service->id] = $service;
        }
        
        
        $inbox = ORM::factory('Inbox')->where('event_id', '=', $id);
        
        //$inbox->and_where('event_id', '=', $id);
        
        $cl = clone($inbox);
        $all_propos = $cl->count_all();
        
        $items_per_page = Kohana::$config->load('site_config.items_per_page.event_messages');
        $pager_data = Helper_Paginator::paginate($inbox, $items_per_page, $this->request->url());
        
        $result = $pager_data['object']->find_all();
        
        $this->template->content = View::factory('event/messages')
            ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
            ->set('id',$id)
            ->set('event',$event)
            ->set('services',$data_services)
            ->set('inbox',$result)
            ->set('all_propos',$all_propos)
            ->set('event_sidebar', Helper_Sidebar::event_sidebar($event))
            ->set('event_services', $event->services->find_all())
            ->set('paginator', $pager_data['view']);
    }

    public function action_profile() {
        $id = $this->request->param('id');
        
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        if($this->request->post()) {
            $post = $this->request->post();
            if($post['action'] == 'edit')
            {
                if (isset($_FILES['logo'])) {
                    $event_logo = $_FILES['logo'];
                    $extension = strtolower(pathinfo($event_logo['name'], PATHINFO_EXTENSION));
                    if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension))
                    {
                        $filename = Text::random('alnum',20).'.'.$extension;
                        $directory = DOCROOT.self::EVENT_LOGO_PATH;
                        
                        if(is_file($directory.'/'.$event->logo))
                        {
                            unlink($directory.'/'.$event->logo);
                        }
                        
                        if (!is_dir($directory)) {
                            mkdir(DOCROOT.self::EVENT_LOGO_PATH, 0777);
                        }
                        Upload::save($event_logo,$filename,$directory);
                    } else {
                        $errors[] = 'Лого должно иметь одно из перечисленных расширений: jpeg, png, gif, bmp, swf';
                    }
                }
                
                try {
                     $event->title = trim($post['title']);
                     if(isset($filename)) {
                        $event->logo = $filename;
                     }
                     $event->date = strtotime(str_replace('.', '-', trim($post['date'])));
                     $event->planned_budget = (strpos($post['planned_budget'], ',') !== false) ? str_replace(',', '.', $post['planned_budget']) : $post['planned_budget'];
                     $event->city_id = $post['city'];
                     $event->number_guest = $post['number_guest'];
                     $event->status = 1;
                     $event->save();
                } catch(ORM_Validation_Exception $e) {
                    $errors[] = $e->errors();
                }
            }
            elseif($post['action'] == 'finish')
            {
                $event->set('status', 0)->save();
            }
             $this->redirect(Route::url('event', array('action' => 'profile', 'id' => $id)));
        }

        $event_inbox = ORM::factory('Inbox')->getMessagesForEvent($id);
        $messages = $event_inbox[0];
        $messages_count = $event_inbox[1];
        $this->template->title = 'Профиль события';
        $this->template->content = View::factory('event/profile')
            ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
            ->set('id',$id)
            ->set('event',$event)
            ->set('messages', $messages)
            ->set('messages_count', $messages_count)
            ->set('event_sidebar', Helper_Sidebar::event_sidebar($event));
    }

    public function action_editEvent() {
        if ($this->request->is_ajax()) {
            $id = $this->request->param('id');
        
            /** Проверка что это событие этого клиента **/
            if(!$this->check_security($id, $this->user->client->id))
            {
                $this->redirect(Route::url('error', array('action' => 'notFound')));
            }
        
            $event = ORM::factory('Event',$id);
            if($this->request->post()) {
                $post = $this->request->post();
                if($post['action'] == 'edit')
                {
                    if (isset($_FILES['logo'])) {
                        $event_logo = $_FILES['logo'];
                        $extension = strtolower(pathinfo($event_logo['name'], PATHINFO_EXTENSION));
                        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension))
                        {
                            $filename = Text::random('alnum',20).'.'.$extension;
                            $directory = DOCROOT.self::EVENT_LOGO_PATH;

                            if(is_file($directory.'/'.$event->logo))
                            {
                                unlink($directory.'/'.$event->logo);
                            }

                            if (!is_dir($directory)) {
                                mkdir(DOCROOT.self::EVENT_LOGO_PATH, 0777);
                            }
                            Upload::save($event_logo,$filename,$directory);
                        } else {
                            $errors[] = 'Лого должно иметь одно из перечисленных расширений: jpeg, png, gif, bmp, swf';
                        }
                    }

                    try {
                        $event->title = trim($post['title']);
                        if(isset($filename)) {
                            $event->logo = $filename;
                        }
                        $event->date = $post['date'] != 'не указано' ? strtotime(str_replace('.', '-', trim($post['date']))) : 0;
                        $event->planned_budget = (strpos($post['planned_budget'], ',') !== false) ? str_replace(',', '.', $post['planned_budget']) : $post['planned_budget'];
                        $event->city_id = $post['city'];
                        $event->number_guest = $post['number_guest'];
                        $event->status = 1;
                        $event->save();
                    } catch(ORM_Validation_Exception $e) {
                        $errors[] = $e->errors();
                    }
                }
                elseif($post['action'] == 'finish')
                {
                    $event->set('status', 0)->save();
                }
            }

        }
    }

    public function action_uploadLogo()
    {
        $event_id = (int)$_POST['event_id'];
            
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($event_id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        if ($this->request->is_ajax()) {
            if ($_FILES['logo']) {
                $event_logo = $_FILES['logo'];
                $extension = strtolower(pathinfo($event_logo['name'], PATHINFO_EXTENSION));
                if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension)) {
                    $filename = Text::random('alnum', 20) . '.' . $extension;
                    $directory = DOCROOT . self::EVENT_LOGO_PATH;
                    if (!is_dir($directory)) {
                        mkdir($directory, 0777);
                    }

                    $event_id = (int)$_POST['event_id'];
                    ORM::factory('Event')->saveLogoByEventId($event_id, $filename);
                    
                    Upload::save($event_logo, $filename, $directory);
                } else {
                    echo 'error';
                    die;
                }
            } else {
                echo 'error';
                die;
            }
            echo $filename;
            die;
        }
    }

    public function action_create()
    {
        if ($this->request->post()) {
            $client = $this->user->client;
            $errors = array();
            $eventId = '';
            $post = $this->request->post();
            $filename = 'img_bloommy.png';
            try {
                $event = ORM::factory('Event');
                $event_array = array();
                $event_array['title'] = trim($post['title']);
                $event_array['logo'] = $filename;
                $event_array['date'] = strtotime(str_replace('.', '-', trim($post['date'])));
                $event_array['number_guest'] = $post['number_guest'];
                $event_array['planned_budget'] = (strpos($post['planned_budget'], ',') !== false) ? str_replace(',', '.', $post['planned_budget']) : $post['planned_budget'];
                $event_array['client_id'] = $client->id;
                $event_array['city_id'] = $post['city'];
                $event_array['status'] = 1;
                $event_array['budget_currency'] = $post['currency'];
                $event = $event->createEvent($event_array);
                $eventId = $event->id;
            } catch (ORM_Validation_Exception $e) {
                $errors[] = $e->errors();
            }

            if (empty($errors)) {
                $this->redirect(Route::url('event', array('action' => 'details', 'id' => $eventId)));
            }
        }

        $this->redirect(Kohana::$base_url);
    }
    
    public function action_details()
    {
        $id = $this->request->param('id');
        
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        if ($this->request->post()) {
            $post = $this->request->post();
            
            $es_sid = $_POST['es_sid'];
            
            //Удаление сервиса
            if(isset($post['budget']))
            {
                foreach($post['budget'] as $k => $v)
                {
                    if(!isset($post['budget_exists'][$k]))
                    {
                        $event->services->delete_service($k);
                    }
                }
            }
            
            //Изменение информации изображений
            if(isset($post['photo']) && count($post['photo']) > 0 && count($post['photo']) <= 12)
            {
                $event->services->photos->save_photos($post, $es_sid);
            }
            
            //Изменение информации видео
            if(isset($post['video']))
            {
                $event->services->videos->save_videos($post, $es_sid);
            }
            
            if(isset($_FILES['video_load']) && is_uploaded_file($_FILES['video_load']['tmp_name']))
            {
                $extension = strtolower(pathinfo($_FILES['video_load']['name'], PATHINFO_EXTENSION));
                $filename = Text::random('alnum',10).'_'.time().'.'.$extension;
                $directory = DOCROOT.'media'.DIRECTORY_SEPARATOR.'event_videos';
                
                Upload::save($_FILES['video_load'],$filename,$directory);
                
                try {
                    $event->videos->set('event_id', $id)
                        ->set('link', $filename)
                        ->set('is_remote', 1)
                        ->set('thumbnail', $filename.'.jpg')
                        ->save();
                } catch(ORM_Validation_Exception $e) {
                    $errors = $e->errors('valid');
                }
            }
            
            if(isset($post['description']))
            {
                try {
                    ORM::factory('EventServices')->addDescription($es_sid, $post['description']);
                } catch(ORM_Validation_Exception $e) {
                    $errors = $e->errors('valid');
                }
            }
            
            header('Location: '.Kohana::$base_url.'event/details/'.$id);
            exit();
        }
        
        $services = ORM::factory('Services')->find_all();
        foreach($services as $service)
        {
            $data_services[$service->id] = $service;
        }

        
        $budgets = $event->services->order_by('id', 'ASC')->find_all(); 
        
        $date_add = date('d.m.Y',$event->date); 
        $date_expire = date('d.m.Y',$event->date_expire);
        $event_first_service = $event->services->getFirstEventService($id);
        $this->template->title = 'Детали события';
        $this->template->content = View::factory('event/details')
            ->bind('errors', $errors)
            ->set('event_sidebar', Helper_Sidebar::event_sidebar($event))
            ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
            ->set('event',$event)
            ->set('budgets',$budgets)
            ->set('services',$data_services)
            ->set('date_add',$date_add)
            ->set('date_expire',$date_expire)
            ->set('event_services', $event->services->find_all())
            ->set('event_first_service', $event_first_service)
            ->set('event_first_service_photos', $event_first_service->photos->find_all())
            ->set('event_first_service_videos', $event_first_service->videos->find_all());
    }
    
    public function action_getService()
    {
        //$_POST['id'] = 5;
        $id = $_POST['id'];
        if($id == 0)
        {
            $service = ORM::factory('EventServices')->find();
        }
        else
        {
            $service = ORM::factory('EventServices', $id);
        }
            
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($service->event_id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
            
        
        $photos = array();
        $photos_data = $service->photos->find_all();
        foreach($photos_data as $photo) { $photos[] = $photo->as_array(); }
        
        
        $videos = array();
        $videos_data = $service->videos->find_all();
        foreach($videos_data as $video) { $videos[] = $video->as_array(); }
        
        $arr = array('id'          => $service->id,
                     'name'        => $service->services->name,
                     'date_added'  => date('d.m.Y',$service->date_added),
                     'deadline'    => ($service->deadline != 0) ? date('d.m.Y',$service->deadline) : '',
                     'description' => $service->description,
                     'budget'      => $service->cost,
                     'propos'      => $service->offers->count_all(),
                     'candidates'  => $service->candidates->count_all(),
                     'photos'      => $photos,
                     'videos'      => $videos);
        echo json_encode($arr);
        die;
    }
    
    public function action_deleteService()
    {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
            $id = (int)$post['id'];
            
            $service = ORM::factory('EventServices')->where('id', '=', $id)->find();
            if($service->loaded())
            {
                /** Проверка что это событие этого клиента **/
                if(!$this->check_security($service->event_id, $this->user->client->id))
                {
                    $this->redirect(Route::url('error', array('action' => 'notFound')));
                }
            }
            $service = ORM::factory('EventServices')->getEventServiceById($id);
            $service->delete();
        }
    }


    public function action_addService()
    {
        $id = $_POST['id'];
        $event_id = $_POST['event_id'];
        
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($event_id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $insert = ORM::factory('EventServices')->set('event_id', $event_id)
            ->set('service_id', $id)
            ->set('date_added', time())
            ->save();
        
        $arr = array('id' => $insert->id, 'name' => $insert->services->name);
        echo json_encode($arr);
        die;
    }

    public function action_budget() {
        $id = $this->request->param('id');
        
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        if ($this->request->post()) {
            $post = $this->request->post();
            if(isset($post['budgets']))
            {
                foreach($post['budgets'] as $k => $v)
                {
                    if(!isset($post['budget_exists'][$k]))
                    {
                        try {
                            $del = $event->services->where('id', '=', $k)->find();
                            if($del->loaded()) $del->delete(); //Удаление
                        } catch(ORM_Validation_Exception $e) {
                            $errors = $e->errors('valid');
                        }
                    }
                    elseif($post['budget_exists'][$k] == 0)
                    {
                        if($post['service_id'][$k] != 0)
                        {
                            try {
                                $event->services->set('event_id', $id) //Добавление
                                    ->set('service_id', $post['service_id'][$k])
                                    ->set('cost', $post['cost'][$k])
                                    ->set('how', $post['count'][$k])
                                    ->save();
                            } catch(ORM_Validation_Exception $e) {
                                $errors = $e->errors('valid');
                            }
                        }
                    }
                    elseif($post['budget_exists'][$k] == 1)
                    {
                        if($post['service_id'][$k] != 0)
                        {
                            try {
                                $event->services->where('id', '=', $k)->find() //Изменение
                                    ->set('service_id', $post['service_id'][$k])
                                    ->set('cost', $post['cost'][$k])
                                    ->set('how', $post['count'][$k])
                                    ->save();
                            } catch(ORM_Validation_Exception $e) {
                                $errors = $e->errors('valid');
                            }
                        }
                    }
                }
            }
            $this->redirect(Route::url('event', array('action' => 'budget', 'id' => $id)));
        }

        
        $budgets = $event->services->order_by('id', 'ASC')->find_all();  
          
        $services = ORM::factory('Services')->order_by('id', 'ASC')->find_all();  
        foreach($services as $service)
        {
            $data_services[$service->id] = $service;
        }

        $this->template->title = 'Бюджет события';
        $this->template->content = View::factory('event/budget')
            ->bind('errors', $errors)
            ->set('event_sidebar', Helper_Sidebar::event_sidebar($event))
            ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
            ->set('budgets',$budgets)
            ->set('services',$data_services);

    }
    
    public function action_addOneGuest()
    {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
        
            /** Проверка что это событие этого клиента **/
            if(!$this->check_security($post['event_id'], $this->user->client->id))
            {
                $this->redirect(Route::url('error', array('action' => 'notFound')));
            }
        
            try {
                ORM::factory('EventGuests')->set('event_id', $post['event_id'])
                    ->set('name', $post['name'])
                    ->set('group_id', $post['group_id'])
                    ->set('email', $post['email'])
                    ->set('phone', $post['phone'])
                    ->save();
            } catch(ORM_Validation_Exception $e) {
                $errors = $e->errors('valid');
                echo 'error';
                die;
            }
        }
        else
        {
            echo 'error';
        }
    }
    
    public function action_guests()
    {
        $id = $this->request->param('id');
            
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
            
        $event = ORM::factory('Event',$id);
        
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
            
            if(isset($post['guest']))
            {
                $event->guests->add_guests($post, $event, $id);
                
                $groups = $event->guests_groups->find_all();
                foreach($groups as $k => $v)
                {
                    if(isset($post['guest_groups'][$v->id]))
                    {
                        $v->set('checked', '1')->save();
                    }
                    else
                    {
                        $v->set('checked', '0')->save();
                    }
                }
                
                $guests_count = $event->guests->count_all();
                $guests_willcome = $event->guests->where('is_will_come', '=', '1')->count_all();
                echo json_encode(array('guests_count' => $guests_count, 'guests_willcome' => $guests_willcome));
                die;
            }
            elseif(isset($post['action']) && $post['action'] == 'add_group')
            {
                try {
                    $insert = $event->guests_groups->set('event_id', $id)->set('name', $post['name'])->save();
                } catch(ORM_Validation_Exception $e) {
                    $errors = $e->errors('valid');
                    echo 'error';
                    die;
                }
                echo $insert->id;
                die;
            }
            elseif(isset($post['action']) && $post['action'] == 'delete_group')
            {
                try {
                    $del = $event->guests_groups->where('id', '=', $post['id'])->find();
                    if($del->loaded())
                    {
                        $del->delete();
                    }
                    $guests = $event->guests->where('group_id', '=', $post['id'])->find_all();
                    foreach($guests as $guest)
                    {
                        $guest->set('group_id', '0')->save();
                    }
                } catch(ORM_Validation_Exception $e) {
                    $errors = $e->errors('valid');
                    echo 'error';
                    die;
                }
                echo 1;
                die;
            }
        }
        else
        {
            $guests_groups = $event->guests_groups->order_by('id', 'ASC')->find_all();
            $guests = $event->guests->order_by('id', 'ASC')->find_all();
            $guests_count = $event->guests->count_all();
            $guests_willcome = $event->guests->where('is_will_come', '=', '1')->count_all();

            $this->template->title = 'Гость';
            $this->template->content = View::factory('event/guests')
                ->bind('errors', $errors)
                ->set('event_sidebar', Helper_Sidebar::event_sidebar($event))
                ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
                ->set('event',$event)
                ->set('guests_groups',$guests_groups)
                ->set('guests',$guests)
                ->set('guests_count',$guests_count)
                ->set('guests_willcome',$guests_willcome)
                ->set('id',$id);
        }
    }

    public function action_getAllGuestGroupByEvent()
    {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
            if ($event_id = $post['event_id']) {
                $groups = ORM::factory('EventGuestsGroups')->getAllGuestGroupForEvent($event_id);
                echo json_encode($groups);
                die;
            } else  {
                echo 'error';
                die;
            }

        }
    }
    
    public function action_seating()
    {
        $id = $this->request->param('id');
            
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        if ($this->request->post()) {
            $post = $this->request->post();
            
            if(isset($post['seating_exists']))
            {
                foreach($post['seating_exists'] as $k => $v)
                {
                    if(!isset($post['seating'][$k])) //Удаление
                    {
                        try {
                            $del = $event->seatings->where('id', '=', $k)->find();
                            if($del->loaded()) $del->delete();
                            $del_guests = ORM::factory('EventGuests')->where('seating_id', '=', $k)->find_all();
                            foreach($del_guests as $del_guest)
                            {
                                $del_guest->set('seating_id', '')->set('seating_position', '')->save();
                            }
                            
                            //$event->guests->where('seating_id', '=', $k)->find()->set('seating_id', '')->set('seating_position', '')->save();
                        } catch(ORM_Validation_Exception $e) {
                            $errors = $e->errors('valid');
                            print_r($errors);
                            echo '1';
                            die;
                        }
                    }
                    elseif($post['seating_exists'][$k] == 0) //Добавление
                    {
                        try {
                            $vis = (isset($_POST['vis'][$k])) ? 1 : 0;
                            $seating = $event->seatings->set('event_id', $event->id)
                                ->set('table_type', $post['table_id'][$k])
                                ->set('show_guests', $vis)
                                ->set('guests_ids', $post['guest'][$k])
                                ->set('stul', $post['stul'][$k])
                                ->save();
                            
                            if(isset($post['guest'][$k]) && isset($post['stul'][$k]))
                            {
                                $stul_data = explode(',', $post['stul'][$k]);
                                $guest_data = explode(',', $post['guest'][$k]);
                                
                                foreach($guest_data as $key => $guest)
                                {
                                    $event->guests->where('id', '=', $guest)->find()
                                        ->set('seating_id', $seating->id)
                                        ->set('seating_position', $stul_data[$key])
                                        ->save();
                                }
                            }
                        } catch(ORM_Validation_Exception $e) {
                            $errors = $e->errors('valid');
                            print_r($errors);
                            echo '2';
                            die;
                        }
                    }
                    elseif($post['seating_exists'][$k] == 1) //Изменение
                    {
                        try {
                            $vis = (isset($_POST['vis'][$k])) ? 1 : 0;
                            $seating = $event->seatings->where('id', '=', $k)->and_where('event_id', '=', $event->id)->find()
                                ->set('table_type', $post['table_id'][$k])
                                ->set('show_guests', $vis)
                                ->set('guests_ids', $post['guest'][$k])
                                ->set('stul', $post['stul'][$k])
                                ->save();
                            
                            if(isset($post['guest'][$k]) && isset($post['stul'][$k]) && $post['stul'][$k] != '' && $post['guest'][$k] != '')
                            {
                                $stul_data = explode(',', $post['stul'][$k]);
                                $guest_data = explode(',', $post['guest'][$k]);
                                
                                $isset_guests = $event->guests->where('seating_id', '=', $k)->find_all();
                                foreach($isset_guests as $key => $isset_guest)
                                {
                                    if(!in_array($isset_guest->id, $guest_data))
                                    {
                                        $isset_guest->set('seating_id', $seating->id)
                                            ->set('seating_position', $stul_data[$key])
                                            ->save();
                                    }
                                    else
                                    {
                                        $isset_guest->set('seating_id', '')
                                            ->set('seating_position', '')
                                            ->save();
                                    }
                                }
                            }
                        } catch(ORM_Validation_Exception $e) {
                            $errors = $e->errors('valid');
                            print_r($errors);
                            echo '3';
                            die;
                        }
                    }
                }
            }
        }
        
        $table_types = ORM::factory('EventTables')->find_all();
        
        foreach($table_types as $tt)
        {
            $data_tt[$tt->id] = $tt->as_array();
        }
        
        $data_guests = array();
        $guests = $event->guests->find_all();
        foreach($guests as $guest)
        {
            $data_guests[$guest->id] = array('id' => $guest->id,
                                             'name' => $guest->name,
                                             'seating_id' => $guest->seating_id,
                                             'seating_position' => $guest->seating_position);
            if($guest->seating_id != '' && $guest->seating_id != '0')
            {
                $data_guests[$guest->id]['placed'] = 1;
            }
            else
            {
                $data_guests[$guest->id]['placed'] = 0;
            }
        }
        
        $seatings = $event->seatings->find_all();
        $st_guests = array();
        foreach($seatings as $seating)
        {
            $st_guests[$seating->id] = $seating->guests->find_all();
        }

        
        $this->template->content = View::factory('event/seating')
                ->bind('errors', $errors)
                ->set('event_sidebar', Helper_Sidebar::event_sidebar($event, 'details', false))
                ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
                ->set('event_sidebar',Helper_Sidebar::event_sidebar($event))
                ->set('event',$event)
                ->set('seatings', $seatings)
                ->set('table_types', $data_tt)
                ->set('guests', $data_guests)
                ->set('st_guests', $st_guests)
                ->set('id',$id);
    }

    public function action_eventServiceCandidate()
    {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
        
            /** Проверка что это событие этого клиента **/
            if(!$this->check_security($post['event_id'], $this->user->client->id))
            {
                $this->redirect(Route::url('error', array('action' => 'notFound')));
            }
        
            $candidate = ORM::factory('EventServiceCandidate');
            try {
                $candidate->event_id = $post['event_id'];
                $candidate->event_service_id = $post['event_service_id'];
                $candidate->agent_id = $post['agent_id'];
                $candidate->save();
             } catch(ORM_Validation_Exception $e) {
                $errors = $e->errors();
            }
        }
    }

    public function action_getEventServices()
    {
        if ($this->request->is_ajax()) {
            $post = $this->request->post();
            $event_id = $post['event_id'];

            $services = ORM::factory('EventServices')->getEventServices($event_id);
            echo json_encode($services);
            die;
        }
    }
    
    public function action_invites()
    {
        $id = $this->request->param('id');
            
        /** Проверка что это событие этого клиента **/
        if(!$this->check_security($id, $this->user->client->id))
        {
            $this->redirect(Route::url('error', array('action' => 'notFound')));
        }
        
        $event = ORM::factory('Event',$id);
        
        if ($this->request->post()) {
            $post = $this->request->post();
            
            if(!isset($post['photo']) || empty($post['photo']))
            {
                if(is_file(DOCROOT.self::EVENT_INVITE_LOGO_PATH.'/'.$event->invites->photo))
                {
                    unlink(DOCROOT.self::EVENT_INVITE_LOGO_PATH.'/'.$event->invites->photo);
                }
                
                $event->invites->set('photo', '')->save();
                $post['photo'] = '';
            }
            
            if (isset($_FILES['invite_image']) && $post['tmp_photo']) {


                    $directory = DOCROOT.self::EVENT_INVITE_LOGO_PATH;
                    $tmp_directory = DOCROOT.self::TMP_EVENT_INVITE_LOGO_PATH;
                    if(is_file($tmp_directory . '/'.$post['tmp_photo']))
                    {
                        if (!is_dir($directory)) {
                            mkdir(DOCROOT.self::EVENT_INVITE_LOGO_PATH, 0777);
                        }

                        if(is_file($directory.'/'.$event->invites->photo))
                        {
                            unlink($directory.'/'.$event->invites->photo);
                        }

                       if (rename(DOCROOT.'tmp/user_logos/'.$post['tmp_photo'], DOCROOT.'media/user_logos/'.$post['tmp_photo'])) {
                           if(is_file($tmp_directory.'/'.$post['tmp_photo']))
                           {
                               unlink($tmp_directory.'/'.$post['tmp_photo']);
                           }
                           $post['photo'] = $post['tmp_photo'];
                       } else {
                           $errors[] = 'Файл не сохранился';
                       }

                    } else {
                        $errors[] = 'Файл не сохранился';
                    }
                } else {
                    $errors[] = 'Файл не загружен';
                }
            
            
            $event->invites->set('event_id', $id)
                ->set('background_id', $post['background_id'])
                ->set('color_id', $post['color_id'])
                ->set('figure_id', $post['figure_id'])
                ->set('description', $post['description'])
                ->set('photo', $post['photo'])
                ->save();
            
            $this->redirect(Route::url('event', array('action' => 'invites', 'id' => $id)));
        }
        
        $guests = $event->guests->order_by('id', 'ASC')->find_all();
        $guests_groups = $event->guests_groups->order_by('id', 'ASC')->find_all();
        
        $this->template->title = 'Гость';
        $this->template->content = View::factory('event/invites')
            ->bind('errors', $errors)
            ->set('guests',$guests)
            ->set('guests_groups',$guests_groups)
            ->set('colors',ORM::factory('InviteColors')->find_all())
            ->set('backgrounds',ORM::factory('InviteBackgrounds')->find_all())
            ->set('figures',ORM::factory('InviteFigures')->find_all())
            ->set('event_sidebar', Helper_Sidebar::event_sidebar($event))
            ->set('menu_sidebar',View::factory('event/menu_sidebar')->set('id',$id))
            ->set('event',$event)
            ->set('id',$id);
    }
    
    
    public function action_sendMail()
    {
        if($this->request->is_ajax())
        {
            $post = $this->request->post();
            
            $toemail = $post['email'];
            
            $to = $post['email'];
            
            $config = Kohana::$config->load('email');
            Email::connect($config);
         
            $subject = 'Сообщение от Коханой..т.е. Коханы.';
            $from = 'kohanaframework@test.ru';
            $message = $post['description'];
         
            Email::send($to, $from, $subject, $message, $html = false);
        }
    }
    
    public function check_security($event_id, $client_id)
    {
        $event = ORM::factory('Event')->where('id', '=', $event_id)->and_where('client_id', '=', $client_id)->find();
        if($event->loaded())
        {
            return true;
        }
        return false;
    }
}