jQuery(document).ready(function($) {
    if (PODLOVE.override_post_title && PODLOVE.override_post_title.enabled) {
        podlove_init_title_override();
    }

    var $titlediv, $titlewrap, $titleinput, $numberinput, $itunestitleinput;

    function podlove_init_title_override() {
        $titlediv = $("#titlediv");
        $titlewrap = $("#titlewrap");
        $titleinput = $("input[name='post_title']", $titlewrap);
        $numberinput = $("#_podlove_meta_number");
        $itunestitleinput = $("#_podlove_meta_title");

        $titleinput.attr('readonly', true);
        $titleinput.css('background-color', '#eee');
        $("#title-prompt-text").hide();

        podlove_update_episode_title();

        $numberinput.on('keyup change', podlove_update_episode_title);
        $itunestitleinput.on('keyup change', podlove_update_episode_title);
    }

    function podlove_update_episode_title() {
        var template = PODLOVE.override_post_title.template;

        var mnemonic = PODLOVE.override_post_title.mnemonic;
        var episode_number = $numberinput.val();
        var episode_title = $itunestitleinput.val();

        var padLeft = function(nr, n, str){
            if (String(nr).length < n) {
                return Array(n-String(nr).length+1).join(str||'0')+nr;
            } else {
                return nr;
            }
        }

        var title = template;
        if (episode_title) {
            title = title.replace('%mnemonic%', mnemonic);
            title = title.replace('%episode_number%', padLeft(episode_number, PODLOVE.override_post_title.episode_padding, '0'));
            title = title.replace('%season_number%', PODLOVE.override_post_title.season_number);
            title = title.replace('%episode_title%', episode_title);
            $titleinput.val(title);
        } else {
            $titleinput.attr('placeholder', PODLOVE.override_post_title.placeholder)
        }

        $("#titlewrap input").trigger('titleHasChanged');
    }
});
