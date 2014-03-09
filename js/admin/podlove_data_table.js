(function($) {

	$.fn.podloveDataTable = function(options) {

		var $this = $(this);

		// set default options
		var settings = $.extend({}, $.fn.podloveDataTable.defaults, options);

		function fetch_object(object_id) {
			object_id = parseInt(object_id, 10);

			return $.grep(settings.dataPresets, function(object, index) {
				return parseInt(object.id, 10) === object_id;
			})[0]; // Using [0] as the returned element has multiple indexes
		}

		function add_object_row(object, entry) {
			var row = $(settings.rowTemplate).html();
			var obj = {row: row, object: object, entry: entry};

			settings.onRowLoad.call(this, obj);
			$("tbody", $this).append(obj.row);
			settings.onRowAdd.call(this, obj);
		}

		// add existing data
		$.each(settings.data, function(index, entry) {
			add_object_row(fetch_object(entry.id), entry);
		});

		// fix td width
		$("tbody td", $this).each(function(){
		    $(this).css('width', $(this).width() +'px');
		});

		if (settings.addRowHandle) {
			$(document).on('click', settings.addRowHandle, function() {
				add_object_row({}, "", "");
			});
		}

		if (settings.deleteHandle) {
			$this.on('click', settings.deleteHandle, function() {
				var tr = $(this).closest("tr");
				settings.onRowDelete.call(this, tr);
				tr.remove();
			});
		}

		if (settings.sortableHandle) {
			$("tbody", $this).sortable({
				handle: settings.sortableHandle,
				helper: function(e, tr) {
				    var $originals = tr.children();
				    var $helper = tr.clone();
				    $helper.children().each(function(index) {
				    	// Set helper cell sizes to match the original sizes
				    	$(this).width($originals.eq(index).width());
				    });
				    return $helper.css({
				    	background: '#EAEAEA'
				    });
				}
			});
		};

		return $this;
	};

	$.fn.podloveDataTable.defaults = {
		rowTemplate: "#podlove-table-template",
		deleteHandle: "",
		sortableHandle: "",
		addRowHandle: "",
		dataPresets: [],
		data: [],
		onRowLoad:   function() {},
		onRowAdd:    function() {},
		onRowDelete: function() {},
		onRowMove:   function() {}
	};

}(jQuery));