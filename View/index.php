<div class="contentGreyBottom">
    <div class="boxContentGrey">
        <div class="contentGrey">
            <div class="boxAllEvents">
                <?php foreach($events_sidebars as $i => $events_sidebar): ?>
                    <?php echo $events_sidebar ?>
                <?php endforeach; ?>
				
                <div class="boxOneEvent">
                    <img src="<?php echo Kohana::$base_url ?>assets/img/img_without_event.png" alt="" border="0px">
                    <a href="<?php echo Kohana::$base_url ?>event/create" class="buttonEvent">добавить событие</a>
                    <p class="addNewAvents">Добавляй свои события</p>
                </div>
                <div class="clear"></div>
                <a href="#" class="archive">Архив событий</a>
                <div class="clear"></div>

            </div>
        </div>
    </div>
</div>