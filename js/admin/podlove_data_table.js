(function($) {

	/**
	 * Podlove Data Table.
	 *
	 * jQuery plugin for dynamic data tables.
	 *
	 * Usage:
	 * 	$(selector).podloveDataTable(options);
	 *
	 * Options:
	 * 	rowTemplate:    selector for html row template, e.g."#podlove-table-template"
	 *	deleteHandle:   selector for row delete element
	 *	sortableHandle: selector for row move/sort element
	 *	addRowHandle:   selector for "add row" element
	 *	dataPresets:    list of objects, must have an id attribute, e.g. [{id: 1, title: "foo"}]
	 *	data:           list of objects, representing existing rows in the table, must have an id attribute
	 *	onRowLoad:      callback function. called when rowTemplate is loaded
	 *	onRowAdd:       callback function. called after rowTemplate was added to the DOM
	 *	onRowDelete:    callback function. called when a row was deleted from the DOM
	 *	onRowMove:      callback function. called when the position of a row has changed
	 */
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

		function add_object_row(object_index, object, entry) {
			var row = $(settings.rowTemplate).html();
			var obj = {row: row, object: object, entry: entry};

			settings.onRowLoad.call(this, obj);
			$("tbody", $this).append($(obj.row).data('object-id', object_index));
			settings.onRowAdd.call(this, obj);
		}

		// add existing data
		$.each(settings.data, function(index, entry) {
			add_object_row(index, fetch_object(entry.id), entry);
		});

		// fix td width
		$("tbody td", $this).each(function(){
		    $(this).css('width', $(this).width() +'px');
		});

		if (settings.addRowHandle) {
			$(document).on('click', settings.addRowHandle, function() {
				add_object_row(0, {}, "", "");
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
				},
				update: settings.onRowMove
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