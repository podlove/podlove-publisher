var PODLOVE = PODLOVE || {};

(function($) {

	$(document).ready(function(){
		$("#add_contributors_submit").on("click", function(e) {
			e.preventDefault();

			var $add_contributors_input = $("#add_contributors_input");
			var new_contributor = $add_contributors_input.val();

			if (!new_contributor.length) return false;

			PODLOVE.add_contributor({
				slug: new_contributor,
				id: 0,
				avatar: null,
				name: new_contributor
			});

			return false;
		});

		$("#contributors").on("click", ".contributor a.ntdelbutton", function(e) {
			e.preventDefault();

			var $contributor = $(this).closest(".contributor");

			// actually remove contributor
			var slug = $contributor.data('termSlug');

			var $data_field = $("#tax-input-podlove-contributors");
			var contributors = $data_field.val().split(",");

			// remove by slug
			contributors.splice(contributors.indexOf(slug),1);
			$data_field.val(contributors.join(","));

			// visurally remove contributor
			$contributor.remove();

			return false;
		});

		$("#add_contributors_input").autocomplete({
			minLength: 0,
			source: PODLOVE.people,
			focus: function(event, ui) {
				$("#add_contributors_input").val(ui.item.label);

				return false;
			},
			select: function(event, ui) {

				PODLOVE.add_contributor({
					slug: ui.item.value,
					id: ui.item.id,
					avatar: ui.item.avatar,
					name: ui.item.label
				});

				return false;
			}
		}).data('autocomplete')._renderItem = function(ul, item) {

			var template = PODLOVE.contributor_template({
				avatar: item.avatar,
				name: item.label,
				display_delete: false,
				display_data: false,
				"class": "contributor autocomplete"
			});

			return $( "<li></li>" )
			    .data( "item.autocomplete", item )
			    .append( "<a>" + template + "</a>" )
			    .appendTo( ul );
		};

	});

	PODLOVE.add_contributor = function(options) {

		if (!options.avatar) {
			options.avatar = 'http://www.gravatar.com/avatar?d=mm';
		}

		// visually add contributor
		$("#add_contributors_input").val('');
		$("#contributors").append(PODLOVE.contributor_template(options));

		// actually add contributor
		var $data_field = $("#tax-input-podlove-contributors");
		var contributors = $data_field.val();

		if (!contributors.length) {
			contributors = options.slug;
		} else {
			contributors = contributors + "," + options.slug;	
		}
		
		$data_field.val(contributors);
	}

	PODLOVE.contributor_template = function(options) {

		var defaults = {
			"display_data": true,
			"display_delete": true,
			"class": "contributor"
		};
		var options = $.extend({}, defaults, options); 

		var tpl = '';
		if (options.display_data) {
			tpl += '<div class="' + options["class"] + '" data-term-slug="' + options.slug + '" data-term-id="' + options.id + '">';
		} else {
			tpl += '<div class="' + options["class"] + '">';
		}
		tpl += '	<span>';
		if (options.display_delete) {
			tpl += '		<a href="#" class="ntdelbutton">x</a>';
		}
		tpl += '		<div class="avatar">';
		tpl += '			<img src="' + options.avatar + '" class="avatar avatar-24 photo" height="24" width="24">';
		tpl += '		</div>';
		tpl += '		<div class="name">';
		tpl += '			' + options.name;
		tpl += '		</div>';
		tpl += '	</span>';
		tpl += '</div>';

		return tpl;
	}

}(jQuery));
