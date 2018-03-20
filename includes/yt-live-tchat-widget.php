<?php

$jlm2017_form_errors = '';
$jlm2017_form_signup_email = '';
$jlm2017_form_signup_zipcode = '';

class FI_YT_Live_Tchat_Widget extends WP_Widget {
    function __construct()
    {
        parent::__construct(
            'fi_yt_live_tchat_widget',
            esc_html__('Live Tchat Youtube'),
            ['description' => esc_html__('Live Tchat Youtube')]
        );
    }

    public function widget($args, $instance)
    {
        ?>
        <iframe width="500" src="https://www.youtube.com/live_chat?v=<?= esc_attr($instance['video_id']) ?>&embed_domain=<?= $_SERVER['HTTP_HOST'] ?>" height="400" frameborder="0" id="tchat"></iframe>
        <?php
        return;
    }

    public function form($instance) {
        $video_id = $instance['video_id'] ?? 'ID de la video';
        $field_id = esc_attr($this->get_field_id('video_id'));
        $field_name = esc_attr($this->get_field_name('video_id'));
        ?>
        <p>
            <label for="<?= $field_id ?>">Video ID :</label>
            <input class="widefat" id="<?= $field_id  ?>" name="<?= $field_name ?>" type="text" value="<?= esc_attr($video_id); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['video_id'] = (!empty($new_instance['video_id'])) ? strip_tags($new_instance['video_id']) : '';

        return $instance;
    }
}
