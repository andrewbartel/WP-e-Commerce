(function($){
	var collapse_item = function(item) {
		item.addClass('collapsed').hide(150);

		var element = item, level, current_level = item.data('level'), id;
		while (element.size() > 0) {
			element = element.prev();
			if (element.data('level') < current_level) {
				item.data('parent', element.data('id'));
				element.attr('rel', element.data('id')); // makes it easier to select using attribute
				break;
			}
		}
	};

	var restrict_sortable_within_same_level = function(item, table) {
		var current_level = item.data('level'), element, level;
		table.find('.sortable').removeClass('sortable');
		item.addClass('sortable');

		element = item;

		while (element.size() > 0) {
			element = element.prev();
			level = element.data('level');
			if (level < current_level) {
				break;
			} else if (level > current_level) {
				collapse_item(element);
			} else if (level == current_level) {
				element.addClass('sortable');
			}
		}

		element = item;
		while (element.size() > 0) {
			element = element.next();
			level = element.data('level');
			if (level < current_level) {
				break;
			} else if (level > current_level) {
				collapse_item(element);
			} else if (level == current_level) {
				element.addClass('sortable');
			}
		}

		table.sortable('refresh');
		table.find('.alternate').removeClass('alternate');
		table.find('tbody tr:not(.collapsed):even').addClass('alternate');
	};

	var event_sort_start = function(e, ui) {
		var colspan = ui.item.find('th:visible, td:visible').size();

		restrict_sortable_within_same_level(ui.item, $(this));
		ui.placeholder.html('<td colspan="' + colspan + '"></td>');
	};

	var event_sort_stop = function(e, ui) {
		var t = $(this);
		t.find('tbody tr').addClass('sortable');
		t.find('tr.collapsed').each(function(){
			var item = $(this),
				parent = item.siblings('[rel="' + item.data('parent') + '"]');
			item.insertAfter(parent).removeClass('collapsed').show(150);
		});
		t.find('.alternate').removeClass('alternate');
		t.find('tbody tr:even').addClass('alternate');
	};

	$.fn.wpsc_sortable_table = function(user_options) {
		var options = {
			update : function(){}
		};
		$.extend(options, user_options);

		$(this).each(function(){
			var t = $(this);
			t.find('tr').addClass('sortable');
			t.sortable({
				axix : 'y',
				items : 'tr.sortable',
				containment : t,
				placeholder : 'wpsc-sortable-table-placeholder',
				cursor : 'move',
				update : options.update,
				start : event_sort_start,
				stop : event_sort_stop
			});
		});
	};
})(jQuery);