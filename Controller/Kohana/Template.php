<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_Controller_Template extends Controller_Main {

    /**
     * @var View page template
     */
    public $template = 'template';

    /**
     * @var  boolean auto render template
     **/
    public $auto_render = TRUE;

    /**
     * Loads the template [View] object.
     */

    public function before()
    {
        parent::before();

        if ($this->auto_render === TRUE)
        {
            $this->template = View::factory($this->template);
            $this->template->description = '';
            $this->template->agent_class = '';
            $this->template->auth_class= 'withNav_1';
            $uri = $this->request->uri();
            $page = ORM::factory('Page')->where('origin_url','=',$uri)->find();
            if ($page->loaded()) {
                $this->template->title = $page->title;
                $this->template->description = $page->description;

            } else {
                $page = ORM::factory('Page')->where('alias_url','=',$uri)->find();
                if ($page->loaded()) {
                    $this->template->title = $page->title;
                    $this->template->description = $page->description;
                }
            }
            //$this->template->head = View::factory(strtolower($this->request->controller()).'/head');
            $this->template->head = View::factory('layout/head');
            $this->template->additional_js = '';
            $this->template->top_footer = '';
            if (!$this->user) {
                $this->template->additional_js = HTML::script(Kohana::$base_url.'assets/js/guest.js');
                $this->template->header = View::factory('layout/guest/header')
                    //->set('bottom_header',View::factory('layout/guest/bottom_header'))
                    ->set('region_popup',View::factory('layout/regions')
                        ->set('country',ORM::factory('Country')->find_all())
                        ->set('city',ORM::factory('City')->getCityOfCountryFirst())
                    )
                    ->set('current_city',$this->user_city);
                $this->template->bottom_header = View::factory('layout/guest/bottom_header');
                $this->template->top_footer = View::factory('layout/guest/top_footer');
            }
            else {
                $this->user_role = $this->user->group->name;
                if ($this->user_role == 'agent' || $this->user_role == 'client') {
                    $cart_goods_count = ORM::factory('Cart')->getGoodsCount($this->user->id);
                }
                switch($this->user_role) {
                    case 'agent':
                        $candidate_count = ORM::factory('Agent')->getCandidateEventsCount($this->user->agent->id);
                        $purse = ORM::factory('Agent')->getPurse($this->user->agent->id);
                        $unread = ORM::factory('Inbox')->getUnreadMessagesCount($this->user->id);
                        $this->template->agent_class = 'forAgent';
                        $this->template->header = View::factory('layout/agent/header')
                            //->set('bottom_header',View::factory('layout/agent/bottom_header'))
                            ->set('region_popup',View::factory('layout/regions')
                                ->set('country',ORM::factory('Country')->find_all())
                                ->set('city',ORM::factory('City')->getCityOfCountryFirst())
                            )
                            ->set('purse',$purse)
                            ->set('cart',$cart_goods_count)
                            ->set('candidate_count',$candidate_count)
                            ->set('unread',$unread)
                            ->set('current_city',$this->user_city);
                        $this->template->bottom_header = View::factory('layout/agent/bottom_header');
                       $services_id = array();
                        foreach ($this->user->agent->services->find_all() as $service)
                        {
                            $services_id[] = $service->service_id;
                        }
                        $events = array();
                        $events_id = array();
                        foreach (ORM::factory('EventServices')->find_all() as $eventBudget)
                        {
                            if (in_array($eventBudget->service_id, $services_id)) {
                                if (!in_array($eventBudget->event_id, $events_id)) {
                                    $events[] = ORM::factory('Event')->where('id', '=', $eventBudget->event_id)->find();
                                    $events_id[] = $eventBudget->event_id;
                                }

                            }
                        }
                        $this->template->top_footer = View::factory('layout/top_footer_similar_events')
                        ->set('events', $events);
                        break;
                    case 'client':
                        $events = ORM::factory('Event')->getEventsForClient($this->user->client->id);
                        $next_event = ORM::factory('Event')->getNextEventForClient($this->user->client->id);
                        $next_event_day = $day = date_diff(date_create(date('d-m-Y',$next_event->date)),
                            date_create(date('d-m-Y')))->days;
                        $purse = ORM::factory('Client')->getPurse($this->user->client->id);
                        $this->template->header = View::factory('layout/client/header')
                            ->set('name',$this->user->client->firstname==''?$this->user->email:$this->user->client->firstname)
                            ->set('region_popup',View::factory('layout/regions')
                                ->set('country',ORM::factory('Country')->find_all())
                                ->set('city',ORM::factory('City')->getCityOfCountryFirst())
                            )
                            ->set('events',$events)
                            ->set('nex_event',$next_event)
                            ->set('nex_event_day',$next_event_day)
                            ->set('purse',$purse)
                            ->set('cart',$cart_goods_count)
                            ->set('current_city',$this->user_city);
                        $this->template->bottom_header = View::factory('layout/client/bottom_header');
                        $this->template->top_footer = View::factory('layout/top_footer_professionals')
                        ->set('agents', ORM::factory('Agent')->where('city_id','=',$this->user->client->city_id)->find_all());
                        break;
                    case 'admin':
                        //admin header
                        break;
                }
            }
            $this->template->content = '';
            $this->template->footer =  View::factory('layout/footer');
        }
    }

    /**
     * Assigns the template [View] as the request response.
     */
    public function after()
    {
        if ($this->auto_render === TRUE)
        {
            $this->response->body($this->template->render());
        }

        parent::after();
    }

}
