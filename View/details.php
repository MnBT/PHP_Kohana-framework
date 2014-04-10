<div id="add_service_popup" class="hidden">
	<div class="popup_html">
		<div class='popup_header'>
			<span>Добавить услугу</span>
			<span class='popup_close_button'><img src='<?php echo Kohana::$base_url ?>assets/img/icon_close_2.png' /></span>
		</div>
		<div class='popup_content'>
			<form action="#">
				<label>Наименование:</label>
				<div class="boxSelect">	
					<select id='add_budget_name' name="group_id" class="se303 chosen-select" tabindex="1">
                        <option value="0">Выбрать</option>	
                        <?php foreach($services as $service) : ?>
                        <option value="<?php echo $service->id; ?>"><?php echo $service->name; ?></option>
                        <?php endforeach; ?>
					</select>
				</div>
			</form>
		</div>
		<div class='popup_footer top'>
			<input type="button" class='add_service_save blue' value="добавить" />
			<input type="button" class='orange' value="отменить" onclick="$('#add_budget_popup').togglePopup(); return false;">
			<div class="clear"></div>
		</div>
	</div>
</div>
<div id="add_video_popup" class="hidden">
    <div class="popup_html">
        <div class='popup_header'>
            <span>Добавить видео</span>
            <span class='popup_close_button'><img src='<?php echo Kohana::$base_url ?>assets/img/icon_close_2.png' /></span>
        </div>
        <div class='popup_content'>
            <form action="#" name="form_add_video">
                <label><input class="video_tab" type="radio" id="tab_link" name="from" checked="" value="link" /> По ссылке</label>
                <label><input class="video_tab" type="radio" id="tab_device" name="from" value="device" /> С компьютера</label>
                <div id="for_tab_link" class="video_body">
                    <label>Ссылка:</label>
                    <input type="text" name="video_name" class="add_video_link" value="" />
                </div>
                <div id="for_tab_device" class="video_body" style="display: none;">
                    <div class="iconAdd2"><a href="#" id="details_add_video_file">Выбрать файл</a></div>
                </div>
            </form>
        </div>
        <div class='popup_footer top'>
            <input type="button" class='add_video_save blue' value="добавить" />
            <input type="button" class='orange' value="отменить" onclick="$('#add_video_popup').togglePopup(); return false;">
            <div class="clear"></div>
        </div>
    </div>
