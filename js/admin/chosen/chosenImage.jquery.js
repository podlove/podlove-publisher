/*
 * Chosen jQuery plugin to add an image to the dropdown items.
 */
(function($) {
    $.fn.chosenImage = function(options) {
        return this.each(function() {
            var $select = $(this);
            var imgMap  = {};

            // 1. Retrieve img-src from data attribute and build object of image sources for each list item.
            $select.find('option').filter(function(){
                return $(this).text();
            }).each(function(i) {
                imgMap[i] = $(this).attr('data-img-src');
            });

            // 2. Execute chosen plugin and get the newly created chosen container.
            $select.chosen(options);
            var $chosen = $select.next('.chosen-container').addClass('chosenImage-container');

            // 3. Style lis with image sources.
            $chosen.on('mousedown.chosen, keyup.chosen', function(event){
                $chosen.find('.chosen-results li').each(function() {
                    var imgIndex = $(this).attr('data-option-array-index');
                    $(this).css(cssObj(imgMap[imgIndex]));
                });
            });

            // 4. Change image on chosen selected element when form changes.
            $select.change(function() {
                var imgSrc = $select.find('option:selected').attr('data-img-src') || '';
                $chosen.find('.chosen-single span').css(cssObj(imgSrc));
            });
            $select.trigger('change');

            // Utilties
            function cssObj(imgSrc) {
                var bgImg = (imgSrc) ? 'url(' + imgSrc + ')' : 'none';
                return { 'background-image' : bgImg };
            }
        });
    };
})(jQuery);
