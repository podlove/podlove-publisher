/* Chosen v1.4.2 | (c) 2011-2015 by Harvest | MIT License, https://github.com/harvesthq/chosen/blob/master/LICENSE.md */
(function(){var a,AbstractChosen,Chosen,SelectParser,b,c={}.hasOwnProperty,d=function(a,b){function d(){this.constructor=a}for(var e in b)c.call(b,e)&&(a[e]=b[e]);return d.prototype=b.prototype,a.prototype=new d,a.__super__=b.prototype,a};SelectParser=function(){function SelectParser(){this.options_index=0,this.parsed=[]}return SelectParser.prototype.add_node=function(a){return"OPTGROUP"===a.nodeName.toUpperCase()?this.add_group(a):this.add_option(a)},SelectParser.prototype.add_group=function(a){var b,c,d,e,f,g;for(b=this.parsed.length,this.parsed.push({array_index:b,group:!0,label:this.escapeExpression(a.label),title:a.title?a.title:void 0,children:0,disabled:a.disabled,classes:a.className}),f=a.childNodes,g=[],d=0,e=f.length;e>d;d++)c=f[d],g.push(this.add_option(c,b,a.disabled));return g},SelectParser.prototype.add_option=function(a,b,c){return"OPTION"===a.nodeName.toUpperCase()?(""!==a.text?(null!=b&&(this.parsed[b].children+=1),this.parsed.push({array_index:this.parsed.length,options_index:this.options_index,value:a.value,text:a.text,html:a.innerHTML,title:a.title?a.title:void 0,selected:a.selected,disabled:c===!0?c:a.disabled,group_array_index:b,group_label:null!=b?this.parsed[b].label:null,classes:a.className,style:a.style.cssText})):this.parsed.push({array_index:this.parsed.length,options_index:this.options_index,empty:!0}),this.options_index+=1):void 0},SelectParser.prototype.escapeExpression=function(a){var b,c;return null==a||a===!1?"":/[\&\<\>\"\'\`]/.test(a)?(b={"<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","`":"&#x60;"},c=/&(?!\w+;)|[\<\>\"\'\`]/g,a.replace(c,function(a){return b[a]||"&amp;"})):a},SelectParser}(),SelectParser.select_to_array=function(a){var b,c,d,e,f;for(c=new SelectParser,f=a.childNodes,d=0,e=f.length;e>d;d++)b=f[d],c.add_node(b);return c.parsed},AbstractChosen=function(){function AbstractChosen(a,b){this.form_field=a,this.options=null!=b?b:{},AbstractChosen.browser_is_supported()&&(this.is_multiple=this.form_field.multiple,this.set_default_text(),this.set_default_values(),this.setup(),this.set_up_html(),this.register_observers(),this.on_ready())}return AbstractChosen.prototype.set_default_values=function(){var a=this;return this.click_test_action=function(b){return a.test_active_click(b)},this.activate_action=function(b){return a.activate_field(b)},this.active_field=!1,this.mouse_on_container=!1,this.results_showing=!1,this.result_highlighted=null,this.allow_single_deselect=null!=this.options.allow_single_deselect&&null!=this.form_field.options[0]&&""===this.form_field.options[0].text?this.options.allow_single_deselect:!1,this.disable_search_threshold=this.options.disable_search_threshold||0,this.disable_search=this.options.disable_search||!1,this.enable_split_word_search=null!=this.options.enable_split_word_search?this.options.enable_split_word_search:!0,this.group_search=null!=this.options.group_search?this.options.group_search:!0,this.search_contains=this.options.search_contains||!1,this.single_backstroke_delete=null!=this.options.single_backstroke_delete?this.options.single_backstroke_delete:!0,this.max_selected_options=this.options.max_selected_options||1/0,this.inherit_select_classes=this.options.inherit_select_classes||!1,this.display_selected_options=null!=this.options.display_selected_options?this.options.display_selected_options:!0,this.display_disabled_options=null!=this.options.display_disabled_options?this.options.display_disabled_options:!0,this.include_group_label_in_selected=this.options.include_group_label_in_selected||!1},AbstractChosen.prototype.set_default_text=function(){return this.default_text=this.form_field.getAttribute("data-placeholder")?this.form_field.getAttribute("data-placeholder"):this.is_multiple?this.options.placeholder_text_multiple||this.options.placeholder_text||AbstractChosen.default_multiple_text:this.options.placeholder_text_single||this.options.placeholder_text||AbstractChosen.default_single_text,this.results_none_found=this.form_field.getAttribute("data-no_results_text")||this.options.no_results_text||AbstractChosen.default_no_result_text},AbstractChosen.prototype.choice_label=function(a){return this.include_group_label_in_selected&&null!=a.group_label?"<b class='group-name'>"+a.group_label+"</b>"+a.html:a.html},AbstractChosen.prototype.mouse_enter=function(){return this.mouse_on_container=!0},AbstractChosen.prototype.mouse_leave=function(){return this.mouse_on_container=!1},AbstractChosen.prototype.input_focus=function(){var a=this;if(this.is_multiple){if(!this.active_field)return setTimeout(function(){return a.container_mousedown()},50)}else if(!this.active_field)return this.activate_field()},AbstractChosen.prototype.input_blur=function(){var a=this;return this.mouse_on_container?void 0:(this.active_field=!1,setTimeout(function(){return a.blur_test()},100))},AbstractChosen.prototype.results_option_build=function(a){var b,c,d,e,f;for(b="",f=this.results_data,d=0,e=f.length;e>d;d++)c=f[d],b+=c.group?this.result_add_group(c):this.result_add_option(c),(null!=a?a.first:void 0)&&(c.selected&&this.is_multiple?this.choice_build(c):c.selected&&!this.is_multiple&&this.single_set_selected_text(this.choice_label(c)));return b},AbstractChosen.prototype.result_add_option=function(a){var b,c;return a.search_match?this.include_option_in_results(a)?(b=[],a.disabled||a.selected&&this.is_multiple||b.push("active-result"),!a.disabled||a.selected&&this.is_multiple||b.push("disabled-result"),a.selected&&b.push("result-selected"),null!=a.group_array_index&&b.push("group-option"),""!==a.classes&&b.push(a.classes),c=document.createElement("li"),c.className=b.join(" "),c.style.cssText=a.style,c.setAttribute("data-option-array-index",a.array_index),c.innerHTML=a.search_text,a.title&&(c.title=a.title),this.outerHTML(c)):"":""},AbstractChosen.prototype.result_add_group=function(a){var b,c;return a.search_match||a.group_match?a.active_options>0?(b=[],b.push("group-result"),a.classes&&b.push(a.classes),c=document.createElement("li"),c.className=b.join(" "),c.innerHTML=a.search_text,a.title&&(c.title=a.title),this.outerHTML(c)):"":""},AbstractChosen.prototype.results_update_field=function(){return this.set_default_text(),this.is_multiple||this.results_reset_cleanup(),this.result_clear_highlight(),this.results_build(),this.results_showing?this.winnow_results():void 0},AbstractChosen.prototype.reset_single_select_options=function(){var a,b,c,d,e;for(d=this.results_data,e=[],b=0,c=d.length;c>b;b++)a=d[b],a.selected?e.push(a.selected=!1):e.push(void 0);return e},AbstractChosen.prototype.results_toggle=function(){return this.results_showing?this.results_hide():this.results_show()},AbstractChosen.prototype.results_search=function(){return this.results_showing?this.winnow_results():this.results_show()},AbstractChosen.prototype.winnow_results=function(){var a,b,c,d,e,f,g,h,i,j,k,l;for(this.no_results_clear(),d=0,f=this.get_search_text(),a=f.replace(/[-[\]{}()*+?.,\\^$|#\s]/g,"\\$&"),i=new RegExp(a,"i"),c=this.get_search_regex(a),l=this.results_data,j=0,k=l.length;k>j;j++)b=l[j],b.search_match=!1,e=null,this.include_option_in_results(b)&&(b.group&&(b.group_match=!1,b.active_options=0),null!=b.group_array_index&&this.results_data[b.group_array_index]&&(e=this.results_data[b.group_array_index],0===e.active_options&&e.search_match&&(d+=1),e.active_options+=1),b.search_text=b.group?b.label:b.html,(!b.group||this.group_search)&&(b.search_match=this.search_string_match(b.search_text,c),b.search_match&&!b.group&&(d+=1),b.search_match?(f.length&&(g=b.search_text.search(i),h=b.search_text.substr(0,g+f.length)+"</em>"+b.search_text.substr(g+f.length),b.search_text=h.substr(0,g)+"<em>"+h.substr(g)),null!=e&&(e.group_match=!0)):null!=b.group_array_index&&this.results_data[b.group_array_index].search_match&&(b.search_match=!0)));return this.result_clear_highlight(),1>d&&f.length?(this.update_results_content(""),this.no_results(f)):(this.update_results_content(this.results_option_build()),this.winnow_results_set_highlight())},AbstractChosen.prototype.get_search_regex=function(a){var b;return b=this.search_contains?"":"^",new RegExp(b+a,"i")},AbstractChosen.prototype.search_string_match=function(a,b){var c,d,e,f;if(b.test(a))return!0;if(this.enable_split_word_search&&(a.indexOf(" ")>=0||0===a.indexOf("["))&&(d=a.replace(/\[|\]/g,"").split(" "),d.length))for(e=0,f=d.length;f>e;e++)if(c=d[e],b.test(c))return!0},AbstractChosen.prototype.choices_count=function(){var a,b,c,d;if(null!=this.selected_option_count)return this.selected_option_count;for(this.selected_option_count=0,d=this.form_field.options,b=0,c=d.length;c>b;b++)a=d[b],a.selected&&(this.selected_option_count+=1);return this.selected_option_count},AbstractChosen.prototype.choices_click=function(a){return a.preventDefault(),this.results_showing||this.is_disabled?void 0:this.results_show()},AbstractChosen.prototype.keyup_checker=function(a){var b,c;switch(b=null!=(c=a.which)?c:a.keyCode,this.search_field_scale(),b){case 8:if(this.is_multiple&&this.backstroke_length<1&&this.choices_count()>0)return this.keydown_backstroke();if(!this.pending_backstroke)return this.result_clear_highlight(),this.results_search();break;case 13:if(a.preventDefault(),this.results_showing)return this.result_select(a);break;case 27:return this.results_showing&&this.results_hide(),!0;case 9:case 38:case 40:case 16:case 91:case 17:break;default:return this.results_search()}},AbstractChosen.prototype.clipboard_event_checker=function(){var a=this;return setTimeout(function(){return a.results_search()},50)},AbstractChosen.prototype.container_width=function(){return null!=this.options.width?this.options.width:""+this.form_field.offsetWidth+"px"},AbstractChosen.prototype.include_option_in_results=function(a){return this.is_multiple&&!this.display_selected_options&&a.selected?!1:!this.display_disabled_options&&a.disabled?!1:a.empty?!1:!0},AbstractChosen.prototype.search_results_touchstart=function(a){return this.touch_started=!0,this.search_results_mouseover(a)},AbstractChosen.prototype.search_results_touchmove=function(a){return this.touch_started=!1,this.search_results_mouseout(a)},AbstractChosen.prototype.search_results_touchend=function(a){return this.touch_started?this.search_results_mouseup(a):void 0},AbstractChosen.prototype.outerHTML=function(a){var b;return a.outerHTML?a.outerHTML:(b=document.createElement("div"),b.appendChild(a),b.innerHTML)},AbstractChosen.browser_is_supported=function(){return"Microsoft Internet Explorer"===window.navigator.appName?document.documentMode>=8:/iP(od|hone)/i.test(window.navigator.userAgent)?!1:/Android/i.test(window.navigator.userAgent)&&/Mobile/i.test(window.navigator.userAgent)?!1:!0},AbstractChosen.default_multiple_text="Select Some Options",AbstractChosen.default_single_text="Select an Option",AbstractChosen.default_no_result_text="No results match",AbstractChosen}(),a=jQuery,a.fn.extend({chosen:function(b){return AbstractChosen.browser_is_supported()?this.each(function(){var c,d;c=a(this),d=c.data("chosen"),"destroy"===b&&d instanceof Chosen?d.destroy():d instanceof Chosen||c.data("chosen",new Chosen(this,b))}):this}}),Chosen=function(c){function Chosen(){return b=Chosen.__super__.constructor.apply(this,arguments)}return d(Chosen,c),Chosen.prototype.setup=function(){return this.form_field_jq=a(this.form_field),this.current_selectedIndex=this.form_field.selectedIndex,this.is_rtl=this.form_field_jq.hasClass("chosen-rtl")},Chosen.prototype.set_up_html=function(){var b,c;return b=["chosen-container"],b.push("chosen-container-"+(this.is_multiple?"multi":"single")),this.inherit_select_classes&&this.form_field.className&&b.push(this.form_field.className),this.is_rtl&&b.push("chosen-rtl"),c={"class":b.join(" "),style:"width: "+this.container_width()+";",title:this.form_field.title},this.form_field.id.length&&(c.id=this.form_field.id.replace(/[^\w]/g,"_")+"_chosen"),this.container=a("<div />",c),this.is_multiple?this.container.html('<ul class="chosen-choices"><li class="search-field"><input type="text" value="'+this.default_text+'" class="default" autocomplete="off" style="width:25px;" /></li></ul><div class="chosen-drop"><ul class="chosen-results"></ul></div>'):this.container.html('<a class="chosen-single chosen-default" tabindex="-1"><span>'+this.default_text+'</span><div><b></b></div></a><div class="chosen-drop"><div class="chosen-search"><input type="text" autocomplete="off" /></div><ul class="chosen-results"></ul></div>'),this.form_field_jq.hide().after(this.container),this.dropdown=this.container.find("div.chosen-drop").first(),this.search_field=this.container.find("input").first(),this.search_results=this.container.find("ul.chosen-results").first(),this.search_field_scale(),this.search_no_results=this.container.find("li.no-results").first(),this.is_multiple?(this.search_choices=this.container.find("ul.chosen-choices").first(),this.search_container=this.container.find("li.search-field").first()):(this.search_container=this.container.find("div.chosen-search").first(),this.selected_item=this.container.find(".chosen-single").first()),this.results_build(),this.set_tab_index(),this.set_label_behavior()},Chosen.prototype.on_ready=function(){return this.form_field_jq.trigger("chosen:ready",{chosen:this})},Chosen.prototype.register_observers=function(){var a=this;return this.container.bind("touchstart.chosen",function(b){return a.container_mousedown(b),b.preventDefault()}),this.container.bind("touchend.chosen",function(b){return a.container_mouseup(b),b.preventDefault()}),this.container.bind("mousedown.chosen",function(b){a.container_mousedown(b)}),this.container.bind("mouseup.chosen",function(b){a.container_mouseup(b)}),this.container.bind("mouseenter.chosen",function(b){a.mouse_enter(b)}),this.container.bind("mouseleave.chosen",function(b){a.mouse_leave(b)}),this.search_results.bind("mouseup.chosen",function(b){a.search_results_mouseup(b)}),this.search_results.bind("mouseover.chosen",function(b){a.search_results_mouseover(b)}),this.search_results.bind("mouseout.chosen",function(b){a.search_results_mouseout(b)}),this.search_results.bind("mousewheel.chosen DOMMouseScroll.chosen",function(b){a.search_results_mousewheel(b)}),this.search_results.bind("touchstart.chosen",function(b){a.search_results_touchstart(b)}),this.search_results.bind("touchmove.chosen",function(b){a.search_results_touchmove(b)}),this.search_results.bind("touchend.chosen",function(b){a.search_results_touchend(b)}),this.form_field_jq.bind("chosen:updated.chosen",function(b){a.results_update_field(b)}),this.form_field_jq.bind("chosen:activate.chosen",function(b){a.activate_field(b)}),this.form_field_jq.bind("chosen:open.chosen",function(b){a.container_mousedown(b)}),this.form_field_jq.bind("chosen:close.chosen",function(b){a.input_blur(b)}),this.search_field.bind("blur.chosen",function(b){a.input_blur(b)}),this.search_field.bind("keyup.chosen",function(b){a.keyup_checker(b)}),this.search_field.bind("keydown.chosen",function(b){a.keydown_checker(b)}),this.search_field.bind("focus.chosen",function(b){a.input_focus(b)}),this.search_field.bind("cut.chosen",function(b){a.clipboard_event_checker(b)}),this.search_field.bind("paste.chosen",function(b){a.clipboard_event_checker(b)}),this.is_multiple?this.search_choices.bind("click.chosen",function(b){a.choices_click(b)}):this.container.bind("click.chosen",function(a){a.preventDefault()})},Chosen.prototype.destroy=function(){return a(this.container[0].ownerDocument).unbind("click.chosen",this.click_test_action),this.search_field[0].tabIndex&&(this.form_field_jq[0].tabIndex=this.search_field[0].tabIndex),this.container.remove(),this.form_field_jq.removeData("chosen"),this.form_field_jq.show()},Chosen.prototype.search_field_disabled=function(){return this.is_disabled=this.form_field_jq[0].disabled,this.is_disabled?(this.container.addClass("chosen-disabled"),this.search_field[0].disabled=!0,this.is_multiple||this.selected_item.unbind("focus.chosen",this.activate_action),this.close_field()):(this.container.removeClass("chosen-disabled"),this.search_field[0].disabled=!1,this.is_multiple?void 0:this.selected_item.bind("focus.chosen",this.activate_action))},Chosen.prototype.container_mousedown=function(b){return this.is_disabled||(b&&"mousedown"===b.type&&!this.results_showing&&b.preventDefault(),null!=b&&a(b.target).hasClass("search-choice-close"))?void 0:(this.active_field?this.is_multiple||!b||a(b.target)[0]!==this.selected_item[0]&&!a(b.target).parents("a.chosen-single").length||(b.preventDefault(),this.results_toggle()):(this.is_multiple&&this.search_field.val(""),a(this.container[0].ownerDocument).bind("click.chosen",this.click_test_action),this.results_show()),this.activate_field())},Chosen.prototype.container_mouseup=function(a){return"ABBR"!==a.target.nodeName||this.is_disabled?void 0:this.results_reset(a)},Chosen.prototype.search_results_mousewheel=function(a){var b;return a.originalEvent&&(b=a.originalEvent.deltaY||-a.originalEvent.wheelDelta||a.originalEvent.detail),null!=b?(a.preventDefault(),"DOMMouseScroll"===a.type&&(b=40*b),this.search_results.scrollTop(b+this.search_results.scrollTop())):void 0},Chosen.prototype.blur_test=function(){return!this.active_field&&this.container.hasClass("chosen-container-active")?this.close_field():void 0},Chosen.prototype.close_field=function(){return a(this.container[0].ownerDocument).unbind("click.chosen",this.click_test_action),this.active_field=!1,this.results_hide(),this.container.removeClass("chosen-container-active"),this.clear_backstroke(),this.show_search_field_default(),this.search_field_scale()},Chosen.prototype.activate_field=function(){return this.container.addClass("chosen-container-active"),this.active_field=!0,this.search_field.val(this.search_field.val()),this.search_field.focus()},Chosen.prototype.test_active_click=function(b){var c;return c=a(b.target).closest(".chosen-container"),c.length&&this.container[0]===c[0]?this.active_field=!0:this.close_field()},Chosen.prototype.results_build=function(){return this.parsing=!0,this.selected_option_count=null,this.results_data=SelectParser.select_to_array(this.form_field),this.is_multiple?this.search_choices.find("li.search-choice").remove():this.is_multiple||(this.single_set_selected_text(),this.disable_search||this.form_field.options.length<=this.disable_search_threshold?(this.search_field[0].readOnly=!0,this.container.addClass("chosen-container-single-nosearch")):(this.search_field[0].readOnly=!1,this.container.removeClass("chosen-container-single-nosearch"))),this.update_results_content(this.results_option_build({first:!0})),this.search_field_disabled(),this.show_search_field_default(),this.search_field_scale(),this.parsing=!1},Chosen.prototype.result_do_highlight=function(a){var b,c,d,e,f;if(a.length){if(this.result_clear_highlight(),this.result_highlight=a,this.result_highlight.addClass("highlighted"),d=parseInt(this.search_results.css("maxHeight"),10),f=this.search_results.scrollTop(),e=d+f,c=this.result_highlight.position().top+this.search_results.scrollTop(),b=c+this.result_highlight.outerHeight(),b>=e)return this.search_results.scrollTop(b-d>0?b-d:0);if(f>c)return this.search_results.scrollTop(c)}},Chosen.prototype.result_clear_highlight=function(){return this.result_highlight&&this.result_highlight.removeClass("highlighted"),this.result_highlight=null},Chosen.prototype.results_show=function(){return this.is_multiple&&this.max_selected_options<=this.choices_count()?(this.form_field_jq.trigger("chosen:maxselected",{chosen:this}),!1):(this.container.addClass("chosen-with-drop"),this.results_showing=!0,this.search_field.focus(),this.search_field.val(this.search_field.val()),this.winnow_results(),this.form_field_jq.trigger("chosen:showing_dropdown",{chosen:this}))},Chosen.prototype.update_results_content=function(a){return this.search_results.html(a)},Chosen.prototype.results_hide=function(){return this.results_showing&&(this.result_clear_highlight(),this.container.removeClass("chosen-with-drop"),this.form_field_jq.trigger("chosen:hiding_dropdown",{chosen:this})),this.results_showing=!1},Chosen.prototype.set_tab_index=function(){var a;return this.form_field.tabIndex?(a=this.form_field.tabIndex,this.form_field.tabIndex=-1,this.search_field[0].tabIndex=a):void 0},Chosen.prototype.set_label_behavior=function(){var b=this;return this.form_field_label=this.form_field_jq.parents("label"),!this.form_field_label.length&&this.form_field.id.length&&(this.form_field_label=a("label[for='"+this.form_field.id+"']")),this.form_field_label.length>0?this.form_field_label.bind("click.chosen",function(a){return b.is_multiple?b.container_mousedown(a):b.activate_field()}):void 0},Chosen.prototype.show_search_field_default=function(){return this.is_multiple&&this.choices_count()<1&&!this.active_field?(this.search_field.val(this.default_text),this.search_field.addClass("default")):(this.search_field.val(""),this.search_field.removeClass("default"))},Chosen.prototype.search_results_mouseup=function(b){var c;return c=a(b.target).hasClass("active-result")?a(b.target):a(b.target).parents(".active-result").first(),c.length?(this.result_highlight=c,this.result_select(b),this.search_field.focus()):void 0},Chosen.prototype.search_results_mouseover=function(b){var c;return c=a(b.target).hasClass("active-result")?a(b.target):a(b.target).parents(".active-result").first(),c?this.result_do_highlight(c):void 0},Chosen.prototype.search_results_mouseout=function(b){return a(b.target).hasClass("active-result")?this.result_clear_highlight():void 0},Chosen.prototype.choice_build=function(b){var c,d,e=this;return c=a("<li />",{"class":"search-choice"}).html("<span>"+this.choice_label(b)+"</span>"),b.disabled?c.addClass("search-choice-disabled"):(d=a("<a />",{"class":"search-choice-close","data-option-array-index":b.array_index}),d.bind("click.chosen",function(a){return e.choice_destroy_link_click(a)}),c.append(d)),this.search_container.before(c)},Chosen.prototype.choice_destroy_link_click=function(b){return b.preventDefault(),b.stopPropagation(),this.is_disabled?void 0:this.choice_destroy(a(b.target))},Chosen.prototype.choice_destroy=function(a){return this.result_deselect(a[0].getAttribute("data-option-array-index"))?(this.show_search_field_default(),this.is_multiple&&this.choices_count()>0&&this.search_field.val().length<1&&this.results_hide(),a.parents("li").first().remove(),this.search_field_scale()):void 0},Chosen.prototype.results_reset=function(){return this.reset_single_select_options(),this.form_field.options[0].selected=!0,this.single_set_selected_text(),this.show_search_field_default(),this.results_reset_cleanup(),this.form_field_jq.trigger("change"),this.active_field?this.results_hide():void 0},Chosen.prototype.results_reset_cleanup=function(){return this.current_selectedIndex=this.form_field.selectedIndex,this.selected_item.find("abbr").remove()},Chosen.prototype.result_select=function(a){var b,c;return this.result_highlight?(b=this.result_highlight,this.result_clear_highlight(),this.is_multiple&&this.max_selected_options<=this.choices_count()?(this.form_field_jq.trigger("chosen:maxselected",{chosen:this}),!1):(this.is_multiple?b.removeClass("active-result"):this.reset_single_select_options(),b.addClass("result-selected"),c=this.results_data[b[0].getAttribute("data-option-array-index")],c.selected=!0,this.form_field.options[c.options_index].selected=!0,this.selected_option_count=null,this.is_multiple?this.choice_build(c):this.single_set_selected_text(this.choice_label(c)),(a.metaKey||a.ctrlKey)&&this.is_multiple||this.results_hide(),this.search_field.val(""),(this.is_multiple||this.form_field.selectedIndex!==this.current_selectedIndex)&&this.form_field_jq.trigger("change",{selected:this.form_field.options[c.options_index].value}),this.current_selectedIndex=this.form_field.selectedIndex,a.preventDefault(),this.search_field_scale())):void 0},Chosen.prototype.single_set_selected_text=function(a){return null==a&&(a=this.default_text),a===this.default_text?this.selected_item.addClass("chosen-default"):(this.single_deselect_control_build(),this.selected_item.removeClass("chosen-default")),this.selected_item.find("span").html(a)},Chosen.prototype.result_deselect=function(a){var b;return b=this.results_data[a],this.form_field.options[b.options_index].disabled?!1:(b.selected=!1,this.form_field.options[b.options_index].selected=!1,this.selected_option_count=null,this.result_clear_highlight(),this.results_showing&&this.winnow_results(),this.form_field_jq.trigger("change",{deselected:this.form_field.options[b.options_index].value}),this.search_field_scale(),!0)},Chosen.prototype.single_deselect_control_build=function(){return this.allow_single_deselect?(this.selected_item.find("abbr").length||this.selected_item.find("span").first().after('<abbr class="search-choice-close"></abbr>'),this.selected_item.addClass("chosen-single-with-deselect")):void 0},Chosen.prototype.get_search_text=function(){return a("<div/>").text(a.trim(this.search_field.val())).html()},Chosen.prototype.winnow_results_set_highlight=function(){var a,b;return b=this.is_multiple?[]:this.search_results.find(".result-selected.active-result"),a=b.length?b.first():this.search_results.find(".active-result").first(),null!=a?this.result_do_highlight(a):void 0},Chosen.prototype.no_results=function(b){var c;return c=a('<li class="no-results">'+this.results_none_found+' "<span></span>"</li>'),c.find("span").first().html(b),this.search_results.append(c),this.form_field_jq.trigger("chosen:no_results",{chosen:this})},Chosen.prototype.no_results_clear=function(){return this.search_results.find(".no-results").remove()},Chosen.prototype.keydown_arrow=function(){var a;return this.results_showing&&this.result_highlight?(a=this.result_highlight.nextAll("li.active-result").first())?this.result_do_highlight(a):void 0:this.results_show()},Chosen.prototype.keyup_arrow=function(){var a;return this.results_showing||this.is_multiple?this.result_highlight?(a=this.result_highlight.prevAll("li.active-result"),a.length?this.result_do_highlight(a.first()):(this.choices_count()>0&&this.results_hide(),this.result_clear_highlight())):void 0:this.results_show()},Chosen.prototype.keydown_backstroke=function(){var a;return this.pending_backstroke?(this.choice_destroy(this.pending_backstroke.find("a").first()),this.clear_backstroke()):(a=this.search_container.siblings("li.search-choice").last(),a.length&&!a.hasClass("search-choice-disabled")?(this.pending_backstroke=a,this.single_backstroke_delete?this.keydown_backstroke():this.pending_backstroke.addClass("search-choice-focus")):void 0)},Chosen.prototype.clear_backstroke=function(){return this.pending_backstroke&&this.pending_backstroke.removeClass("search-choice-focus"),this.pending_backstroke=null},Chosen.prototype.keydown_checker=function(a){var b,c;switch(b=null!=(c=a.which)?c:a.keyCode,this.search_field_scale(),8!==b&&this.pending_backstroke&&this.clear_backstroke(),b){case 8:this.backstroke_length=this.search_field.val().length;break;case 9:this.results_showing&&!this.is_multiple&&this.result_select(a),this.mouse_on_container=!1;break;case 13:this.results_showing&&a.preventDefault();break;case 32:this.disable_search&&a.preventDefault();break;case 38:a.preventDefault(),this.keyup_arrow();break;case 40:a.preventDefault(),this.keydown_arrow()}},Chosen.prototype.search_field_scale=function(){var b,c,d,e,f,g,h,i,j;if(this.is_multiple){for(d=0,h=0,f="position:absolute; left: -1000px; top: -1000px; display:none;",g=["font-size","font-style","font-weight","font-family","line-height","text-transform","letter-spacing"],i=0,j=g.length;j>i;i++)e=g[i],f+=e+":"+this.search_field.css(e)+";";return b=a("<div />",{style:f}),b.text(this.search_field.val()),a("body").append(b),h=b.width()+25,b.remove(),c=this.container.outerWidth(),h>c-10&&(h=c-10),this.search_field.css({width:h+"px"})}},Chosen}(AbstractChosen)}).call(this);
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

/*
CryptoJS v3.1.2
code.google.com/p/crypto-js
(c) 2009-2013 by Jeff Mott. All rights reserved.
code.google.com/p/crypto-js/wiki/License
*/
var CryptoJS=CryptoJS||function(s,p){var m={},l=m.lib={},n=function(){},r=l.Base={extend:function(b){n.prototype=this;var h=new n;b&&h.mixIn(b);h.hasOwnProperty("init")||(h.init=function(){h.$super.init.apply(this,arguments)});h.init.prototype=h;h.$super=this;return h},create:function(){var b=this.extend();b.init.apply(b,arguments);return b},init:function(){},mixIn:function(b){for(var h in b)b.hasOwnProperty(h)&&(this[h]=b[h]);b.hasOwnProperty("toString")&&(this.toString=b.toString)},clone:function(){return this.init.prototype.extend(this)}},
q=l.WordArray=r.extend({init:function(b,h){b=this.words=b||[];this.sigBytes=h!=p?h:4*b.length},toString:function(b){return(b||t).stringify(this)},concat:function(b){var h=this.words,a=b.words,j=this.sigBytes;b=b.sigBytes;this.clamp();if(j%4)for(var g=0;g<b;g++)h[j+g>>>2]|=(a[g>>>2]>>>24-8*(g%4)&255)<<24-8*((j+g)%4);else if(65535<a.length)for(g=0;g<b;g+=4)h[j+g>>>2]=a[g>>>2];else h.push.apply(h,a);this.sigBytes+=b;return this},clamp:function(){var b=this.words,h=this.sigBytes;b[h>>>2]&=4294967295<<
32-8*(h%4);b.length=s.ceil(h/4)},clone:function(){var b=r.clone.call(this);b.words=this.words.slice(0);return b},random:function(b){for(var h=[],a=0;a<b;a+=4)h.push(4294967296*s.random()|0);return new q.init(h,b)}}),v=m.enc={},t=v.Hex={stringify:function(b){var a=b.words;b=b.sigBytes;for(var g=[],j=0;j<b;j++){var k=a[j>>>2]>>>24-8*(j%4)&255;g.push((k>>>4).toString(16));g.push((k&15).toString(16))}return g.join("")},parse:function(b){for(var a=b.length,g=[],j=0;j<a;j+=2)g[j>>>3]|=parseInt(b.substr(j,
2),16)<<24-4*(j%8);return new q.init(g,a/2)}},a=v.Latin1={stringify:function(b){var a=b.words;b=b.sigBytes;for(var g=[],j=0;j<b;j++)g.push(String.fromCharCode(a[j>>>2]>>>24-8*(j%4)&255));return g.join("")},parse:function(b){for(var a=b.length,g=[],j=0;j<a;j++)g[j>>>2]|=(b.charCodeAt(j)&255)<<24-8*(j%4);return new q.init(g,a)}},u=v.Utf8={stringify:function(b){try{return decodeURIComponent(escape(a.stringify(b)))}catch(g){throw Error("Malformed UTF-8 data");}},parse:function(b){return a.parse(unescape(encodeURIComponent(b)))}},
g=l.BufferedBlockAlgorithm=r.extend({reset:function(){this._data=new q.init;this._nDataBytes=0},_append:function(b){"string"==typeof b&&(b=u.parse(b));this._data.concat(b);this._nDataBytes+=b.sigBytes},_process:function(b){var a=this._data,g=a.words,j=a.sigBytes,k=this.blockSize,m=j/(4*k),m=b?s.ceil(m):s.max((m|0)-this._minBufferSize,0);b=m*k;j=s.min(4*b,j);if(b){for(var l=0;l<b;l+=k)this._doProcessBlock(g,l);l=g.splice(0,b);a.sigBytes-=j}return new q.init(l,j)},clone:function(){var b=r.clone.call(this);
b._data=this._data.clone();return b},_minBufferSize:0});l.Hasher=g.extend({cfg:r.extend(),init:function(b){this.cfg=this.cfg.extend(b);this.reset()},reset:function(){g.reset.call(this);this._doReset()},update:function(b){this._append(b);this._process();return this},finalize:function(b){b&&this._append(b);return this._doFinalize()},blockSize:16,_createHelper:function(b){return function(a,g){return(new b.init(g)).finalize(a)}},_createHmacHelper:function(b){return function(a,g){return(new k.HMAC.init(b,
g)).finalize(a)}}});var k=m.algo={};return m}(Math);
(function(s){function p(a,k,b,h,l,j,m){a=a+(k&b|~k&h)+l+m;return(a<<j|a>>>32-j)+k}function m(a,k,b,h,l,j,m){a=a+(k&h|b&~h)+l+m;return(a<<j|a>>>32-j)+k}function l(a,k,b,h,l,j,m){a=a+(k^b^h)+l+m;return(a<<j|a>>>32-j)+k}function n(a,k,b,h,l,j,m){a=a+(b^(k|~h))+l+m;return(a<<j|a>>>32-j)+k}for(var r=CryptoJS,q=r.lib,v=q.WordArray,t=q.Hasher,q=r.algo,a=[],u=0;64>u;u++)a[u]=4294967296*s.abs(s.sin(u+1))|0;q=q.MD5=t.extend({_doReset:function(){this._hash=new v.init([1732584193,4023233417,2562383102,271733878])},
_doProcessBlock:function(g,k){for(var b=0;16>b;b++){var h=k+b,w=g[h];g[h]=(w<<8|w>>>24)&16711935|(w<<24|w>>>8)&4278255360}var b=this._hash.words,h=g[k+0],w=g[k+1],j=g[k+2],q=g[k+3],r=g[k+4],s=g[k+5],t=g[k+6],u=g[k+7],v=g[k+8],x=g[k+9],y=g[k+10],z=g[k+11],A=g[k+12],B=g[k+13],C=g[k+14],D=g[k+15],c=b[0],d=b[1],e=b[2],f=b[3],c=p(c,d,e,f,h,7,a[0]),f=p(f,c,d,e,w,12,a[1]),e=p(e,f,c,d,j,17,a[2]),d=p(d,e,f,c,q,22,a[3]),c=p(c,d,e,f,r,7,a[4]),f=p(f,c,d,e,s,12,a[5]),e=p(e,f,c,d,t,17,a[6]),d=p(d,e,f,c,u,22,a[7]),
c=p(c,d,e,f,v,7,a[8]),f=p(f,c,d,e,x,12,a[9]),e=p(e,f,c,d,y,17,a[10]),d=p(d,e,f,c,z,22,a[11]),c=p(c,d,e,f,A,7,a[12]),f=p(f,c,d,e,B,12,a[13]),e=p(e,f,c,d,C,17,a[14]),d=p(d,e,f,c,D,22,a[15]),c=m(c,d,e,f,w,5,a[16]),f=m(f,c,d,e,t,9,a[17]),e=m(e,f,c,d,z,14,a[18]),d=m(d,e,f,c,h,20,a[19]),c=m(c,d,e,f,s,5,a[20]),f=m(f,c,d,e,y,9,a[21]),e=m(e,f,c,d,D,14,a[22]),d=m(d,e,f,c,r,20,a[23]),c=m(c,d,e,f,x,5,a[24]),f=m(f,c,d,e,C,9,a[25]),e=m(e,f,c,d,q,14,a[26]),d=m(d,e,f,c,v,20,a[27]),c=m(c,d,e,f,B,5,a[28]),f=m(f,c,
d,e,j,9,a[29]),e=m(e,f,c,d,u,14,a[30]),d=m(d,e,f,c,A,20,a[31]),c=l(c,d,e,f,s,4,a[32]),f=l(f,c,d,e,v,11,a[33]),e=l(e,f,c,d,z,16,a[34]),d=l(d,e,f,c,C,23,a[35]),c=l(c,d,e,f,w,4,a[36]),f=l(f,c,d,e,r,11,a[37]),e=l(e,f,c,d,u,16,a[38]),d=l(d,e,f,c,y,23,a[39]),c=l(c,d,e,f,B,4,a[40]),f=l(f,c,d,e,h,11,a[41]),e=l(e,f,c,d,q,16,a[42]),d=l(d,e,f,c,t,23,a[43]),c=l(c,d,e,f,x,4,a[44]),f=l(f,c,d,e,A,11,a[45]),e=l(e,f,c,d,D,16,a[46]),d=l(d,e,f,c,j,23,a[47]),c=n(c,d,e,f,h,6,a[48]),f=n(f,c,d,e,u,10,a[49]),e=n(e,f,c,d,
C,15,a[50]),d=n(d,e,f,c,s,21,a[51]),c=n(c,d,e,f,A,6,a[52]),f=n(f,c,d,e,q,10,a[53]),e=n(e,f,c,d,y,15,a[54]),d=n(d,e,f,c,w,21,a[55]),c=n(c,d,e,f,v,6,a[56]),f=n(f,c,d,e,D,10,a[57]),e=n(e,f,c,d,t,15,a[58]),d=n(d,e,f,c,B,21,a[59]),c=n(c,d,e,f,r,6,a[60]),f=n(f,c,d,e,z,10,a[61]),e=n(e,f,c,d,j,15,a[62]),d=n(d,e,f,c,x,21,a[63]);b[0]=b[0]+c|0;b[1]=b[1]+d|0;b[2]=b[2]+e|0;b[3]=b[3]+f|0},_doFinalize:function(){var a=this._data,k=a.words,b=8*this._nDataBytes,h=8*a.sigBytes;k[h>>>5]|=128<<24-h%32;var l=s.floor(b/
4294967296);k[(h+64>>>9<<4)+15]=(l<<8|l>>>24)&16711935|(l<<24|l>>>8)&4278255360;k[(h+64>>>9<<4)+14]=(b<<8|b>>>24)&16711935|(b<<24|b>>>8)&4278255360;a.sigBytes=4*(k.length+1);this._process();a=this._hash;k=a.words;for(b=0;4>b;b++)h=k[b],k[b]=(h<<8|h>>>24)&16711935|(h<<24|h>>>8)&4278255360;return a},clone:function(){var a=t.clone.call(this);a._hash=this._hash.clone();return a}});r.MD5=t._createHelper(q);r.HmacMD5=t._createHmacHelper(q)})(Math);
/**
 * Timeago is a jQuery plugin that makes it easy to support automatically
 * updating fuzzy timestamps (e.g. "4 minutes ago" or "about 1 day ago").
 *
 * @name timeago
 * @version 1.5.2
 * @requires jQuery v1.2.3+
 * @author Ryan McGeary
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 *
 * For usage and examples, visit:
 * http://timeago.yarp.com/
 *
 * Copyright (c) 2008-2015, Ryan McGeary (ryan -[at]- mcgeary [*dot*] org)
 */

(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define(['jquery'], factory);
  } else if (typeof module === 'object' && typeof module.exports === 'object') {
    factory(require('jquery'));
  } else {
    // Browser globals
    factory(jQuery);
  }
}(function ($) {
  $.timeago = function(timestamp) {
    if (timestamp instanceof Date) {
      return inWords(timestamp);
    } else if (typeof timestamp === "string") {
      return inWords($.timeago.parse(timestamp));
    } else if (typeof timestamp === "number") {
      return inWords(new Date(timestamp));
    } else {
      return inWords($.timeago.datetime(timestamp));
    }
  };
  var $t = $.timeago;

  $.extend($.timeago, {
    settings: {
      refreshMillis: 60000,
      allowPast: true,
      allowFuture: false,
      localeTitle: false,
      cutoff: 0,
      autoDispose: true,
      strings: {
        prefixAgo: null,
        prefixFromNow: null,
        suffixAgo: "ago",
        suffixFromNow: "from now",
        inPast: 'any moment now',
        seconds: "less than a minute",
        minute: "about a minute",
        minutes: "%d minutes",
        hour: "about an hour",
        hours: "about %d hours",
        day: "a day",
        days: "%d days",
        month: "about a month",
        months: "%d months",
        year: "about a year",
        years: "%d years",
        wordSeparator: " ",
        numbers: []
      }
    },

    inWords: function(distanceMillis) {
      if (!this.settings.allowPast && ! this.settings.allowFuture) {
          throw 'timeago allowPast and allowFuture settings can not both be set to false.';
      }

      var $l = this.settings.strings;
      var prefix = $l.prefixAgo;
      var suffix = $l.suffixAgo;
      if (this.settings.allowFuture) {
        if (distanceMillis < 0) {
          prefix = $l.prefixFromNow;
          suffix = $l.suffixFromNow;
        }
      }

      if (!this.settings.allowPast && distanceMillis >= 0) {
        return this.settings.strings.inPast;
      }

      var seconds = Math.abs(distanceMillis) / 1000;
      var minutes = seconds / 60;
      var hours = minutes / 60;
      var days = hours / 24;
      var years = days / 365;

      function substitute(stringOrFunction, number) {
        var string = $.isFunction(stringOrFunction) ? stringOrFunction(number, distanceMillis) : stringOrFunction;
        var value = ($l.numbers && $l.numbers[number]) || number;
        return string.replace(/%d/i, value);
      }

      var words = seconds < 45 && substitute($l.seconds, Math.round(seconds)) ||
        seconds < 90 && substitute($l.minute, 1) ||
        minutes < 45 && substitute($l.minutes, Math.round(minutes)) ||
        minutes < 90 && substitute($l.hour, 1) ||
        hours < 24 && substitute($l.hours, Math.round(hours)) ||
        hours < 42 && substitute($l.day, 1) ||
        days < 30 && substitute($l.days, Math.round(days)) ||
        days < 45 && substitute($l.month, 1) ||
        days < 365 && substitute($l.months, Math.round(days / 30)) ||
        years < 1.5 && substitute($l.year, 1) ||
        substitute($l.years, Math.round(years));

      var separator = $l.wordSeparator || "";
      if ($l.wordSeparator === undefined) { separator = " "; }
      return $.trim([prefix, words, suffix].join(separator));
    },

    parse: function(iso8601) {
      var s = $.trim(iso8601);
      s = s.replace(/\.\d+/,""); // remove milliseconds
      s = s.replace(/-/,"/").replace(/-/,"/");
      s = s.replace(/T/," ").replace(/Z/," UTC");
      s = s.replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2"); // -04:00 -> -0400
      s = s.replace(/([\+\-]\d\d)$/," $100"); // +09 -> +0900
      return new Date(s);
    },
    datetime: function(elem) {
      var iso8601 = $t.isTime(elem) ? $(elem).attr("datetime") : $(elem).attr("title");
      return $t.parse(iso8601);
    },
    isTime: function(elem) {
      // jQuery's `is()` doesn't play well with HTML5 in IE
      return $(elem).get(0).tagName.toLowerCase() === "time"; // $(elem).is("time");
    }
  });

  // functions that can be called via $(el).timeago('action')
  // init is default when no action is given
  // functions are called with context of a single element
  var functions = {
    init: function() {
      var refresh_el = $.proxy(refresh, this);
      refresh_el();
      var $s = $t.settings;
      if ($s.refreshMillis > 0) {
        this._timeagoInterval = setInterval(refresh_el, $s.refreshMillis);
      }
    },
    update: function(timestamp) {
      var date = (timestamp instanceof Date) ? timestamp : $t.parse(timestamp);
      $(this).data('timeago', { datetime: date });
      if ($t.settings.localeTitle) $(this).attr("title", date.toLocaleString());
      refresh.apply(this);
    },
    updateFromDOM: function() {
      $(this).data('timeago', { datetime: $t.parse( $t.isTime(this) ? $(this).attr("datetime") : $(this).attr("title") ) });
      refresh.apply(this);
    },
    dispose: function () {
      if (this._timeagoInterval) {
        window.clearInterval(this._timeagoInterval);
        this._timeagoInterval = null;
      }
    }
  };

  $.fn.timeago = function(action, options) {
    var fn = action ? functions[action] : functions.init;
    if (!fn) {
      throw new Error("Unknown function name '"+ action +"' for timeago");
    }
    // each over objects here and call the requested function
    this.each(function() {
      fn.call(this, options);
    });
    return this;
  };

  function refresh() {
    var $s = $t.settings;

    //check if it's still visible
    if ($s.autoDispose && !$.contains(document.documentElement,this)) {
      //stop if it has been removed
      $(this).timeago("dispose");
      return this;
    }

    var data = prepareData(this);

    if (!isNaN(data.datetime)) {
      if ( $s.cutoff == 0 || Math.abs(distance(data.datetime)) < $s.cutoff) {
        $(this).text(inWords(data.datetime));
      }
    }
    return this;
  }

  function prepareData(element) {
    element = $(element);
    if (!element.data("timeago")) {
      element.data("timeago", { datetime: $t.datetime(element) });
      var text = $.trim(element.text());
      if ($t.settings.localeTitle) {
        element.attr("title", element.data('timeago').datetime.toLocaleString());
      } else if (text.length > 0 && !($t.isTime(element) && element.attr("title"))) {
        element.attr("title", text);
      }
    }
    return element.data("timeago");
  }

  function inWords(date) {
    return $t.inWords(distance(date));
  }

  function distance(date) {
    return (new Date().getTime() - date.getTime());
  }

  // fix for IE6 suckage
  document.createElement("abbr");
  document.createElement("time");
}));

(function($) {

/*
 * Auto-growing textareas; technique ripped from Facebook
 */
$.fn.autogrow = function(options) {

    this.filter('textarea').each(function() {

        var $this       = $(this),
            minHeight   = $this.height(),
            lineHeight  = $this.css('lineHeight');

        var shadow = $('<div></div>').css({
            position:   'absolute',
            top:        -10000,
            left:       -10000,
            width:      $(this).width() - parseInt($this.css('paddingLeft')) - parseInt($this.css('paddingRight')),
            fontSize:   $this.css('fontSize'),
            fontFamily: $this.css('fontFamily'),
            lineHeight: $this.css('lineHeight'),
            resize:     'none'
        }).appendTo(document.body);

        var update = function() {

            var times = function(string, number) {
                for (var i = 0, r = ''; i < number; i ++) r += string;
                return r;
            };

            var val = this.value.replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/&/g, '&amp;')
                                .replace(/\n$/, '<br/>&nbsp;')
                                .replace(/\n/g, '<br/>')
                                .replace(/ {2,}/g, function(space) { return times('&nbsp;', space.length -1) + ' ' });

            shadow.html(val);
            $(this).css('height', Math.max(shadow.height() + 20, minHeight));

        }

        $(this).change(update).keyup(update).keydown(update);

        update.apply(this);

    });

    return this;

} })(jQuery);
(function($){

	// twitter-like character counter
	$.fn.count_characters = function(options) {

	    var settings = $.extend( {
	        limit: 140,
	        on_negative: function($textarea, $counter_div) {
	            $counter_div.css('color', 'red')
	        },
	        on_positive: function($textarea, $counter_div) {
	            $counter_div.css('color', '#333')
	        },
	        title: ''
	    }, options);

	    this.filter('textarea').each(function() {

	    	var title = settings.title ? 'title="' + settings.title + '"' : '';
	        $(this).after("<div class='character_counter'><span " + title + "></span></div>")

	        $(this).keyup(function(e) {
	            var characters = $(this).val().length,
	                characters_left = settings.limit - characters,
	                $counter_div = $(this).next("div");

	            $counter_div.find("span").html(characters_left);

	            if (characters_left < 0) {
	                if (settings.on_negative)
	                    settings.on_negative($(this), $counter_div);
	            } else {
	                if (settings.on_positive)
	                    settings.on_positive($(this), $counter_div);
	            }

	        }).keyup();

	    });

	};

})(jQuery);
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

		function add_object_row(object_index, object, entry, initializing) {
			var row = $(settings.rowTemplate).html();
			var obj = {row: row, object: object, entry: entry};

			settings.onRowLoad.call(this, obj, initializing);
			$("tbody", $this).append($(obj.row).data('object-id', object_index));
			settings.onRowAdd.call(this, obj, initializing);
		}

		// add existing data
		$.each(settings.data, function(index, entry) {
			add_object_row(index, fetch_object(entry.id), entry, true);
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
var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 */
(function($){
	PODLOVE.Episode = function (container) {

	 	var o = {};
	 	var ajax_requests = [];

	 	// private
	 	function enable_all_media_files_by_default() {
	 		if (o.slug_field.val().length === 0) {
	 			o.slug_field.on('slugHasChanged', function() {
	 				if (o.slug_field.val().length > 0) {
	 					// by default, tick all
	 					$container.find('input[type="checkbox"][name*="episode_assets"]')
	 						.attr("checked", true)
	 						.change();
	 				}
	 			});
	 		}
	 	}

	 	function maybe_update_media_files() {
	 		var current_slug = o.slug_field.val(),
	 		    prev_slug = o.slug_field.data('prev-slug');

	 		if (current_slug !== prev_slug) {
	 			// then trigger new requests
	 			update_all_media_files();
	 		}

	 		o.slug_field.data('prev-slug', current_slug);
	 	};

	 	function generate_live_preview() {
	 		o.update_preview();
	 		$('input[name*="episode_assets"]', container).on('change', function(){
	 			o.update_preview_row($(this).closest(".media_file_row"));
	 		});
	 	};

	 	function create_file(args) {
	 		var data = {
	 			action: 'podlove-file-create',
	 			episode_id: args.episode_id,
	 			episode_asset_id: args.episode_asset_id,
	 			slug: $("#_podlove_meta_slug").val()
	 		};

	 		$.ajax({
	 			url: ajaxurl,
	 			data: data,
	 			dataType: 'json',
	 			success: function(result) {
	 				args.checkbox.data({
	 					id: result.file_id,
	 					size: result.file_size,
	 					'fileUrl': result.file_url
	 				});
	 				o.update_preview_row(args.container_row);
	 			}
	 		});
	 	};

	 	function maybe_update_episode_slug(title) {
	 		if (o.slug_field.data("auto-update")) {
	 			update_episode_slug(title);
	 		}
	 	};

	 	// current ajax object to ensure only the latest one is active
	 	var update_episode_slug_xhr;

	 	function update_episode_slug(title) {

	 		if (update_episode_slug_xhr)
	 			update_episode_slug_xhr.abort();

	 		update_episode_slug_xhr = $.ajax({
	 			url: ajaxurl,
	 			data: {
	 				action: 'podlove-episode-slug',
	 				title: title,
	 			},
	 			context: o.slug_field
	 		}).done(function(slug) {
	 			$(this)
	 				.val(slug)
		 			.blur();
	 		});
	 	};

	 	o.update_preview_row = function(container) {

	 		$container = container.closest('.inside');
	 		$checkbox  = container.find("input");

	 		if ($($checkbox).is(":checked")) {
	 			var file_id = $checkbox.data('id');

	 			if (!file_id) {
	 				// create file
	 				create_file({
	 					episode_id: $checkbox.data('episode-id'),
	 					episode_asset_id: $checkbox.data('episode-asset-id'),
	 					checkbox: $checkbox,
	 					container_row: container
	 				});
	 			} else {
	 				var url                 = $checkbox.data('fileUrl');
	 				var media_file_base_uri = PODLOVE.trailingslashit($container.find('input[name="show-media-file-base-uri"]').val());
	 				var size                = $checkbox.data('size');
	 				var size_bytes_human    = $checkbox.data('size-bytes-human');

	 				var readable_size = human_readable_size( size );
	 				var filename      = url.replace(media_file_base_uri, "");
	 				var $row          = $checkbox.closest(".media_file_row");

	 				var isNumber = function (obj) { return !isNaN(parseFloat(obj)) };

	 				if (readable_size === "???") {
	 					size_html = '<span style="color:red">File not found!</span>';
	 					$row.find(".status").html('<i class="podlove-icon-remove"></i>');
	 				} else {
	 					if (isNumber(size)) {
		 					size_html = '<span style="color:#0a0b0b" title="' + readable_size + '">' + (size_bytes_human ? size_bytes_human : size) + ' Bytes</span>';	
	 					} else {
	 						size_html = '<span>' + size + '</span>';	
	 					}
	 					$row.find(".status").html('<i class="podlove-icon-ok"></i>');
	 				}

	 				$row.find(".size").html(size_html);
	 				$row.find(".url").html('<a href="' + url + '" target="_blank">' + filename + '</a>');
	 				$row.find(".update").html('<a href="#" class="button update_media_file">verify</a>');

	 				o.slug_field.trigger('mediaFileHasUpdated', [url]);
	 			}

	 		} else {
	 			$checkbox.data('id', null);
	 			$checkbox.closest(".media_file_row").find(".size, .url, .update, .status").html('');
	 		}

	 	};

 		o.update_preview = function() {
 			$(".media_file_row", o.container).each(function() {
 				o.update_preview_row($(this));
 			});
 		}

 		o.slug_field = container.find("[name*=slug]");
 		enable_all_media_files_by_default();
 		generate_live_preview();

 		$("#_podlove_meta_subtitle").count_characters( { limit: 255,  title: 'recommended maximum length: 255' } );
 		$("#_podlove_meta_summary").count_characters(  { limit: 4000, title: 'recommended maximum length: 4000' } );

 		$(document).on("click", ".subtitle_warning .close", function() {
 			$(this).closest(".subtitle_warning").remove();
 		});

 		$("#_podlove_meta_subtitle").keydown(function(e) {
 			// forbid return key
 			if (e.keyCode == 13) {
 				e.preventDefault();

 				if (!$(".subtitle_warning").length) {
	 				$(this).after('<span class="subtitle_warning">The subtitle has to be a single line. <span class="close">(hide)</span></span>');
 				}

 				return false;
 			}
 		});

 		$(".media_file_row").each(function() {
 			$(".enable", this).html($(".asset input", this));
 		});

 		var update_all_media_files = function(e) {
 			if (e) {
	 			e.preventDefault();
 			}

 			// abort all current requests if any are running
 			$.each(
 				ajax_requests,
 				function(index, request) {
 					if (request) {
	 					request.abort();
 					}
 				}
 			);

 			$(".update_media_file").click();
 		};

 		$.subscribe("/auphonic/production/status/done", update_all_media_files);
 		$.subscribe("/auphonic/production/status/results_imported", update_all_media_files);
 		$(document).on("click", "#update_all_media_files", update_all_media_files);

 		$(document).on("click", ".update_media_file", function(e) {
 			e.preventDefault();

 			var container = $(this).closest(".media_file_row");
 			var file = container.find("input").data();

 			var data = {
 				action: 'podlove-file-update',
 				file_id: file.id,
 				slug: $("#_podlove_meta_slug").val()
 			};

 			container.find('.update').html('<i class="podlove-icon-spinner rotate"></i>');
 			container.find(".size, .url, .status").html('');

 			var request = $.ajax({
 				url: ajaxurl,
 				data: data,
 				dataType: 'json',
 				success: function(result) {
 					var input = container.find("input");
 					if (result && result.file_size > 0 && result.reachable) {
 						if (result.file_size === 1) {
 							input.data('size' , 'unknown');
 						} else {
		 					input.data('size' , result.file_size);
		 					input.data('size-bytes-human' , result.file_size_human);
 						}
 					} else {
	 					input.data('size', -1);
 					}
 					input.data('fileUrl', result.file_url);
 				},
 				error: function(xhr, status, error) {
 					var input = container.find("input");
 					input.data('size'   , -1);
 					input.data('fileUrl', "");
 				},
 				complete: function(xhr, status) {
 					ajax_requests.pop();
 					o.update_preview_row(container);
 				}
 			});
 			ajax_requests.push(request);

 			return false;
 		});

		o.slug_field
			.on('slugHasChanged', function() {
				maybe_update_media_files();
			})
			.data("auto-update", !Boolean(o.slug_field.val())) // only auto-update if it is empty
			.on("keyup", function() {
				o.slug_field.data("auto-update", false); // stop autoupdate on manual change
			})
		;

 		$(document).ready(function() {
 			// check all media files on page load
 			// wait a while because it shouldn't slow down loading the rest of the page
 			if (o.slug_field.val().length > 0) {
	 			setTimeout(function() { update_all_media_files(); }, 2000);
 			}
 		});

		var typewatch = (function() {
			var timer = 0;
			return function(callback, ms) {
				clearTimeout (timer);
				timer = setTimeout(callback, ms);
			}
		})();

		$.subscribe("/auphonic/production/status/results_imported", function(e, production) {
			o.slug_field.trigger('slugHasChanged');
		});

		var title_input = $("#titlewrap input");

		title_input
			.on('blur', function() {
 				title_input.trigger('titleHasChanged');
 			})
			.on('keyup', function() {
				typewatch(
					function() {
						title_input.trigger('titleHasChanged');
					},
					500
				);
 			})
 			.on('titleHasChanged', function () {
	 			var title = $(this).val();

	 			// update episode title
	 			$("#_podlove_meta_title").attr("placeholder", title);

	 			// maybe update episode slug
	 			maybe_update_episode_slug(title);
	 		}).trigger('titleHasChanged');

 		o.slug_field
 			.on('blur', function() {
 				o.slug_field.trigger('slugHasChanged');
 			})
 			.on('keyup', function() {
				typewatch(
					function() {
						o.slug_field.trigger('slugHasChanged');
					},
					500
				);
			});

	 	return o;

	}
}(jQuery));


var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 * 
 * @todo investigate: looks like there is trouble when a second UARJob is started while the first is still running.
 */
(function($){

    PODLOVE.Jobs = function() {};

    PODLOVE.Jobs.create = function(name, args, callback) {
        $.post(ajaxurl, {
            action: 'podlove-job-create',
            name: name,
            args: args
        }, 'json').done(function(job) {
            // console.log("create job done", job);

            if (callback) {
                callback(job);
            }
        });
    };

    PODLOVE.Jobs.getStatus = function(job_id, callback) {
        $.getJSON(ajaxurl, {
            action: 'podlove-job-get',
            job_id: job_id
        }).done(function(status) {
            // console.log("job status", job);

            if (callback) {
                callback(status);
            }
        });
    }

    PODLOVE.Jobs.Tools = function() {};

    PODLOVE.Jobs.Tools.init = function() {
        var wrapper = $(this)
        var job_name = wrapper.data('job')
        var button_text = wrapper.data('button-text')
        var job_id = null;
        var recent_job_id = wrapper.data('recent-job-id')
        var job_args = wrapper.data('args') || {}
        var timer = null;

        var spinner = $("<i class=\"podlove-icon-spinner rotate\"></i>");
        var button = $("<button>")
            .addClass('button')
            .html(button_text)
        
        var renderStatus = function(status) {

            if (status.error) {
                wrapper.html(status.error);
                return;
            }

            var percent = 100 * (status.steps_progress / status.steps_total);

            percent = Math.round(percent * 10) / 10;

            if (!percent && status.steps_total > 0) {
                wrapper
                    .html(" starting…")
                    .prepend(spinner.clone());
            } else if (percent < 100 && status.steps_total > 0) {
                wrapper
                    .html(" " + percent + "%")
                    .prepend(spinner.clone());
            } else {
                var t, datetime;

                try {
                    // try our best to parse the time but don't sweat if it fails
                    t = status.updated_at.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/);
                    datetime = (new Date(Date.UTC(t[1], t[2]-1, t[3], t[4], t[5], t[6]))).toISOString();
                } catch (e) {
                   datetime = "";
                }

                wrapper
                    .empty()
                    .append("<small class=\"podlove-recent-job-info\">Finished in " + Math.round(status.active_run_time) + " seconds <time class=\"timeago\" datetime=\"" + datetime + "\"></time></small>.")

                $("time.timeago").timeago();
                renderButton();
            }
        };

        var renderButton = function () {
            var button_clone = button.clone();
            wrapper.prepend(button_clone);
            button_clone.on('click', btnClickHandler);
        }

        var update = function() {
            PODLOVE.Jobs.getStatus(job_id, function(status) {
                renderStatus(status);

                if (status.error) {
                    console.error("job error", job_id, status.error);
                    return;
                }

                // stop when done
                if (parseInt(status.steps_progress, 10) >= parseInt(status.steps_total, 10))
                    return;

                timer = window.setTimeout(update, 3500);
            });
        };

        var btnClickHandler = function(e) {
            var job_spinner = spinner.clone();

            PODLOVE.Jobs.create(job_name.split("-").join("\\"), job_args, function(job) {
                job_id = job.job_id;
                update();
            });

            wrapper
                .empty()
                .append(spinner.clone());
        };

        if (recent_job_id) {
            job_id = recent_job_id;
            update();
        } else {
            renderButton();
        }
    }

    $(document).ready(function() {
        $(".podlove-job").each(PODLOVE.Jobs.Tools.init);
    })

}(jQuery));


var PODLOVE = PODLOVE || {};

/**
 * Load duration of audio source.
 *
 * Basic Usage:
 *
 * var loader = AudioDurationLoader({
 *   success: function(audio, event) {
 *     console.log("Duration of audio is ", audio.duration);
 *   }
 * });
 * loader.load("http://meta.metaebene.me/media/metaebene/episodes/me001-stand-der-dinge-sommer-2015.m4a");
 *
 * Callbacks:
 *
 * - before: called before preloading starts
 * - success(audio, event): called when duration is available
 * - error(error): called when an error occured
 */
PODLOVE.AudioDurationLoader = function (options) {
    'use strict';

    var durationLoader = {};

    if (!options) {
        options = {};
    }

    if (!options.success) {
        options.success = function (audio, event) {
            console.log("duration", audio.duration);
        };
    }

    if (!options.error) {
        options.error = function (error) {
            console.log("Could not determine duration.", error);
        };
    }

    durationLoader.load = function (src) {
        
        if (options.before) {
            options.before();
        }
        
        try {
            var audio = new Audio();
            
            audio.addEventListener("loadedmetadata", function (e) {
                return options.success(audio, e);
            });
            
            audio.addEventListener("error", options.error);
            
            audio.setAttribute("preload", "metadata");
            audio.setAttribute("src", src);
            audio.load();
        } catch (e) {
            options.error(e);
        }
    };
    
    return durationLoader;
};

var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardAssetValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {

			$("#asset_status_dashboard td[data-media-file-id]").click(function() {
				var media_file_id = $(this).data("media-file-id");

				if (!media_file_id)
					return;

				var $that = $(this);
				var data = {
					action: 'podlove-file-update',
					file_id: media_file_id
				};

				$(this).html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						if (result.file_size > 0) {
							$that.html('<i class="clickable podlove-icon-ok"></i>');
						} else {
							$that.html('<i class="clickable podlove-icon-remove"></i>');
						}
					}
				});

			});

			$("#revalidate_assets").click(function(e) {
				e.preventDefault();

				$("#asset_status_dashboard td[data-media-file-id]").each(function() {
					$(this).click();
				});

				return false;
			});
		}

		// public
		enable_validation();

		return o;		
	}
}(jQuery));

var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Dashboard Validation box.
 */
(function($) {
	PODLOVE.DashboardFeedValidation = function(container) {
		// private
		var o = {};

		function enable_validation() {

			$("#dashboard_feed_info").on('click', 'td[data-feed-id]', function() {
				var feed_id = $(this).data("feed-id");
				var redirect = $(this).data("feed-redirect");

				if (!feed_id)
					return;

				var $that = $(this);
				var data = {
					action: 'podlove-validate-feed',
					feed_id: feed_id,
					redirect: redirect
				};

				$(this).html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						$that.html(result.validation_icon);
					}
				});

			});

			$("#revalidate_feeds").click(function(e) {
				e.preventDefault();

				$("#dashboard_feed_info td[data-feed-id]").each(function() {
					$(this).click();
				});
			});
		}

		function enable_information() {

			$("#dashboard_feed_info").on('click', 'td[data-feed-id]', function() {
				var feed_id = $(this).data("feed-id");
				var redirect = $(this).data("feed-redirect");

				if (!feed_id)
					return;

				var column_latest_item 	= $(this).prev();
				var column_size			= column_latest_item.prev();
				var column_modification = column_size.prev();

				var data = {
					action: 'podlove-feed-info',
					feed_id: feed_id,
					redirect: redirect
				};

				column_latest_item.html('<i class="podlove-icon-spinner rotate"></i>');
				column_size.html('<i class="podlove-icon-spinner rotate"></i>');
				column_modification.html('<i class="podlove-icon-spinner rotate"></i>');

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						column_size.html(result.size);
						column_modification.html(result.last_modification);
						column_latest_item.html(result.latest_item);
					}
				});

			});
		}

		enable_validation();
		enable_information();

		// fetch missing data on page load
		$("#dashboard_feed_info [data-needs-validation]").each(function() {
			$(this).removeAttr('data-needs-validation').click();
		});

		return o;		
	}
}(jQuery));
var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Show Settings Screen.
 */
(function($) {
	PODLOVE.EpisodeAssetSettings = function(container) {
		// private
		var o = {};

		function make_asset_list_table_sortable() {
			$("table.episode_assets tbody").sortable({
				handle: '.reorder-handle',
				helper: function(event, el) {
					
					helper = $("<div></div>");
					helper.append( el.find(".title").html() );
					helper.css({
						width: $("table.episode_assets").width(),
						background: 'rgba(255,255,255,0.66)',
						boxSizing: 'border-box',
						padding: 5
					});

					return helper;
				},
				update: function( event, ui ) {
					// console.log(ui);
					var prev = parseFloat(ui.item.prev().find(".position").val()),
					    next = parseFloat(ui.item.next().find(".position").val()),
					    new_position = 0;

					if ( ! prev ) {
						new_position = next / 2;
					} else if ( ! next ) {
						new_position = prev + 1;
					} else {
						new_position = prev + (next - prev) / 2
					}

					// update UI
					ui.item.find(".position").val(new_position);

					// persist
					var data = {
						action: 'podlove-update-asset-position',
						asset_id: ui.item.find(".asset_id").val(),
						position: new_position
					};

					$.ajax({ url: ajaxurl, data: data, dataType: 'json'	});
				}
			});
		}

		function filter_file_formats_by_asset_type() {
			$('select[name=podlove_episode_asset_type]', container).on('change', function() {
				var $container = $(this).closest('table');
			
				$("#option_storage option").remove().appendTo($("#podlove_episode_asset_file_type_id"));
				$("#podlove_episode_asset_file_type_id option[data-type!='" + $(this).val() + "']").remove().appendTo($("#option_storage"));
				$('select[name*=file_type_id]').change();
			}).change();
		}

		function slugify(text) {

			text = text.trim();
			// replace non letter or digits by -
			text = text.replace(/[^-\w\.\~]/g, '-');
			text = text.toLowerCase();

			return text ? text : 'n-a';
		}

		// set default asset title
		function generate_default_episode_asset_title() {
			$('select[name*=file_type_id]', container).on('change', function() {
				var $container = $(this).closest('table');
				var $title = $container.find('[name*="title"]');
				var $name = $container.find('[name*="name"]');
				var fileFormatTitle = $("option:selected", this).data('name');
				var isCreateAction = ($container.closest("form").find("input[name='action']").val() === 'create');

				if (!fileFormatTitle)
					return;

				// only prefill on unsaved assets
				if (!isCreateAction)
					return;

				$title.val($("option:selected", this).data('name'));
				$name.val(slugify($("option:selected", this).data('name')));
			});
		}

		function generate_live_preview() {
			// handle preview updates
			$('input[name*="url_template"]', container).on( 'keyup', o.update_preview );
			$('input[name*="suffix"]', container).on( 'keyup', o.update_preview );
			$('#podlove_show_media_file_base_uri', container).on( 'keyup', o.update_preview );
			$('select[name="podlove_episode_asset_type"]', container).on( 'change', o.update_preview );
			$('[name*="file_type_id"]', container).on( 'change', o.update_preview );
			o.update_preview();
		}

		// public
		o.update_preview = function () {
			$('#url_preview', container).each(function() {
				var template = $("#url_template").html();
				var $preview = $("#url_preview");
				var $container = $(this).closest('table');

				var media_file_base_uri = $('#podlove_show_media_file_base_uri').val();
				var episode_slug        = '<span style="font-style:italic; font-weight:100">episode-slug</span>';
				var suffix              = $('input[name*="suffix"]').val();

				var selected_file_type  = $container.find('[name*="file_type_id"] option:selected').text();
				var format_extension    = $container.find('[name*="file_type_id"] option:selected').data('extension');

				if (!format_extension) {
					$preview.html('Please select file format');
					return;
				}

				template = template.replace( '%media_file_base_url%', '<span style="color:grey">' + media_file_base_uri );
				template = template.replace( '%episode_slug%', episode_slug + "</span>" );
				template = template.replace( '%suffix%', suffix );
				template = template.replace( '%format_extension%', format_extension );

				$preview.html(template);	
			});
		}

		generate_default_episode_asset_title();
		filter_file_formats_by_asset_type();
		generate_live_preview();
		make_asset_list_table_sortable();

		return o;
	};
}(jQuery));

(function($){
	var detect_duration = function(e) {
		var button = $("#podlove_detect_duration"),
		    status = $("#podlove_detect_duration_status")
		    url    = choose_asset_for_detection();

		var setStatusSuccess = function() {
			status.html('<i class="podlove-icon-ok"></i>');
		};

		var setStatusError = function(message) {
			status.html('<i class="podlove-icon-remove"></i> <em>' + message + '</em>');
		};

		var loader = PODLOVE.AudioDurationLoader({
			before: function() {
				status.html('<i class="podlove-icon-spinner rotate"></i>');
			},
			success: function(audio, event) {
				var duration;

				if (!audio || !audio.duration) {
					setStatusError("Could not determine duration (Error Code: #1)");
					return;
				}

				duration = PODLOVE.toDurationFormat(audio.duration);

				if (!duration) {
					setStatusError("Could not determine duration (Error Code: #2)");
					return;
				}
				
				$("#_podlove_meta_duration").val(duration);
				status.html('<i class="podlove-icon-ok"></i>');
			},
			error: function() {
				setStatusError("Could not determine duration (Error Code: #3)");
			}
		});
		
		if (url) {
			loader.load(url);
		} else {
			setStatusError("You need at least one validated media file.");
		}

		e.preventDefault();
	};

	var choose_asset_for_detection = function() {
		var urls = $(".media_file_row .url a")
			.map(function() {
				return $(this).attr("href");
			})
			.filter(function() {
				return this.match(/\.(mp3|m4a|ogg|oga|opus)$/);
			});

		return urls[0];
	};

	$(document).ready(function() {

		// inject detect-duration-button
		$(".row__podlove_meta_duration div input")
			.after(" <a href=\"#\" id=\"podlove_detect_duration\" class=\"button\">detect duration</a> <span id=\"podlove_detect_duration_status\"></span>");

		$("#podlove_podcast").on('click', '#podlove_detect_duration', detect_duration);
	});
}(jQuery));

var PODLOVE = PODLOVE || {};

(function($) {
	PODLOVE.License = function(settings) {
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

		var podlove_change_url_preview_and_name_from_form = function(version_value, modification_value, commercial_use_value, jurisdiction_value) {
			if (!version_value || !modification_value || !commercial_use_value || !jurisdiction_value )
				return;

			var $that = $(this);
			var data = {
				action: 'podlove-get-license-url',
				version: version_value,
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(settings.license_url_field_id).val(result);
					$(".podlove-license-link").attr("href", result);
				}
			});

			// Redifining the required AJAX action (for license name)
			data.action = 'podlove-get-license-name';

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(settings.license_name_field_id).val(result);
					$(".podlove-license-link").html(result);
					$(".podlove-license-link").attr("alt", result);
				}
			});

			$(".podlove_podcast_license_image").html(podlove_get_license_image(version_value, modification_value, commercial_use_value));
			$(".row_podlove_podcast_license_preview").show();
		};

		var podlove_get_license_image = function(version_value, modification_value, commercial_use_value) {
			if (version_value == 'cc0') {
				return '<img src="' + settings.plugin_url + '/images/cc/pd.png" alt="" />';
			} else if (version_value == 'pdmark') {
				return '<img src="' + settings.plugin_url + '/images/cc/pdmark.png" alt="" />';
			} else {
				return '<img src="' + settings.plugin_url + '/images/cc/' + podlove_license_cc_get_image(modification_value, commercial_use_value) + '.png" alt="" />';
			}
		};

		var podlove_filter_license_selector = function(license_version) {
			switch(license_version) {
				case 'cc3':
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction").closest('div').show();
				break;
				case 'cc4':
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use").closest('div').show();
					$("#license_cc_license_jurisdiction").closest('div').hide();
				break;
				default:
					$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction").closest('div').hide();
				break;
			}
		};

		var podlove_populate_license_form = function(version_value, modification_value, commercial_use_value, jurisdiction_value) {
			$("#license_cc_version").find('option[value=' + version_value + ']').attr('selected','selected');
			$("#license_cc_allow_modifications").find('option[value=' + modification_value + ']').attr('selected','selected');
			$("#license_cc_allow_commercial_use").find('option[value=' + commercial_use_value + ']').attr('selected','selected');
			$("#license_cc_license_jurisdiction").find('option[value=' + jurisdiction_value + ']').attr('selected','selected');

			podlove_filter_license_selector($("#license_cc_version").val());

			$(".podlove_podcast_license_image").html(podlove_get_license_image(version_value, modification_value, commercial_use_value));
			
			var data = {
				action: 'podlove-get-license-name',
				version: version_value,
				modification: modification_value,
				commercial_use: commercial_use_value,
				jurisdiction: jurisdiction_value
			};

			$.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'json',
				success: function(result) {
					$(".podlove-license-link").html(result);
					$(".podlove-license-link").attr('href', $("#podlove_podcast_license_url").val())
				}
			});

			if( $(settings.license_name_field_id).val() == '' || $(settings.license_url_field_id).val() == '' )
				$(".row_podlove_podcast_license_preview").hide();
		};

		$("#podlove_cc_license_selector_toggle").on( 'click', function() {
			$(this).find("._podlove_episode_list_triangle").toggle();
			$(this).find("._podlove_episode_list_triangle_expanded").toggle();
			$(".row_podlove_cc_license_selector").toggle();
		});

		$("#license_cc_version").on( 'change', function () {
			podlove_filter_license_selector($(this).val());
		} );

		$(settings.license_url_field_id).on( 'change', function() {
			if( $(this).val().indexOf('creativecommons.org') !== -1 ) {
				var data = {
					action: 'podlove-get-license-parameters-from-url',
					url: $(this).val()
				};

				$.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(result) {
						podlove_populate_license_form(
							result.version,
							result.modification,
							result.commercial_use,
							result.jurisdiction
						);
					}
				});
			} else {
				$(".podlove_podcast_license_image").html('');
				$(".podlove-license-link").html( $(settings.license_name_field_id).val() );
				$(".podlove-license-link").attr("href", $(this).val() );
			}
			$(".row_podlove_podcast_license_preview").show();
		});

		$(settings.license_name_field_id).on( 'change', function() {
			$(".podlove-license-link").html( $(this).val() );
			$(".row_podlove_podcast_license_preview").show();
		});

		$("#license_cc_allow_modifications, #license_cc_allow_commercial_use, #license_cc_license_jurisdiction, #license_cc_version").on( 'change', function() {
			podlove_change_url_preview_and_name_from_form(
				$("#license_cc_version").val(),
				$("#license_cc_allow_modifications").val(),
				$("#license_cc_allow_commercial_use").val(),
				$("#license_cc_license_jurisdiction").val()
			);
		});

		$(document).ready(function() {
			if( $(settings.license_name_field_id).val() !== '' || $(settings.license_url_field_id).val() !== '' )
				podlove_populate_license_form( settings.license.version, settings.license.modification, settings.license.commercial_use, settings.license.jurisdiction );

			if( $(settings.license_name_field_id).val() == '' || $(settings.license_url_field_id).val() == '' )
				$(".row_podlove_podcast_license_preview").hide();
		});
	}

}(jQuery));


var PODLOVE = PODLOVE || {};
PODLOVE.media = PODLOVE.media || {};

(function($) {
	"use strict";

	var args;
	
	PODLOVE.media.init =  function() {
		$(".podlove-media-upload-wrap").each(function() {
			PODLOVE.media.init_field($(this));
		});
	};

	PODLOVE.media.init_field = function(container) {
		var $upload_link = $(".podlove-media-upload", container),
		    options = $upload_link.data(),
			params  = {	
				frame:   options.frame,
				library: { type: options.type },
				button:  { text: options.button },
				className: options['class'],
				title: options.title
			}
		;
		
		if (typeof options.state != "undefined" ) params.state = options.state;
		
		options.input_target = $('#'+options.target);
		options.container = container;

		if (options.preview) {
			options.input_target.on("change", function() {
				PODLOVE.media.render_preview(options.container);
			});
		}

		// set size that is selected by default
		if (options.size) {
			wp.media.view.settings.defaultProps.size = options.size;
		}

		args = options;

		var file_frame = wp.media(params);
		
		file_frame.states.add([
			new wp.media.controller.Library({
				id:         'podlove_select_single_image',
				priority:   20,
				toolbar:    'select',
				filterable: 'uploaded',
				library:    wp.media.query( file_frame.options.library ),
				multiple:   false,
				editable:   true,
				displaySettings: true,
				allowLocalEdits: true
			}),
		]);
		
		file_frame.on('select update insert', function() { PODLOVE.media.insert(file_frame, options); });

		$upload_link.on('click', function() {
			file_frame.open();
		});

		container.on('click', '.podlove_reset_image', {options: options}, PODLOVE.media.reset);

		PODLOVE.media.render_preview(container);
	}

	PODLOVE.media.reset = function(e) {
		var options = e.data.options;

		options.container.find(".podlove_preview_pic").empty().hide();
		options.input_target.val("");
	};

	function get_gravatar(email) {
		if ( email.indexOf("@") == -1 ) {
			return email;
		} else {
			return 'https://www.gravatar.com/avatar/' + CryptoJS.MD5( email ) + '&s=400';

		}	
	}

	PODLOVE.media.render_preview = function(wrapper) {
		var preview  = $(".podlove_preview_pic", wrapper)[0],
		    $input   = $("input", wrapper).first(),
		    url      = $input.val();

	    if (args.allowGravatar) {
	    	url = get_gravatar(url);
	    }

		if (!url) {
			return;
		}

		$(".podlove_preview_pic", wrapper).empty().hide();

		var image = document.createElement('img');
		image.width = 300;
		image.src = url;

		var remove = document.createElement('button');
		remove.className = 'podlove_reset_image button';
		remove.appendChild(document.createTextNode('remove'));

		preview.appendChild(image);
		preview.appendChild(remove);
		preview.style.display = "block";
	};
	
	PODLOVE.media.insert = function(file_frame , options) {
		var state		= file_frame.state(), 
			selection	= state.get('selection').first().toJSON(),
			value		= selection.id,
			fetch_val   = typeof options.fetch != 'undefined' ? fetch_val = options.fetch : false
		
		/*fetch custom val like url*/
		if (fetch_val) {
			value = state.get('selection').map( function( attachment ) {
				var element = attachment.toJSON();
				
				if (fetch_val == 'url') {
					var display = state.display( attachment ).toJSON();
					
					if (element.sizes && element.sizes[display.size] && element.sizes[display.size].url) {
						return element.sizes[display.size].url;
					} else if (element.url) {
						return element.url;
					}
				}
			});
		}	
		
		// change the target input value
		options.input_target.val(value).trigger('change')
		
		// trigger event in case it is necessary (uploads)
		if (typeof options.trigger != "undefined") {
			$("body").trigger(options.trigger, [selection, options]);
		}
	}

	$(document).ready(function () {
		PODLOVE.media.init();
	});

})(jQuery);	 

var PODLOVE = PODLOVE || {};

(function($) {
	PODLOVE.ProtectFeed = function() {
		var $protection = $("#podlove_feed_protected"),
			$protection_row = $("tr.row_podlove_feed_protection_type"),
			$protection_type = $("#podlove_feed_protection_type"),
			$credentials = $("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user");

		var protectionIsActive = function() {
			return $protection.is(":checked");
		};

		var isCustomLogin = function() {
			return $protection_type.val() == "0";
		};

		if (protectionIsActive()) {
			$protection_row.show();
		}
		
		if (protectionIsActive() && isCustomLogin()) {
			$credentials.show();
		}

		$("#podlove_feed_protected").on("change", function() {
			if (protectionIsActive()) {
				$protection_row.show();
				if (isCustomLogin()) {
					$credentials.show();
				} 
			} else {
				$protection_row.hide();
				$credentials.hide();
			}
		});	

		$protection_type.change(function() {
			if (protectionIsActive() && isCustomLogin()) {
				$credentials.show();
			} else {
				$credentials.hide();
			}
		});
	}
}(jQuery));
var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Feed Settings Screen.
 */
(function($) {
	PODLOVE.FeedSettings = function(container) {
		// private
		var o = {};

		function make_feed_list_table_sortable() {
			$("table.feeds tbody").sortable({
				handle: '.reorder-handle',
				helper: function(event, el) {
					
					helper = $("<div></div>");
					helper.append( el.find(".title").html() );
					helper.css({
						width: $("table.feeds").width(),
						background: 'rgba(255,255,255,0.66)',
						boxSizing: 'border-box',
						padding: 5
					});

					return helper;
				},
				update: function( event, ui ) {
					// console.log(ui);
					var prev = parseFloat(ui.item.prev().find(".position").val()),
					    next = parseFloat(ui.item.next().find(".position").val()),
					    new_position = 0;

					if ( ! prev ) {
						new_position = next / 2;
					} else if ( ! next ) {
						new_position = prev + 1;
					} else {
						new_position = prev + (next - prev) / 2
					}

					// update UI
					ui.item.find(".position").val(new_position);

					// persist
					var data = {
						action: 'podlove-update-feed-position',
						feed_id: ui.item.find(".feed_id").val(),
						position: new_position
					};

					$.ajax({ url: ajaxurl, data: data, dataType: 'json'	});
				}
			});
		}

		function generate_slug_live_preview() {
			// handle preview updates
			$('#podlove_feed_slug', container).on( 'keyup', o.update_url_preview );
			o.update_url_preview();
		}

		function generate_title_live_preview() {
			// handle preview updates
			$('#podlove_feed_append_name_to_podcast_title', container).change( function () {
				o.update_title_preview();
			});
			$('#podlove_feed_name', container).change( function () {
				o.update_title_preview();
			});
			o.update_title_preview();
		}

		function manage_redirect_url_display() {
			var http_status = $("#podlove_feed_redirect_http_status").val();

			if (http_status > 0) {
				$(".row_podlove_feed_redirect_url").show();
			} else {
				$(".row_podlove_feed_redirect_url").hide();
			}
		}

		function slugify(text) {

			text = text.trim();
			// replace non letter or digits by -
			text = text.replace(/[^-\w\.\~]/g, '-');

			return text ? text : 'n-a';
		}

		// public
		o.update_url_preview = function () {
			// remove trailing slash
			var url = $("#feed_subscribe_url_preview").data('url').substr(0, $("#feed_subscribe_url_preview").data('url').length - 1);
			
			// remove slug if there is one
			if (url.substr(-4) !== "feed") {
				url = url.substr(0, url.lastIndexOf("/"));
			}

			var slug = slugify($("#podlove_feed_slug").val());
			var preview = ""

			if (slug == "n-a") {
				preview = "enter slug for preview"
			} else {
				preview = url + "/" + slug + "/"
			}

			$("#feed_subscribe_url_preview").html(preview);
		}

		o.update_title_preview = function () {
			if( $("#podlove_feed_append_name_to_podcast_title").prop('checked') ) {
				$("#feed_title_preview_append").html( ' (' + $("#podlove_feed_name").val() + ')' );
			} else {
				$("#feed_title_preview_append").html('');
			}
		}

		if ($("#feed_title_preview_append").length && $("#podlove_feed_append_name_to_podcast_title").length) {
			generate_title_live_preview();
		}

		if ($("#feed_subscribe_url_preview").length && $("#podlove_feed_slug").length) {
			generate_slug_live_preview();
		}

		$("#podlove_feed_redirect_http_status").on("change", function(){
			manage_redirect_url_display();
		});
		manage_redirect_url_display();
		make_feed_list_table_sortable();

		return o;
	};
}(jQuery));

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

var PODLOVE = PODLOVE || {};

// jQuery Tiny Pub/Sub
// https://github.com/cowboy/jquery-tiny-pubsub
(function($) {
	var o = $({});
	$.subscribe = function() {
		o.on.apply(o, arguments);
	};

	$.unsubscribe = function() {
		o.off.apply(o, arguments);
	};

	$.publish = function() {
		o.trigger.apply(o, arguments);
	};
}(jQuery));

PODLOVE.rtrim = function (string, thechar) {
	var re = new RegExp(thechar + "+$","g");
	return string.replace(re, '');
}

PODLOVE.untrailingslashit = function (url) {
	return PODLOVE.rtrim(url, '/');
}

PODLOVE.trailingslashit = function (url) {
	return PODLOVE.untrailingslashit(url) + '/';
}

PODLOVE.toDurationFormat = function (float_seconds) {
	var sec_num = parseInt(float_seconds, 10);
	var hours   = Math.floor(sec_num / 3600);
	var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
	var seconds = sec_num - (hours * 3600) - (minutes * 60);
	var milliseconds = Math.round((float_seconds % 1) * 1000);

	if (hours   < 10) {hours   = "0"+hours;}
	if (minutes < 10) {minutes = "0"+minutes;}
	if (seconds < 10) {seconds = "0"+seconds;}
	var time = hours+':'+minutes+':'+seconds;

	if (milliseconds) {
		time += '.' + milliseconds;
	};

	return time;
}

function human_readable_size(size) {
	if (!size || size < 1) {
		return "???";
	}

	var kilobytes = size / 1024;

	if (kilobytes < 500) {
		return kilobytes.toFixed(2) + " kB";
	}

	var megabytes = kilobytes / 1024
	return megabytes.toFixed(2) + " MB";
}

function convert_to_slug(string) {
	string = string.toLowerCase();
	string = string.replace(/\s+/g, '-');
	string = string.replace(/[\u00e4]/g, 'ae');
	string = string.replace(/[\u00f6]/g, 'oe');
	string = string.replace(/[\u00fc]/g, 'ue');
	string = string.replace(/[\u00df]/g, 'ss');
	string = string.replace(/[^\w\-]+/g, '');
	string = escape(string);
	return string;
}

function auto_fill_form(id, title_id) {
	(function($) {
		switch( id ) {
			case 'contributor':
				if( $("#podlove_contributor_publicname").val() == "" ) {
					if( $("#podlove_contributor_realname").val() == "" ) {
						$("#podlove_contributor_publicname").attr( 'placeholder', $("#podlove_contributor_nickname").val() );
					} else {
						$("#podlove_contributor_publicname").attr( 'placeholder', $("#podlove_contributor_realname").val() );
					}											
				}
			break;
			case 'contributor_group':
				if( $("#podlove_contributor_group_slug").val() == "" ) {
					$("#podlove_contributor_group_slug").val( convert_to_slug( $("#podlove_contributor_" + title_id).val() ) );
				}
			break;
			case 'contributor_role':
				if( $("#podlove_contributor_role_slug").val() == "" ) {
					$("#podlove_contributor_role_slug").val( convert_to_slug( $("#podlove_contributor_" + title_id).val() ) );
				}
			break;
		}

		
	}(jQuery));
}

/**
 * HTML-based input behavior for text fields.
 * 
 * To activate behavior, add class `podlove-check-input`.
 *
 * - trims whitespace from beginning and end
 *
 * Add these data attributes to add further behavior:
 *
 * - `data-podlove-input-type="url"`   : verifies against URL regex
 * - `data-podlove-input-type="avatar"`: verifies against URL or email regex
 * - `data-podlove-input-type="email"` : verifies against email regex
 * - `data-podlove-input-remove="@ +"` : removes given whitespace separated list of characters from input
 *
 * Expects HTML to be in the following form:
 *
 * ```html
 * <input type="text" id="inputid" class="podlove-check-input">
 * <span class="podlove-input-status" data-podlove-input-status-for="inputid"></span>
 * ```
 */
function clean_up_input() {
	(function($) {
		$(".podlove-check-input").on('change', function() {
			var textfield = $(this);
			var textfieldid = textfield.attr("id");
			var $status = $(".podlove-input-status[data-podlove-input-status-for=" + textfieldid + "]");

			textfield.removeClass("podlove-invalid-input");
			$status.removeClass("podlove-input-isinvalid");

			function ShowInputError(message) {
				$status.text(message);

				textfield.addClass("podlove-invalid-input");
				$status.addClass("podlove-input-isinvalid");
			}

			// trim whitespace
			textfield.val( textfield.val().trim() );

			// remove blacklisted characters
			if ( inputType = $(this).data("podlove-input-remove") ) {
				characters = $(this).data("podlove-input-remove").split(' ');
				$.each( characters, function(index, character) {
					textfield.val( textfield.val().replace(character, '') );
				} );
			}
			
			// handle special input types
			if ( inputType = $(this).data("podlove-input-type") ) {
				$status.text('');

				if ( $(this).val() == '' )
					return;

				switch(inputType) {
					case "url":
						valid_url_regexp = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i;

						if ( ! textfield.val().match(valid_url_regexp) ) {
							// Encode URL only if it is not already encoded
							if ( ! encodeURI( textfield.val() ).match(valid_url_regexp) ) {
								ShowInputError('Please enter a valid URL');
							} else {
								textfield.val( encodeURI( textfield.val() ) );
							}							
						}		 				
					break;
					case "avatar":
						if ( ! textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i) ) {
							// textfield.val( encodeURI( textfield.val() ) );

							if ( ! textfield.val().match(/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i) ) {
								ShowInputError('Please enter a valid email adress or a valid URL');
							}
						}
					break;
					case "email":
						if ( ! textfield.val().match(/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/i) )
							ShowInputError('Please enter a valid email adress.');
					break;
				}
			}	
		});
	}(jQuery));
}

/**
 * Initialize contextual help links.
 *
 *	Use like this:
 *
 *  <a href="#" data-podlove-help="help-tab-id">?</a>
 */
function init_contextual_help_links() {
	jQuery("a[data-podlove-help]").on("click", function (e) {
		var help_id = jQuery(this).data('podlove-help');

		e.preventDefault();

		// Remove 'active' class from all link tabs
		jQuery('li[id^="tab-link-"]').each(function(){
		    jQuery(this).removeClass('active');
		});

		// Hide all panels
		jQuery('div[id^="tab-panel-"]').each(function(){
		    jQuery(this).css('display', 'none');
		});

		// Set our desired link/panel
		jQuery('#tab-link-' + help_id).addClass('active');
		jQuery('#tab-panel-' + help_id).css('display', 'block');

		// Force click on the Help tab
		if (jQuery('#contextual-help-link').attr('aria-expanded') === "false") {
			jQuery('#contextual-help-link').click();
		}

		// Force scroll to top, so you can actually see the help
		window.scroll(0, 0);
	});
}

jQuery(function($) {

	$( "#_podlove_meta_recording_date" ).datepicker({ dateFormat: 'yy-mm-dd'});

	$("#dashboard_feed_info").each(function() {
		PODLOVE.DashboardFeedValidation($(this));
	});
	
	$("#asset_validation").each(function() {
		PODLOVE.DashboardAssetValidation($(this));
	});

	$("#podlove_podcast").each(function() {
		PODLOVE.Episode($(this));
	});

	$("#podlove_episode_assets, table.episode_assets").each(function() {
		PODLOVE.EpisodeAssetSettings($(this));
	});

	$(".wrap").each(function() {
		PODLOVE.FeedSettings($(this));
	});

	$(".row_podlove_feed_protected").each(function() {
		PODLOVE.ProtectFeed();
	});

	$(".autogrow").autogrow();

	$("#podlove_contributor_publicname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_realname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_nickname").change(function() {
		auto_fill_form('contributor', 'realname');
	});

	$("#podlove_contributor_group_title").change(function() {
		auto_fill_form('contributor_group', 'group_title');
	});

	$("#podlove_contributor_role_title").change(function() {
		auto_fill_form('contributor_role', 'role_title');
	});

	$(document).ready(function() {
		auto_fill_form('contributor', 'realname');
		clean_up_input();
		init_contextual_help_links();
	});
	
});

