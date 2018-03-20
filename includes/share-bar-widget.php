<?php

class FI_Share_Bar extends WP_Widget {
    function __construct()
    {
        parent::__construct(
            'fi_share_bar',
            esc_html__('Barre de partage'),
            ['description' => esc_html__('Barre de partage')]
        );
    }

    public function widget($args, $instance)
    {
        ?>
        <div class="row">
            <div class="col-sm-6">
                <a href="https://www.facebook.com/sharer/sharer.php?app_id=113869198637480&u=<?= urlencode(get_permalink()); ?>&display=popup&ref=plugin&src=share_button" class="btn btn-primary btn-block" style="border-color:#3b5998;background-color:#3b5998;"><strong>PARTAGER SUR FACEBOOK</strong></a>
            </div>
            <div class="col-sm-6">
                <a href="https://twitter.com/intent/tweet?original_referer=https%3A%2F%2Fpublish.twitter.com%2F&ref_src=twsrc%5Etfw&text=<?= urlencode($instance['tweet_text']); ?>&tw_p=tweetbutton&url=<?= urlencode(get_permalink()); ?>" class="btn btn-primary btn-block" style="border-color:#4099ff;background-color:#4099ff;"><strong>PARTAGER SUR TWITTER</strong></a>
            </div>
        </div>
        <?php
        return;
    }

    public function form($instance) {
        $video_id = $instance['tweet_text'] ?? 'Texte du tweet';
        $field_id = esc_attr($this->get_field_id('tweet_text'));
        $field_name = esc_attr($this->get_field_name('tweet_text'));
        ?>
        <p>
            <label for="<?= $field_id ?>">Text du tweet :</label>
            <textarea id="<?= $field_id  ?>" name="<?= $field_name ?>" type="text" value="<?= esc_attr($tweet_text); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['tweet_text'] = (!empty($new_instance['tweet_text'])) ? strip_tags($new_instance['tweet_text']) : '';

        return $instance;
    }
}
