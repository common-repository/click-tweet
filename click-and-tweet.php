<?php
/**
 * Plugin Name: Click & Tweet
 * Plugin URI: http://spacestud.io
 * Description: Quote text in your WordPress posts for easy sharing on twitter.
 * Version: 0.8.9
 * Author: Space Studio
 * Author URI: http://spacestud.io
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: click_and_tweet
 * Domain Path: /languages

 * Click & Tweet is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.

 * Click & Tweet is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Click & Tweet. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, array('ClickAndTweet', 'activation'));

/**
 * Deactivation Hook
 */
register_deactivation_hook(__FILE__, array('ClickAndTweet', 'deactivation'));

/**
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, array('ClickAndTweet', 'uninstall'));

/**
 * Click & Tweet
 */
class ClickAndTweet
{
    /**
     * Plugin Settings.
     *
     * @var array
     */
    public $settings;

    /**
     * Plugin version
     * @var string
     */
    protected $version = '0.8.8';

    /**
     * Plugin option name.
     *
     * @var string
     */
    static $pluginOptionName = 'click_and_tweet_settings';

    /**
     * Plugin option name.
     *
     * @var string
     */
    public $optionName = 'click_and_tweet_settings';

    /**
     * Inline Layout Positions
     *
     * @var array
     */
    protected $inlinePositions = ['left' => 'Left', 'right' => 'Right'];

    /**
     * Card Layout Positions
     *
     * @var array
     */
    protected $cardPositions = [
        'center-top' => 'Center Top',
        'center-bottom' => 'Center Bottom',
        'left-center' => 'Left Center',
        'left-top' => 'Left Top',
        'left-bottom' => 'Left Bottom',
        'right-center' => 'Right Center',
        'right-top' => 'Right Top',
        'right-bottom' => 'Right Bottom'
    ];

    /**
     * Types of url Shorteners
     * @var array
     */
    protected $urlShorteners = [
        'none' => 'None',
        'wp' => 'WordPress',
        'google' => 'Google',
        'bitly' => 'Bitly'
    ];

    /**
     * Google shortener api key
     * @var string
     */
    protected $clickAndTweetApiKey = 'AIzaSyBqZoC8D1Dif4NDQoN0GnVnAWFbwfXCT-Y';

    /**
     *	Types of Posts
     * @var array
     */
    protected $postTypes = ['post', 'page'];

    /**
     * admin pages
     * @var array
     */
    protected $adminPages = ['settings_page_click-and-tweet', 'post.php', 'post-new.php'];

    /**
     * Call to Action Text
     * @var string
     */
    public $callToActionText = 'Click & Tweet!';

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->optionName = self::$pluginOptionName;

        $this->settings = get_option($this->optionName);

        if (is_admin()) {
            $this->adminInit();
        }