</div>
<div class="contentGreyBottom">
<div class="boxContentGrey">
    <div class="contentGrey">
        <div class="contentLeft2">
            <?php echo $event_sidebar ?>
            <?php echo $menu_sidebar ?>
        </div>
        <div class="contentRight2 pad">
            <div class="boxdescriptionWorker">
                <div class="listGuests">
                    <div class="listGuests"><form name="event_details_form" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" id="es_event_id" value="<?php echo $event->id; ?>" />
                            <input type="hidden" name="es_sid" id="es_sid" value="<?php echo $event_first_service->id ?>" />
                            <div class="boxInSearch">
                                <h2>Я в поиске</h2>
                                <p class="textMain">Успешный руководитель, знаете, что в любом важном деле в первую очередь ценится проф. Успешный руководитель, знаете, что в любом важном деле в первую очередь ценится проф. Успешный руководитель, знаете, что в любом важном деле в первую очередь ценится проф. Успешный руководитель, знаете, что в любом важном деле в первую очередь ценится проф. Успешный руководитель, знаете, что в любом важном деле в первую<br/> очередь ценится проф.</p>
                                <div class="blockImg">
                                    <div class="boxService" id="box_service">
                                        <h3>Услуги</h3>
                                        <div id="services_list">
                                            <?php foreach($event_services as $es) : ?>
                                                <div class="oneService" data-id="<?php echo $es->id; ?>">
                                                    <?php echo $services[$es->service_id]->name; ?>
                                                    <a href="#" class="delProduct"></a>
                                                    <input type="hidden" name="budget_value[<?php echo $es->id; ?>]" value="<?php echo $services[$es->service_id]->id; ?>" />
                                                    <input type="hidden" name="budget_exists[<?php echo $es->id; ?>]" value="1" />
                                                </div>
                                                <input type="hidden" name="budget[<?php echo $es->id; ?>]" value="1" />
                                            <?php endforeach; ?>
                                            <div class="iconAdd2"><a id="details_add_service" href="#">Добавить</a></div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>


                                <div class="blockInf1 hidden">
                                    <h2 id="es_name"><?php echo $event_first_service->services->name ?></h2>
                                    <div class="openRowBlock">
                                        <div class="rowLeft">
                                            <div class="textTop2">
                                                <p>Позиция добавлена: <span id="es_date_added"><?php echo  $event_first_service->date_added ? date('d-m-Y', $event_first_service->date_added) : 'не указано' ?></span></p>
                                                <p>Конечный срок: <span id="es_deadline"><?php echo  $event_first_service->deadline ? date('d-m-Y', $event_first_service->deadline) : 'не указано' ?></span></p>
                                                <div class="clear"></div>
                                            </div>
                                            <h3>Описание</h3>
                                            <p id="details_description"><?php echo $event_first_service->description ?></p>
                                            <div class="text-editor" id="details_description_container"><textarea id="details_description_textarea"><?php echo $event_first_service->description ?></textarea></div>
                                            <a id="details_edit_description" href="#">Редактировать</a>
                                            <input id="input_description" type="hidden" name="description" value="" />
                                            <div class="clear"></div>
                                        </div>
                                        <div class="rowRight">
                                            <?php if ($event_first_service->cost): ?>
                                                <div class="blockGreyPrice">
                                                    <p>Бюджет: <span><span id="es_budget" style="display:inline;"><?php echo $event_first_service->cost ?></span> <?php echo $event->currency->display ?></span></p>
                                                </div>
                                            <?php endif; ?>
                                            <div class="rowRightText">
                                                <p>Всего предложений: <span id="es_propos">24</span></p>
                                                <p>Кандидатов: <a href="#" id="es_candidates">3</a> <span class="boxPrompt"><span class="imgPrompt"><a href="#"><img src="img/img_prompt.png"   alt="" border="0px" /></a> <a href="#"><img src="img/img_prompt.png"   alt="" border="0px" /></a> <a href="#"><img src="img/img_prompt.png"   alt="" border="0px" /></a><span class="promptBottom"></span></span></span></p>
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                        <div class="blockPhotoTab" id="event_photos">
                                            <h3>Фото <img src="<?php echo Kohana::$base_url ?>assets/img/icon_title_1.png" align="absmiddle"   alt="" border="0px" /></h3>
                                            <div class="preview" id="es_photos">
                                                <?php foreach($event_first_service_photos as $photo): ?>
                                                    <div class="blockImg">
                                                        <a href="#"><img src="<?php Kohana::$base_url ?>media/event_photos/<?php echo $photo->link ?>" alt="" border="0px"></a>
                                                        <input type="hidden" class="photo_exists" name="photo_exists[<?php echo $photo->id ?>]" value="1">
                                                        <span class="delProduct"></span>
                                                    </div>
                                                    <input type="hidden" name="photo[<?php echo $photo->id ?>]" value="<?php echo $photo->link ?>">
                                                <?php endforeach; ?>
                                                <div class="clear"></div>
                                            </div>
                                            <input type="file" id="input_add_photos" name="photos" multiple="multiple" accept="image/jpeg,image/png,image/gif" />
                                            <div class="iconAdd2"><a href="#">Добавить</a></div>
                                        </div>
                                        <div class="blockPhotoTab">
                                            <h3>Видео <img src="<?php echo Kohana::$base_url ?>assets/img/icon_title_2.png" align="absmiddle"   alt="" border="0px" /></h3>
                                            <div class="previewVideo">
                                                <input type="file" name="video_load" id="video_load" value="" accept="video/avi,video/mpg,video/ogg,video/mp4" />
                                                <div id="es_videos">
                                                    <?php foreach($event_first_service_videos as $video): ?>
                                                        <div class="blockImg">
                                                            <a href="#"><img src="<?php echo $video->thumbnail ?>" alt="" border="0px"></a><a href="#" class="boxVideoTop2"></a>
                                                            <input type="hidden" name="video_link[<?php echo $video->id ?>]" value="<?php echo $video->thumbnail ?>">
                                                            <input type="hidden" name="video_exists[<?php echo $video->id ?>]" value="1">
                                                            <span class="delProduct"></span>
                                                        </div>
                                                        <input type="hidden" name="video[<?php echo $video->id ?>]" value="1">
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                            <div class="iconAdd2"><a href="#" id="details_add_video">Добавить</a></div>
                                        </div>
                                        <a href="#" id="budget_details_save" class="button_4 save">Сохранить</a>
                                        <a href="#" id="w_reload" class="button_5">Отменить</a>
                                        <div class="clear"></div>
                                        <div class="top_19"></div>
                                    </div>
                                </div>


                            </div>
                        </form></div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>
