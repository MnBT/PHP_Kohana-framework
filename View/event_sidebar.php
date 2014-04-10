<?php if ($popup === true): ?>
<script type="text/javascript" src="<?php echo URL::base();?>assets/js/event_sidebar.js"></script>
<div id="event_edit_popup" class="hidden">
    <div class="popup_html">
        <div class='popup_header'>
            <span>Редактировать</span>
            <span class='popup_close_button'><img src='<?php echo Kohana::$base_url ?>assets/img/icon_close_2.png' /></span>
        </div>
        <div class='popup_content'>
            <form name="edit_event_form" id="edit_event_form" action="<?php echo Kohana::$base_url; ?>event/editEvent/<?php echo $event->id ?>" method="post" enctype="multipart/form-data">
                <div class='content_left'>
                    <label>Загрузить новую картинку:</label>
                    <input type='text' class="event_photo_name" style='width: 205px'/>
                    <input type="button" class='browse orange' value="обзор...">
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/gif" class="logo_event" style="display:none" />
                    <label>Дата события:</label>
                    <input class="datepicker" type='text' name='date' value="" />
                    <label>Страна:</label>
                    <div class="boxSelect">
                        <select id='country' name="country" class="se303 chosen-select" tabindex="1">
                            <?php foreach($countries as $country) : ?>
                                <option value="<?php echo $country->id; ?>"><?php echo $country->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label>Город:</label>
                    <div class="boxSelect">
                        <select id='city' name="city" class="se303 chosen-select" tabindex="1">
                            <?php foreach($cities as $city) : ?>
                                <option value="<?php echo $city->id; ?>"><?php echo $city->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class='content_right'>
                    <label>Название события:</label>
                    <input type='text' name='title' value="" />
                    <label>Планируемый бюджет:</label>
                    <input type='text' name='planned_budget' value=""/>
                    <label>Количество гостей:</label>
                    <input type='text' name='number_guest' value=""/>
                </div>
                <input type="hidden" name="action" value="edit" />
                <div class="clear"></div>
            </form>
        </div>
        <div class='popup_footer top'>
            <input type="button" class="blue save_event" value="сохранить" style='float:left;margin-left: 166px;'>
            <input type="button" class='orange close_popup' value="отменить" onclick="$('#event_edit_popup').togglePopup(); return false;" style='float:left;margin-left: 25px;'>
            <div class="clear"></div>
        </div>
    </div>
</div>
<div id="event_finish_popup" class="hidden">
    <div class="popup_html" style='width: 379px;'>
        <div class='popup_header'>
            <span>Завершить</span>
            <span class='popup_close_button'><img src='<?php echo Kohana::$base_url ?>assets/img/icon_close_2.png' /></span>
        </div>
        <div class='popup_content'>
            <form name="finish_event_form" class="finish_event_form" action="<?php echo Kohana::$base_url; ?>event/editEvent/<?php echo $event->id ?>" method="post">
                <label style='font-weight: bold;font-size: 17px;'></label>
                <input type="hidden" name="action" value="finish" />
            </form>
        </div>
        <div class='popup_footer top'>
            <input type="button" class='blue small_blue event_finish' value="да" style='width: auto;height: auto;padding: 12px 31px 10px 28px;'>
            <input type="button" class='orange small_orange' value="нет" onclick="$('#event_finish_popup').togglePopup(); return false;"  style='width: auto;height: auto;padding: 12px 31px 10px 28px;'>
            <div class="clear"></div>
        </div>
    </div>
</div>
<?php endif ?>
<!-- popups for edit and complete event -->
<div class="boxOneEvent">
    <div class="hidden event_data" data-info='{"id":"<?php echo $event->id ?>","title":"<?php echo $event->title ?>","date":"<?php echo date('d-m-Y',$event->date) ?>","planned_budget":<?php echo $event->planned_budget ?>, "number_guest":"<?php echo $event->number_guest ?>", "city_id": "<?php echo $event->city_id ?>", "country_id":"<?php echo $event->city->country->id ?>"}'><?php echo $event->title ?></div>
    <a href="/event/details/<?php echo $event->id ?>" class="name eventTitle"><?php echo $event->title ?></a>

        <a href="/event/details/<?php echo $event->id ?>"><div class="eventLogo" data-img="<?php echo Kohana::$base_url ?>media/event_logos/" style="background:url(<?php echo Kohana::$base_url.'media/event_logos/'.$event->logo ?>);width:229px;max-height: 143px" alt="" border="0px" ></div>

    <p class="iconPropositions"><?php echo $inbox_count; ?> предложения</p>
    <p class="iconViews"><?php echo $event->views ?></p>
    <div class="clear"></div>
    <div class="descriptionEvent">
        <div class="raitingEvent"><a style="width:60%"></a></div>
        <div class="topDescriptionEvent">
            <p><span>60%</span> сделанно</p>
            <p><span><?php echo $spent; ?> <?php echo $event->currency->display ?></span> потраченно</p>
            <p><span class="date_expire"><?php echo $date_expire; ?></span> дней</p>
            <div class="clear"></div>
        </div>
        <div class="mainDescriptionEvent">
            <div class="lineDescription">
                <p>Дата:</p><p class="eventDate"><?php echo date('d.m.Y',$event->date) ?></p>
                <div class="clear"></div>
            </div>
            <div class="lineDescription">
                <p>Город:</p><p class="eventCity" data-city-name="<?php echo $event->city->id ?>"><?php echo $event->city->name ?></p>
                <div class="clear"></div>
            </div>
            <div class="lineDescription">
                <p>Гостей:</p><p class="eventNumberGuest"><?php echo $event->number_guest ?></p>
                <div class="clear"></div>
            </div>
            <div class="lineDescription last">
                <p>Бюджет:</p><p><span class="eventBudget"><?php echo $event->planned_budget ?></span> <?php echo $event->currency->display ?></p>
                <div class="clear"></div>
            </div>
        </div>

        <div class="linksEvent">
            <?php if ($link == 'edit'):  ?>
                <a href="#" class="edit_event">Редактировать</a>
                <a href="#" class="finish_event">Завершить</a>
            <?php else: ?>
                <a href="#">Смотреть детальнее</a>
            <?php endif; ?>
            <div class="clear"></div>
        </div>
        <?php if (isset($several)): ?>
            <input type="hidden" class="several" data-several="true" />
             <style>
                 .boxOneEvent a.name{
                     display: block;
                     height: 45px;
                     word-wrap: break-word;
                    }
            </style>

        <?php else: ?>
            <input type="hidden" class="several" data-several="false" />
        <?php endif; ?>
    </div>
</div>