        $this->init();
    }

    /**
     * Allowed post types of plugin.
     * @return array
     */
    public function getPostTypes()
    {
        return apply_filters('click_and_tweet_post_types', $this->postTypes);
    }

    /**
    * Checks to see if editor is allowed on post
    * @return boolean
    */
    public function isAllowed()
    {
        global $post;

        if(isset($post) && in_array($post->post_type, $this->getPostTypes())){
            return true;
        }
        return false;
    }

    /**
     * Initilize in the admin.
     */
    public function adminInit()
    {
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_init', array($this, 'editorStyle'));
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('admin_menu', array($this, 'pluginMenu'));
        add_action('admin_head', array($this, 'editorButton'));
    }

    /**
     * Initilize on the website.
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_shortcode('clickandtweet', [$this, 'clickAndTweetShortCode']);
        add_action('post_updated', [$this, 'checkPermalinkChanges'], 10, 3);
    }

    /**
     * Enqueue scripts needed for the admin
     */
    public function enqueueAdminScripts($hook)
    {

        if (!in_array($hook, $this->adminPages)) {
            return;
        }

        wp_enqueue_style(
            $handle = 'click-and-tweet-css',
            $src = plugins_url('/assets/css/click-and-tweet.css', __FILE__),
            $deps = false,
            $ver = $this->version,
            $media = ''
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script(
            $handle = 'click-and-tweet-js',
            $src = plugins_url('/assets/js/click-and-tweet.js', __FILE__),
            $deps = ['jquery'],
            $ver = $this->version,
            $in_footer = true
        );

        // Register the script
        wp_register_script(
        'click_and_tweet_js_variables',
        plugins_url('/assets/js/click-and-tweet-admin.js', __FILE__),
        array( 'wp-color-picker' ),
        $ver = $this->version
        );

        // Localize the script with new data
        $data = array(
            'pluginUrl' => plugins_url('', __FILE__),
        );

        //sets the values for username, inline positions, and card positions as long as they are selected, to change the default.
        if(!empty($this->settings['use_default_username']) && isset($this->settings['default_username'])) {
            $data['defaultUsername'] = $this->settings['default_username'];
        }

        if(!empty($this->settings['inline_tweet_position'])) {
            $data['defaultInlinePosition'] = $this->settings['inline_tweet_position'];
        }

        if(!empty($this->settings['card_tweet_position'])) {
            $data['defaultCardPosition'] = $this->settings['card_tweet_position'];
        }


        wp_localize_script('click_and_tweet_js_variables', 'click_and_tweet', $data);

        // Enqueued script with localized data.
        wp_enqueue_script('click_and_tweet_js_variables');
    }

    /**
     * Enqueue all the scripts for the plugin.
     */
    public function enqueueScripts()
    {
        //Enqueue the css file
        wp_enqueue_style(
            $handle = 'click-and-tweet-css',
            $src = plugins_url('/assets/css/click-and-tweet.css', __FILE__),
            $deps = false,
            $ver = $this->version,
            $media = ''
        );

        //Enqueue the js file
        wp_enqueue_script(
            $handle = 'click-and-tweet-js',
            $src = plugins_url('/assets/js/click-and-tweet.js', __FILE__),
            $deps = ['jquery'],
            $ver = $this->version,
            $in_footer = true
        );
    }

    /**
     * Add stylesheet to the editor.
     */
    public function editorStyle()
    {
        add_editor_style(plugins_url('/assets/css/click-and-tweet-editor.css', __FILE__));
    }

    /**
     * Plugin Activation. Sets the default settings.
     */
    public static function activation()
    {
        $option = get_option(self::$pluginOptionName);

        if(!isset($option['inline_tweet_position'])) {
            $option['inline_tweet_position'] = 'right';
            update_option(self::$pluginOptionName, $option);
        }

        if(!isset($option['card_tweet_position'])) {
            $option['card_tweet_position'] = 'left-center';
            update_option(self::$pluginOptionName, $option);
        }

    }

    /**
     * Plugin Deactivation.
     */
    public static function deactivation()
    {
        //
    }

    /**
     * Function to uninstall the plugin
     */
    public static function uninstall()
    {
        delete_option(self::$pluginOptionName);

        // For site options in Multisite
        delete_site_option(self::$pluginOptionName);
    }

    /**
     * Initializes the plugin menu. Gives it a title.
     */
    public function pluginMenu()
    {
        add_options_page(
            $page_title = 'Click & Tweet',
            $menu_title = 'Click & Tweet',
            $capability = 'activate_plugins',
            $menu_slug = 'click-and-tweet',
            array($this, 'pluginSettings')
         );
    }

    /**
     * Initializes the setinggs page and the fields.
     */
    public function pluginSettings()
    {
        ?>
            <div class="wrap">
                <h2>Click & Tweet Settings</h2>
                <p>&nbsp;</p>

                <form class="" action="options.php" method="post" enctype="multipart/form-data">
                    <?php
                        settings_fields('click_and_tweet_settings');
                        do_settings_sections('click_and_tweet_settings');
                        submit_button('Update Settings');
                    ?>
                </form>
                <footer><i>Click & Tweet - Version <?php echo $this->version ?></i></footer>
            </div>
        <?php

    }

    /**
     * Function to register the settings.
     */
    public function registerSettings()
    {
        $this->registerPluginSettings();
    }

    /**
     * Registers all the setting fields.

     */
    public function registerPluginSettings()
    {
        register_setting(
            'click_and_tweet_settings',
            'click_and_tweet_settings', $sanitize_callback = [$this, 'sanitizeSettings']
        );

        add_settings_field(
            $id = 'use_default_username',
            $title = 'Display Username',
            $callback = array($this, 'callbackHandleDefaultField'),
            $page = 'click_and_tweet_settings',
            $section = 'click_and_tweet_settings'
        );


        add_settings_field(
            $id = 'default_username',
            $title = 'Default Username',
            $callback = array($this, 'callbackTwitterHandleField'),
            $page = 'click_and_tweet_settings',
            $section = 'click_and_tweet_settings',
            $args = ['class' => (empty($this->settings['use_default_username'])) ? 'default-username hidden' : 'default-username']
        );

        add_settings_field(
            $id = 'use_auto_truncate',
            $title = 'Auto Truncate',
            $callback = array($this, 'callbackTwitterAutoTruncateField'),
            $page = 'click_and_tweet_settings',
            $section = 'click_and_tweet_settings'
        );

        add_settings_field(
            $id = 'inline_tweet_position',
            $title = 'Default Inline Position',
            $callback = array($this, 'callbackInlineTweetPositionField'),
            $page = 'click_and_tweet_settings',
            $section = 'layout_positioning'
        );

        add_settings_field(
            $id = 'card_tweet_position',
            $title = 'Default Card Position',
            $callback = array($this, 'callbackCardPositionField'),
            $page = 'click_and_tweet_settings',
            $section = 'layout_positioning'
        );

        add_settings_field(
            $id = 'tweet_color',
            $title = 'Tweet Color Option',
            $callback = array($this, 'callbackTweetColorField'),
            $page = 'click_and_tweet_settings',
            $section = 'appearance_settings',
            $args = ['class' => 'tweet-color-setting']
        );

        add_settings_field(
            $id = 'url_shorteners',
            $title = 'URL Shorteners',
            $callback = array($this, 'callbackUrlShortenerField'),
            $page = 'click_and_tweet_settings',
            $section = 'url_shortener_section'
        );

        add_settings_field(
            $id = 'bitly_access_code',
            $title = '',
            $callback = array($this, 'callbackBitlyShortenerField'),
            $page = 'click_and_tweet_settings',
            $section = 'url_shortener_section',
            $args = ['class' => (isset($this->settings['url_shorteners']) && $this->settings['url_shorteners'] == 'bitly') ? 'bitly-shortener' : 'bitly-shortener hidden']
        );

        add_settings_section(
            $id = 'click_and_tweet_settings',
            $title = 'General Settings',
            $callback = array($this, 'callbackTwitterHandleSection'),
            $page = 'click_and_tweet_settings'
        );

        add_settings_section(
            $id = 'layout_positioning',
            $title = 'Layout and Positioning',
            $callback = array($this, 'callbackLayoutSection'),
            $page = 'click_and_tweet_settings'
        );

        add_settings_section(
            $id = 'appearance_settings',
            $title = 'Appearance',
            $callback = array($this, 'callbackAppearanceSection'),
            $page = 'click_and_tweet_settings'
        );

        add_settings_section(
            $id = 'url_shortener_section',
            $title = 'Link Shorteners',
            $callback = array($this, 'callbackUrlShortenerSection'),
            $page = 'click_and_tweet_settings'
        );
    }

    /**
     * call back function for settings section
     */
    public function callbackTwitterHandleSection()
    {
        echo '';
    }

    public function callbackLayoutSection()
    {
        echo '';
    }

    public function callbackAppearanceSection()
    {
        echo '';
    }

    public function callbackUrlShortenerSection()
    {
        echo '';
    }
    /**
     * function for handle settings field. Adds a text input for twitter handle.
     */
    public function callbackTwitterHandleField($args)
    {
        $setting = isset($this->settings['default_username']) ? ($this->settings['default_username']) : '';
        echo  "<input type='text' name='click_and_tweet_settings[default_username]' value='$setting' class='regular-text' placeholder='username'/>";

    }

    /**
     * function to set the function of auto setting the handler to on or off.
     */
    public function callbackHandleDefaultField()
    {
        $checked_yes = (isset($this->settings['use_default_username']) && $this->settings['use_default_username'] == 1) ? 'checked' : '';
        $checked_no = (!isset($this->settings['use_default_username']) || $this->settings['use_default_username'] == 0) ? 'checked' : '';
        $html = "<input type='radio' name='click_and_tweet_settings[use_default_username]' value='1' $checked_yes /> On <input type='radio' name='click_and_tweet_settings[use_default_username]' value='0' $checked_no /> Off";
        $html .= "<p class='description'>Display your username in tweets by default?</p>";
        echo $html;
    }

    /*
     * function to call back the Auto Truncate Settings field.
     */
     public function callbackTwitterAutoTruncateField()
     {
        $checked_yes = (isset($this->settings['use_auto_truncate']) && $this->settings['use_auto_truncate'] == 1) ? 'checked' : '';
        $checked_no = (!isset($this->settings['use_auto_truncate']) || $this->settings['use_auto_truncate'] == 0) ? 'checked' : '';
        $html = "<input type='radio' name='click_and_tweet_settings[use_auto_truncate]' value='1' $checked_yes /> On <input type='radio' name='click_and_tweet_settings[use_auto_truncate]' value='0' $checked_no /> Off";
        $html .= "<p class='description'>Auto truncate text to avoid exceeding 140 characters on Twitter.</p>";
        echo $html;
     }

    /**
     * callback function that returns a select menu of inline tweet Positions
     */
    public function callbackInlineTweetPositionField()
    {
        $options = '';
        $selected = isset($this->settings['inline_tweet_position']) ? $this->settings['inline_tweet_position'] : '';

        foreach ($this->inlinePositions as $key => $value) {
            $isSelected = ($key == $selected) ? ' selected' : '';
            $options .= "<option value=\"$key\"$isSelected>$value</option>";
        }

        $selectMenu = "<select name=\"click_and_tweet_settings[inline_tweet_position]\">$options</select>";

        echo $selectMenu;
    }

    /**
     * callback function that returns a select menu of card Positions
     */
    public function callbackCardPositionField()
    {
        $options = '';
        $selected = isset($this->settings['card_tweet_position']) ? $this->settings['card_tweet_position'] : '';

        foreach ($this->cardPositions as $key => $value) {
            $isSelected = ($key == $selected) ? ' selected' : '';
            $options .= "<option value=\"$key\"$isSelected>$value</option>";
        }

        $selectMenu = "<select name=\"click_and_tweet_settings[card_tweet_position]\">$options</select>";

        echo $selectMenu;
    }

    /**
     * callback function for the twitter color field.
     */
    public function callbackTweetColorField()
    {
        $tweetColorValue = (isset( $this->settings['tweet_color'] ) ) ? $this->settings['tweet_color'] : '';
        echo '<input type="text" name="click_and_tweet_settings[tweet_color]" value="' . $tweetColorValue . '" class="tweet-color-field" >';

    }

    /**
     * callback function for the url shortener field.
     */
    public function callbackUrlShortenerField()
    {
        $options = '';
        $selected = isset($this->settings['url_shorteners']) ? $this->settings['url_shorteners'] : '';

        foreach ($this->urlShorteners as $key => $value) {
            $isSelected = ($key == $selected) ? ' selected' : '';
            $options .= "<option value=\"$key\"$isSelected>$value</option>";
        }

        $selectMenu = "<select name=\"click_and_tweet_settings[url_shorteners]\">$options</select>";

        echo $selectMenu;

    }

    /**
     * callback function for the bitly shortener field.
     */
    public function callbackBitlyShortenerField()
    {
        $setting = isset($this->settings['bitly_access_code']) ? ($this->settings['bitly_access_code']) : '';
        $html = "<p><strong>Access Token</strong></p>";
        $html .= "<input type='text' name='click_and_tweet_settings[bitly_access_code]' value='$setting' class='regular-text' placeholder='Bitly Access Token'/>";
        $html .= '<p class="description"><a href=\https://space-studio.gitbooks.io/click-tweet-plugin/content/PLUGINSETTINGS.html\>Read Documentation</a></p>';
        echo $html;
    }
    /**
     * sanitizes the setting for the twitter handle and adds '@' to the beginning.
     */
    public function sanitizeSettings($setting)
    {
        if (isset($setting['use_default_username']) && (isset($setting['default_username']) && (!empty($setting['default_username']) && $setting['default_username'][0] != '@'))){
            $setting['default_username'] = '@'.$setting['default_username'];
        }

        return $setting;
    }

    /**
     * Add plugin button to the editor.
     */
    public function editorButton()
    {
        if ($this->isAllowed() && get_user_option('rich_editing') == 'true') {
            add_filter('mce_external_plugins', [$this, 'tinyMcePlugin']);
            add_filter('mce_buttons', [$this, 'tinyMceButton']);
        }
    }

    public function tinyMcePlugin($plugin_array)
    {
        $plugin_array['noneditable'] = plugins_url('/assets/js/tinymce/noneditable.js', __FILE__);
        $plugin_array['click_and_tweet'] = plugins_url('/assets/js/click-and-tweet-tinymce-plugin.js', __FILE__);

        return $plugin_array;
    }

    /**
     * Add Click & Tweet Button to the editor toolbar.
     *
     * @param array $buttons buttons from the editor
     *
     * @return array
     */
    public function tinyMceButton($buttons)
    {
        $buttons[] = 'click_and_tweet_button';

        return $buttons;
    }

    /**
     * Shortcode function.
     *
     * @param array  $atts
     * @param string $content
     *
     * @return string
     */
    public function clickAndTweetShortCode($atts, $content)
    {
        $content = (isset($atts['layout']) && ($atts['layout'] == 'card')) ? $this->cardTweet($atts, $content) : $this->inlineTweet($atts, $content);
        return $content;
    }

    /**
     * Build the url for the shortcode
     * @param  array $atts
     * @param string $content
     * @return string $url
     */
    private function shortcodeUrl($atts, $content)
    {
        $url = 'https://twitter.com/intent/tweet?'.http_build_query($this->getQuery($atts, $content));

        return $url;
    }

    /**
     * Build the query for the link
     * @param  array $atts
     * @return array  $query
     */
    private function getQuery($atts = array(), $content)
    {
        $text = html_entity_decode($content);
        $username = $this->getUsername($atts);
        $url = $this->getUrl();
        $hashtags = $this->getHashtags($atts);
        $related = $this->getRelated($atts);
        $query = [
            'text' => $this->autoTruncate($text, $username, $url, $hashtags),
            'url' => $url,
            'hashtags' =>  $hashtags,
            'related' => $related
        ];

        if($username) $query['via'] = $username;

        if($related) $query['related'] = $related;

        return $query;
    }

    /**
     * Auto Truncates the tweet to prevent the tweet from exceeding 140 characters
     * @param  string $text, $username, $url, $hashtag
     * @return string $text
     */
    private function autoTruncate($text, $username, $url, $hashtag)
    {
        if(empty($this->settings['use_auto_truncate'])) return $text;

        $more = "...";
        $moreLength = strlen($more);
        $textLength = strlen($text);
        $usernameLength = ($username) ? strlen($username) + 1 : 0;
        $viaLength = ($username) ? 4 : 0;
        $hashtagCount = (!empty($hashtag) && count(explode(',',$hashtag)) > 1) ? count(explode(',',$hashtag)) : 0;
        $hashtagLength = strlen($hashtag) + $hashtagCount + 1;
        $urlLength = (strlen($url) >= 23) ? 21 : strlen($url) + 1;

        $totalLength = $textLength + $hashtagLength + $viaLength + $usernameLength + $urlLength + $moreLength;
        $x = $hashtagLength + $viaLength + $usernameLength + $urlLength + $moreLength;

        if($totalLength > 140) {
            $text = wp_html_excerpt($text, 140);
            $y = strlen($text) - $x;
            $text = wp_html_excerpt($text, $y - 3, $more);
        }

        return $text;
    }

    /**
     * Get the url for the tweet
     * @return string
     */
    private function getUrl()
    {
        if(isset($this->settings['url_shorteners'])) {

            if($this->settings['url_shorteners'] == 'wp') {
                return $url = wp_get_shortlink();
            }

            if($this->settings['url_shorteners'] == 'google') {

                return $url = $this->getGoogleShortUrl();
            }
            if($this->settings['url_shorteners'] == 'bitly') {

                return $url = $this->getBitlyShortUrl();
            }
        }

        return $url = get_permalink();
    }

    /**
     * Get username from shortcode attributes
     * @param  array $atts
     * @return string $username
     */
    private function getUsername($atts = array())
    {
        $defaultUsername = !empty($this->settings['use_default_username']) &&
                           isset($this->settings['default_username']) ? $this->settings['default_username'] : '';
        $handle = (!empty($atts['handle'])) ? $atts['handle'] : $defaultUsername;
        $username = trim(str_replace('@', '', $handle));

        return $username;
    }

    /**
     * Get hash tags from shortcode attributes
     * @param  array $atts
     * @return $hashtags
     */
    private function getHashtags($atts = array())
    {
        if(isset($atts['hashtag'])){
            $hashtags = $atts['hashtag'];
            $hashtags = trim(str_replace('#', '', $hashtags));
            $hashtags = explode(' ', $hashtags);
            $hashtags = implode(',', $hashtags);
            $hashtags = rtrim($hashtags, ',');

            return $hashtags;
        }

        return $hashtags = '';
    }

    /**
     * Get related usernames
     * @param  array $atts
     * @return string $related
     */
    private function getRelated($atts)
    {
        if(isset($atts['related'])){
            $related = $atts['related'];
            $related = str_replace('@', '', $related);
            $related = explode(' ', $related);
            $related =  implode(',', $related);

            return $related;
        }

        return $related = '';
    }

    /**
     * gets the color setting and applys it to the tweet text.
     * @return $style
     */
    private function getColor()
    {
        $color = isset($this->settings['tweet_color']) ? $this->settings['tweet_color'] : '';
        $style = !empty($this->settings['tweet_color']) ? " style=\"color:$color\" " : '';
        return $style;
    }

    /**
     * Create an inline tweet
     * @param  array $atts
     * @param  string $content
     * @return string
     */
    public function inlineTweet($atts, $content)
    {
        $position = (!empty($atts['position'])) ? ' '.$atts['position']  : ' '.$this->settings['inline_tweet_position'];
        $style = $this->getColor();
        $linkAttributes = "href=\"{$this->shortcodeUrl($atts, $content)}\"
                            target=\"_blank\"
                            class=\"click-and-tweet-inline$position\"
                            $style";

        $twitterLogo = "<span class=\"click-and-tweet-twitter-logo\">
                            <span class=\"call-to-action-text\">{$this->filterCallToAction()}</span>
                        </span>";

        $linkContent = (isset($position) && trim($position) == 'left') ? "{$twitterLogo}{$this->filterContent($content)}" : "{$this->filterContent($content)}{$twitterLogo}";

        $content = "<a {$linkAttributes}>{$linkContent}</a>";

        return $content;
    }

    /**
     * Create a tweet card
     * @param  array $atts
     * @param  string $content
     * @return string
     */
    public function cardTweet($atts, $content)
    {
        $position = (!empty($atts['position'])) ? ' '.$atts['position']  : ' '.$this->settings['card_tweet_position'];
        $style = $this->getColor();
        $linkAttributes = "href=\"{$this->shortcodeUrl($atts, $content)}\"
                            target=\"_blank\"
                            class=\"click-and-tweet-card-link$position\"
                            $style";

        $twitterLogo = "<span class=\"click-and-tweet-twitter-logo\"></span>";

        $linkContent = "<span class=\"click-and-tweet-card-text\">{$this->filterContent($content)}</span>";

        switch($atts['position']) {
            case 'left-center':
            case 'left-top':
            case 'left-bottom':
            case 'right-center':
            case 'right-top':
            case 'right-bottom':
            case 'center-top':
                $tweet = "{$twitterLogo}{$linkContent}";
                break;
            default:
                $tweet = "{$linkContent}{$twitterLogo}";
        }

        $content = "<div class=\"click-and-tweet-card$position\">
                        <a {$linkAttributes}>$tweet<span class=\"call-to-action-text\">{$this->filterCallToAction()}</span></a>
                    </div>";

        return $content;
    }

    /*
     * Function to get the url shortened using the google api.
     * Adds post meta if it doesnt exist.
     */
    public function getGoogleShortUrl()
    {
        global $post;

        if(! $googleShortUrl = get_post_meta($post->ID, 'click_and_tweet_google_url', true)) {

            $key = $this->clickAndTweetApiKey;
            $url = 'https://www.googleapis.com/urlshortener/v1/url?key='.$key;

            $response = wp_remote_post( $url, array(
                'method' => 'POST',
    	        'headers' => array('content-type' => 'application/json'),
    	        'body' => json_encode(array('longUrl' => get_permalink())),
            ));

            $urlBody = json_decode($response['body'], true);
            $googleShortUrl = $urlBody['id'];

            add_post_meta($post->ID, 'click_and_tweet_google_url', $googleShortUrl);

        }

        return $googleShortUrl;
    }

    /**
     * shortens the url using bitly
     * @return $bitlyShortUrl
     */
    public function getBitlyShortUrl()
    {
        global $post;

        if (!$accessToken = $this->settings['bitly_access_code']) {
            return $bitlyShortUrl = get_permalink();
        }

        $url = 'https://api-ssl.bitly.com/v3/shorten?access_token='.$accessToken.'&longUrl='.get_permalink();
        $bitlyShortUrl = get_permalink();

        if(! $bitlyShortUrl = get_post_meta($post->ID, 'click_and_tweet_bitly_url', true)) {

            $response = wp_remote_post( $url, array(
                'method' => 'GET',
                'headers' => array('content-type' => 'application/json'),
                'body' => json_encode(array('longUrl' => get_permalink()))
            ));

            $urlBody = json_decode(($response['body']), true);
            $urlData = $urlBody['data'];

            if(in_array('url', $urlData)) {
                $bitlyShortUrl = $urlData['url'];
                add_post_meta($post->ID, 'click_and_tweet_bitly_url', $bitlyShortUrl);
            }

        }

        return $bitlyShortUrl;
    }

    /**
     * Checks to see if the post was updated to get new permalink.
     */
    public function checkPermalinkChanges($post_ID, $postAfterUpdate, $postBeforeUpdate)
    {
        global $post;
        if($postAfterUpdate->post_name != $postBeforeUpdate->post_name) {
            delete_post_meta($post->ID, 'click_and_tweet_google_url');
        }
    }

    /**
     * Filter hook for devs to edit the returned text of tweets.
     * @param $content
     * @return [apply_filter]
     */
    public function filterContent($content)
    {
        return apply_filters('click_and_tweet_content', $content);
    }

    /**
     * Filter hook for devs to edit the call to action text
     * @param  $callToActionText
     * @return [apply_filter]    [relating to the $callToActionText]
     */
    public function filterCallToAction()
    {
        return apply_filters('click_and_tweet_cta', $this->callToActionText);
    }

}
/*
 * Checks the users php version for compatibility
 */
if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
    add_action( 'admin_notices', create_function( '', "echo '<div class=\"error\"><p>".__('Click & Tweet requires PHP 5.6 to function properly. Please upgrade PHP or deactivate Click & Tweet.', 'Click % Tweet') ."</p></div>';" ) );
    return;
} else {
  //Call the plugin
  new ClickAndTweet();
}
