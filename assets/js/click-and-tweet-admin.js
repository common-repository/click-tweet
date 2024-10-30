jQuery(function($){

    /**
     * toggles username text field
     */
    $('[name="click_and_tweet_settings[use_default_username]"]').on('change', function(){
        $('.default-username').toggleClass('hidden');
    });

    $('[name="click_and_tweet_settings[url_shorteners]"]').on('change', function(){
        if ($(this).val() == "bitly") {
            $('.bitly-shortener').show();
        } else {
            $('.bitly-shortener').hide();
        }
    });

    var myOptions = {
        defaultColor: false,
        hide: true,
        palettes: ['#55acee','','','','']
    };

    $('.tweet-color-field').wpColorPicker(myOptions);

});
