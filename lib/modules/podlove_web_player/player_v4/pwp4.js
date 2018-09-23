jQuery(function () {
  jQuery(".pwp4-wrapper").each(function () {
    var that = jQuery(this);
    var id = that.attr("id");

    podlovePlayer("#" + id, window[id]);
  })
});
