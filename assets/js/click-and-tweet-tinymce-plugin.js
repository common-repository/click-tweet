(function($) {
    tinymce.PluginManager.add('click_and_tweet', function(ed, url) {
        var activeShortcode, editorEditable = true, shortcodeAdded = false, activeSpan,
            activeSelection;

        var defaults = {
            handle : click_and_tweet.defaultUsername || null,
            layout : click_and_tweet.defaultLayout || null,
            inlinePosition : click_and_tweet.defaultInlinePosition || null,
            cardPosition : click_and_tweet.defaultCardPosition || null
        };

        var inlineTweetPositions = {
            '' : 'Default',
            'left' : 'Left',
            'right' : 'Right'
        };

        var cardTweetPositions = {
            '' : 'Default',
            'center-top': 'Center Top',
            'center-bottom': 'Center Bottom',
            'left-center': 'Left Center',
            'left-top': 'Left Top',
            'left-bottom': 'Left Bottom',
            'right-center': 'Right Center',
            'right-top': 'Right Top',
            'right-bottom': 'Right Bottom'
        };

        /**
         * Add Button to the editor
         */
        ed.addButton('click_and_tweet_button', {
            title: 'Toggle Click & Tweet',
            image: click_and_tweet.pluginUrl + '/assets/img/twitter-logo.png',
            onclick: function() {
                toggleClickTweet();
            }
        });

        /**
         * When text is clicked, select
         */
        ed.on('click', function(e) {
            removeTweet(e);
            toggleTooltip(e);
            togglePanel(e);
            closeToolTip(e);
            updatePositionSelectOnChange(e);

            if ($(e.target).is('input[type="text"]')) {

                if($(e.target).val() === ''){
                    var tooltip = $(ed.getBody()).find('.click-and-tweet-tooltip');
                    $(e.target).focus().trigger('click');
                }
            }
        });

        ed.on('mouseout', function(event){
            var target = (typeof event.toElement !== 'undefined') ? event.toElement : event.relatedTarget;

            if(!target) {
                var tooltip = $(ed.getBody()).find('.click-and-tweet-tooltip').remove();
                activeShortcode = '';
            }
        });

        /**
         * Before setting content convert shortcode to HTML
         */
        ed.on('BeforeSetContent', function(event) {
            var spacer = '<span class="click-and-tweet-spacer"></span>';
            var result = wp.shortcode.replace('clickandtweet', event.content, function(shortcode){

                return wp.html.string({
                            tag: "span",
                            content: shortcode.content,
                            attrs : {
                                class : 'click-and-tweet-shortcode',
                                'data-handle' : shortcode.attrs.named.handle || '',
                                'data-hashtag' : shortcode.attrs.named.hashtag || '',
                                'data-related' : shortcode.attrs.named.related || '',
                                'data-layout' : shortcode.attrs.named.layout || '',
                                'data-position' : shortcode.attrs.named.position || '',
                            }
                        }) + spacer;
            });
            activeSpan = result;
            event.content = result;
        });

        ed.on('SetContent', function(event) {
            if(shortcodeAdded){
                //Trigger open here
                shortcodeAdded = false;
            }
        });

        ed.on('keydown', function(event) {
            keyDownHandeler(event);
        });

        /**
         * Upon getting content, convert HTML to shortcode.
         */
        ed.on('GetContent', function(event) {
            var content = $($.parseHTML(event.content));
            var shortcodes = $(content).find('.click-and-tweet-shortcode').each(function(i,e){
                var shortcode = wp.shortcode.string({
                    tag : 'clickandtweet',
                    single : 'false',
                    content : $(e).text(),
                    attrs : {
                        handle : $(e).data('handle') || '',
                        hashtag : $(e).data('hashtag') || '',
                        related : $(e).data('related') || '',
                        layout : $(e).data('layout') || '',
                        position : $(e).data('position') || ''
                    }
                });

                event.content = event.content.replace($(e)[0].outerHTML, shortcode);
            });
        });

        ////////////
        //Helpers //
        ////////////

        //Toggle click to tweet shorcode when selected
        function toggleClickTweet(){
            var body = ed.getBody();
            var text = ed.selection.getContent();

            var newText = '';
            if (text.match(/click-and-tweet-shortcode/g)) {
                newText = $(text).contents().filter(function() {
                    return this.nodeType == Node.TEXT_NODE;
                }).text();
            }else{
                if((newText = newText.match(/\[clickandtweet.*?\](.*?)\[\/clickandtweet\]/))){
                    newText = newText[1];
                }else {
                    newText = wp.shortcode.string({
                        tag : 'clickandtweet',
                        single : 'false',
                        content : text
                    });
                    shortcodeAdded = true;
                }
            }

            if (!text.length) return;

            //Replace the selected text, replace all content and set caret.
            ed.selection.setContent(newText);
            var bookmark = ed.selection.getBookmark();
            ed.setContent($(body).html());
            ed.selection.moveToBookmark(bookmark);
        }
        /**
         * The template for the tooltip
         * @return string
         */
        function tweetToolTip()
        {
            var tooltip = '<div class="click-and-tweet-tooltip mceNonEditable" contenteditable="false">' +
                            '<span class="close">&times;</span>' +
                            '<div class="panel default">' +
                                '<div>' +
                                    '<label class="blue">@</label>' +
                                    '<input type="text" name="handle" class="click-and-tweet-username mceEditable" contenteditable="true" placeholder="@username">' +
                                '</div>' +
                                '<div>' +
                                    '<label class="blue">#</label>' +
                                    '<input type="text" name="hashtag" class="click-and-tweet-hash mceEditable" contenteditable="true" placeholder="#hashtag">' +
                                '</div>' +
                                '<div>' +
                                    '<label class="blue">@</label>' +
                                    '<input type="text" name="related" class="click-and-tweet-related mceEditable" contenteditable="true" placeholder="@relatedUsername">' +
                                '</div>' +
                                '<a class="remove" title="remove">Remove</a>' +
                                '<a class="toggle-panel more-link" data-panel="more" title="More">&middot;&middot;&middot;</a>' +
                            '</div>' +
                            '<div class="panel more" style="display: none;">' +
                                '<div>' +
                                    '<label>Layout</label>' +
                                    '<select class="click-to-tweet-layout mceEditable" contenteditable="true" name="layout">' +
                                        '<option value="inline">Inline</option>' +
                                        '<option value="card">Card</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div>' +
                                    '<label>Position</label>' +
                                    '<select class="click-to-tweet-layout-position mceEditable" contenteditable="true" name="position"></select>' +
                                '</div>' +
                                '<a class="toggle-panel back-link" data-panel="default" title="Back">&lsaquo;<small>Back</small></a>' +
                            '</div>' +
                          '</div>';
            return tooltip;
        }

        /**
         * Toggle the shotcode tooltip
         */
        function toggleTooltip(e)
        {
            if ($(e.target)[0].className == 'click-and-tweet-shortcode') {
                var tooltip = $(ed.getBody()).find('.click-and-tweet-tooltip');

                if(activeShortcode !== $(e.target)[0]){
                    tooltip.remove();

                    activeShortcode = $(e.target)[0];

                    toolip = tweetToolTip();
                    $(ed.getBody()).append(toolip, updateShortcode($(e.target)));
                    var positionedTooltip = toolTipPosition(e);
                    toolTipHover(positionedTooltip);
                    ed.selection.select($(e.target)[0]);
                } else if(tooltip.length){
                    tooltip.remove();
                    activeShortcode = '';
                }
            }
        }

        /**
         * Close the tooltip
         */
        function closeToolTip(e, force)
        {
            if ($(e.target)[0].className == 'close' || force) {
                $(e.target).closest('.click-and-tweet-tooltip').remove();
                activeShortcode = '';
                toggleEditable();
            }
        }

        /**
         * Close the tooltip
         */
        function togglePanel(e)
        {
            if ($($(e.target)[0]).hasClass('toggle-panel') || $($(e.target)[0]).parent().hasClass('toggle-panel')) {
                var target = ($($(e.target)[0]).parent().hasClass('toggle-panel')) ? $($(e.target)[0]).parent() : $($(e.target)[0]);
                var panel = target.data('panel');
                target.closest('.click-and-tweet-tooltip').find('.panel').hide();
                target.closest('.click-and-tweet-tooltip').find('.panel.' + panel).show();
                return false;
            }
        }

        /**
         * Get the toolip classes based on the element position
         * @param  object e Event object
         * @return string  class
         */
        function toolTipPosition(e)
        {
            var tooltip = $(ed.getBody()).find('.click-and-tweet-tooltip');

            var parentWidth = e.target.offsetParent.clientWidth,
                parentHeight = e.target.offsetParent.clientHeight,
                targetWidth = e.target.offsetWidth,
                targetHeight = e.target.offsetHeight,
                targetXOffset = e.target.offsetLeft,
                targetYOffset = e.target.offsetTop,
                minXOffset = 200,
                minYOffset = 100,
                maxXOffset = parentWidth - minXOffset,
                maxYOffset = parentHeight - minYOffset,
                xOffset = 0,
                yOffset = 0;

            //Determine the x position of tooltip
            switch (true) {
                case targetXOffset <= minXOffset && targetWidth <= minXOffset:
                    xClassName = 'left';
                    break;
                case targetXOffset >= maxXOffset :
                    xClassName = 'right';
                    break;
                default:
                    xClassName = 'center';
            }

            //Determine the y position of the tooltip
            switch (true) {
                case targetYOffset >= maxYOffset && maxYOffset > 0 :
                    yClassName = 'top';
                    break;
                case parentHeight - targetYOffset <= maxYOffset && targetYOffset <= tooltip.outerHeight():
                    yClassName = 'top';
                    break;
                default:
                    yClassName = 'bottom';
            }

            //Add classnames to the tooltip
            var classNames = xClassName +' '+ yClassName;

            tooltip.addClass(classNames);

            //Determine the Y coordinate of the tooltip
            switch (yClassName) {
                case 'top' :
                    yOffset = (targetYOffset >= tooltip.outerHeight()) ? targetYOffset - tooltip.outerHeight() : tooltip.outerHeight() - targetYOffset;
                    break;
                default:
                    yOffset = (targetYOffset + targetHeight) - 25;
            }

            //Determine the X coordinate of the tooltip
            switch (xClassName) {
                case 'left' :
                    xOffset = targetXOffset;
                    break;
                case 'right' :
                    xOffset = targetXOffset - tooltip.width();
                    break;
                default:
                    if(targetWidth > maxXOffset ){
                        xOffset = (targetWidth / 2) - (tooltip.width() / 2);
                    }else{
                        xOffset = targetXOffset + (targetWidth / 2) - (tooltip.width() / 2);
                    }

            }

            tooltip.css({
                'left': xOffset + 'px',
                'top': yOffset + 'px'
            });

            return tooltip;
        }

        /**
         * Helper for the tooltip when hovering
         * @param  object tooltip
         */
        function toolTipHover(tooltip)
        {
            $(tooltip).hover(function(){
                toggleEditable();
            }, function(){
                toggleEditable();
            });
        }

        /**
         * Update shortcode on type in tooltip fields
         */
        function updateShortcode(element)
        {
            setTimeout(function () {
                var shortcode = element;
                var inputs = $('.click-and-tweet-tooltip', ed.getBody()).find('input[type="text"], select');

                inputs.each(function(i,e){
                    setInputFields(e, element);
                });

                inputs.on('keyup change', function(){
                    shortcode.attr('data-' + $(this).attr('name'), $(this).val());
                });
            }, 10);
        }

        /**
         * Set inputs with set data
         * @param  object e
         * @param  object parent
         */
        function setInputFields(e, parent)
        {
            if( typeof parent[0].dataset[$(e).attr('name')] !== 'undefined'){
                var value = parent[0].dataset[$(e).attr('name')] || '';
                if(value) $(e).val(value);
            }

            if($(e).attr('name') == 'layout'){
                updatePositionSelect(e);
            }

            if($(e).attr('name') == 'handle' && defaults.handle) $(e).attr('placeholder', defaults.handle);
        }

        /**
         * Keydown handler for the plugin
         * @param  object ed   Editor
         * @param  object event Event
         */
        function keyDownHandeler(event)
        {
            var spacer = $(ed.getBody()).find('.click-and-tweet-spacer');

            if(spacer.length){
                ed.selection.select(spacer.get(0));
                if(ed.selection.getRng().startContainer.nextSibling === null){
                    ed.selection.setContent('&nbsp;');
                }else{
                    ed.selection.setContent(' ');
                }
            }
        }

        /**
         * Disable the editior from being editable
         */
        function toggleEditable()
        {
            if(editorEditable) {
                $(ed.getBody()).attr({'contenteditable': false});
                editorEditable = false;
            } else {
                $(ed.getBody()).attr({'contenteditable': true});
                editorEditable = true;
            }
        }

        /**
         * Update the position select options
         */
        function updatePositionSelect(e)
        {
            e = e.currentTarget || e;
            var options = ($(e).val() == 'card') ? cardTweetPositions : inlineTweetPositions;
            var layout = $('.click-to-tweet-layout', ed.getBody());
            var layoutPosition = $('.click-to-tweet-layout-position', ed.getBody());

            layoutPosition.empty();
            $.each(options, function(value,key) {
              layoutPosition.append($("<option></option>").attr("value", value).text(key));
            });
        }

        /**
         * Update the position select options when the layout select changes
         */
        function updatePositionSelectOnChange(e)
        {
            if(e.target.className == 'click-to-tweet-layout'){
                $('.click-to-tweet-layout', ed.getBody()).on('change', function(e){
                    updatePositionSelect(e);
                });
            }
        }

        /**
         * Remove a tweet from the editor
         */
        function removeTweet(e)
        {
            if ($(e.target)[0].className == 'remove' && activeShortcode) {
                var body = ed.getBody();
                var shortcode = $(body).find(activeShortcode);
                var newText = '';
                newText = $(shortcode).contents().filter(function() {
                    return this.nodeType == Node.TEXT_NODE;
                }).text();

                shortcode.replaceWith(newText);

                closeToolTip(e, true);

            }
        }
    });

})(jQuery);
