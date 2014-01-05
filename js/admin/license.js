var PODLOVE = PODLOVE || {};

(function($) {
	PODLOVE.License = function(settings) {

		var enable_license_widget = function() {
			$(settings.form_type).change(function() {
				if($(settings.form_type).val() !== "") {
					podlove_toggle_license_form($(settings.form_type).val());
					podlove_check_license_form($(settings.form_type).val());
					$(settings.status).show();
				} else {
					$(settings.form_row_other+","+settings.form_row_cc_preview).hide();
					$(settings.form_row_cc).hide();										
					$(settings.status).hide();
				}
			});			

			$(settings.container).on('click', 'a.toggle_license', function(e) {
				e.preventDefault();
				podlove_toggle_license_form($(this).data('type'));
			});

			$(settings.form_row_cc+","+settings.form_row_other).change(function(){
				podlove_check_license_form($(settings.form_type).val());
			});
			podlove_check_license_form($(settings.form_type).val());
		};

		var podlove_license_cc_get_image = function (allow_modifications, commercial_use) {
			var banner_identifier_allowed_modification, banner_identifier_commercial_use;

			switch (allow_modifications) {
				case "yes" :
					banner_identifier_allowed_modification = 1;
				break;
				case "yesbutshare" :
					banner_identifier_allowed_modification = 10;
				break;
				case "no" :
					banner_identifier_allowed_modification = 0;
				break;
				default :
					banner_identifier_allowed_modification = 1;
				break;
			}

			banner_identifier_commercial_use = (commercial_use == "no") ? "0" : "1";

			return banner_identifier_allowed_modification + "_" + banner_identifier_commercial_use;
		};

		var podlove_check_license_form = function (license_type) {
			switch (license_type) {
				case "cc" :
					var modification_url_slug, commercial_use_url_slug;
					
					$(settings.form_row_cc_preview).show();
					if($(settings.form_cc_modification).val() == "" ||
					   $(settings.form_cc_commercial_use).val() == "" ||
					   $(settings.form_cc_jurisdiction).val() == "") {
						$(settings.status).html("<i class=\"podlove-icon-remove\"></i> Additional parameters license parameters need to be set.");
						podlove_toggle_license_form("cc");
						$(settings.form_row_cc_preview).hide();
					} else {
						if($(settings.form_cc_jurisdiction).val() == "international") {
							var country = '';
							var version_and_name = settings.versions[$(settings.form_cc_jurisdiction).val()];
							var name = version_and_name.name;
						} else {
							var country = $(settings.form_cc_jurisdiction).val()+'/';
							var version_and_name = settings.versions[$(settings.form_cc_jurisdiction).val()];
							var name = settings.locales[$(settings.form_cc_jurisdiction).val()];
						}

						// Setting URL slugs to build the correct url
						switch ( $(settings.form_cc_modification).val() ) {
							case "yes" :
								modification_url_slug = "";
							break;
							case "yesbutshare" :
								modification_url_slug = "-sa";
							break;
							case "no" :
								modification_url_slug = "-nd";
							break;
						}
						switch( $(settings.form_cc_commercial_use).val() ) {
							case "yes" :
								commercial_use_url_slug = "";
							break;
							case "no" :
								commercial_use_url_slug = "-nc";
							break;
						}


						$(settings.status).html("<i class=\"podlove-icon-ok\"></i> All license parameter are set. You can <a href=\"#\" class=\"toggle_license\" data-type=\"cc\">edit</a> the license parameters.");			
						$(settings.image).html("<div class=\"podlove_cc_license\"><img src=\"" + settings.plugin_url + "/images/cc/" + podlove_license_cc_get_image($(settings.form_cc_modification).val(), $(settings.form_cc_commercial_use).val()) + ".png\" /> <p>This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by"+commercial_use_url_slug+modification_url_slug+"/"+version_and_name.version+"/"+country+"deed.en\">Creative Commons Attribution "+version_and_name.version+" "+name+" License</a>.</p></div>");
					}
				break;
				case "other" :
					if($(settings.form_other_url).val() == "" ||
					   $(settings.form_other_name).val() == "") {
						$(settings.status).html("<i class=\"podlove-icon-remove\"></i> You need to select additonal options.");
						podlove_toggle_license_form("other");
						$(settings.form_row_cc_preview).hide();
					} else {
						$(settings.status).html("<i class=\"podlove-icon-ok\"></i> All license parameter are set. You can <a href=\"#\" class=\"toggle_license\" data-type=\"other\">edit</a> the license parameters.");
						$(settings.form_row_cc_preview).show();
						$(settings.image).html("<div class=\"podlove_license\"><p>This work is licensed under the <a rel=\"license\" href=\""+$(settings.form_other_url).val()+"\">"+$(settings.form_other_name).val()+"</a> license.</p></div>");
					}
					
				break;
			}
		};

		var podlove_toggle_license_form = function (license_type) {
			switch(license_type) {
				case "cc" :
					$(settings.form_row_other).hide();
					$(settings.form_row_cc).show();
				break;
				case "other" :
					$(settings.form_row_other).show();
					$(settings.form_row_cc).hide();
				break;
			}
		};

		settings.form_row_cc = settings.form_row_cc_modification+","+settings.form_row_cc_commercial_use+","+settings.form_row_cc_jurisdiction;
		settings.form_row_other = settings.form_row_other_name+","+settings.form_row_other_url;

		enable_license_widget();
	}

}(jQuery));

