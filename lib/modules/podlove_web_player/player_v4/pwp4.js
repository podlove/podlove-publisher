jQuery(function () {
  jQuery(".pwp4-wrapper").each(function () {
    var that = jQuery(this);
    var id = that.attr("id");
    var config = that.data("episode");
    
    if (typeof podlovePlayer === "function") {
      podlovePlayer("#" + id, config);
    }
  })
});
