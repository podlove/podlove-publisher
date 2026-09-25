var PODLOVE = PODLOVE || {}

/**
 * Handles all logic in Create/Edit Episode screen.
 */
;(function ($) {
  PODLOVE.Episode = function (container) {
    var o = {}

    o.slug_field = container.find('[name*=slug]')

    $('#_podlove_meta_subtitle').count_characters({
      limit: 255,
      title: wp.i18n.__('recommended maximum length: 255', 'podlove-podcasting-plugin-for-wordpress'),
    })
    $('#_podlove_meta_summary').count_characters({
      limit: 4000,
      title: wp.i18n.__('recommended maximum length: 4000', 'podlove-podcasting-plugin-for-wordpress'),
    })

    $(document).on('click', '.subtitle_warning .close', function () {
      $(this).closest('.subtitle_warning').remove()
    })

    $('#_podlove_meta_subtitle').keydown(function (e) {
      // forbid return key
      if (e.keyCode == 13) {
        e.preventDefault()

        if (!$('.subtitle_warning').length) {
          $(this).after(
            jQuery('<span class="subtitle_warning">').text(wp.i18n.__('The subtitle has to be a single line.', 'podlove-podcasting-plugin-for-wordpress') + ' ').append(jQuery('<span class="close">').text(wp.i18n.__('(hide)', 'podlove-podcasting-plugin-for-wordpress')))
          )
        }

        return false
      }
    })

    var typewatch = (function () {
      var timer = 0
      return function (callback, ms) {
        clearTimeout(timer)
        timer = setTimeout(callback, ms)
      }
    })()

    $.subscribe('/auphonic/production/status/results_imported', function (e, production) {
      o.slug_field.trigger('slugHasChanged').data('auto-update', false)
    })

    var title_input = $('#titlewrap input')

    title_input
      .on('blur', function () {
        title_input.trigger('titleHasChanged')
      })
      .on('keyup', function () {
        typewatch(function () {
          title_input.trigger('titleHasChanged')
        }, 500)
      })
      .on('titleHasChanged', function () {
        var title = $(this).val()

        // update episode title
        $('#_podlove_meta_title').attr('placeholder', title)
      })
      .trigger('titleHasChanged')

    o.slug_field
      .on('blur', function () {
        o.slug_field.trigger('slugHasChanged')
      })
      .on('keyup', function () {
        typewatch(function () {
          o.slug_field.trigger('slugHasChanged')
        }, 500)
      })

    return o
  }
})(jQuery)
