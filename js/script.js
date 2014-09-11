'use strict';

var editForm = {

	editableTerms: ['start', 'end', 'place', 'getting_mean', 'backing_mean', 'notes'],

	attachEvents: function(){

		this.$days.children('.list-cell-edit').on('click', function(){
			editForm.edit($(this).parent().data('id'));
		});

		this.$fromCancel.click(this.hide);
	},

	edit: function(id){

		this.$form[0].reset();

		this.$fromId.val(id);

		var data = this.data[id];

		for(var term in this.editableTerms) {

			var termName = this.editableTerms[term],
				currentData = data[termName];

			if($.isPlainObject(currentData)) {

				var keys = Object.keys(currentData).join('|'),
					$checkbox = $('[name="' + termName + '[]"]').filter(function(){
						return this.value.match(keys);
					});

				$checkbox.prop('checked', true);
			}
			else
				this.$form.find('[name=' + termName + ']').val(currentData);
		}

		editForm.show();
	},

	hide: function(e){
		e.preventDefault();
		editForm.$form.hide();
	},

	init: function(){
		this.initComponents();
		this.attachEvents();
	},

	initComponents: function(){
		this.$form = $('#day-edit');
		this.$fromId = $('#de-id');
		this.$fromCancel = $('#de-cancel');
		this.$days = $('.day-row');
	},

	show: function(){
		this.$form.show();
	}

};

$(function(){

	editForm.init();

	var $mainTable = $('#main-table');

	$mainTable.tableScroll();

	var $tsHead = $('.ts-head'),
		tsHeight = $tsHead.height(),
		$tsBody = $('.ts-body'),
		$window = $(window);

	$window.on('scroll', function(){

		var tsBodyTop = $tsBody.offset().top - $window.scrollTop() - tsHeight;

		if(tsBodyTop < 0)
			$tsHead.css({
				position: 'fixed',
				top: 0
			});
		else
			$tsHead.css({
				position: 'static'
			});
	})
});

$.fn.tableScroll = function(height){
	var table = $(this),
		dir = table.css('direction'),
		wrapper = $('<div>').css({
			direction: dir == 'rtl' ? 'ltr' : 'rtl',
			height: height,
			overflowX: 'hidden',
			overflowY: 'auto'
		});

	table.wrap('<div class="table-scroll-wrapper"></div>');

	table.css('float', 'left').addClass('table-scroll').wrap(wrapper.addClass('ts-body')).attr('original-width', table.width());

	table.find('th, tbody tr:first td').each(function(){
		$(this).attr('original-width', $(this).width())
	});

	var newTable = table.clone();

	table.find('thead tr').hide();

	newTable.children('tbody').empty();

	table.parent().parent().prepend(newTable);

	newTable.wrap($('<div>').height(newTable.height()).addClass('ts-head'));

	newTable.width(newTable.attr('original-width')).removeAttr('id');

	newTable.find('th').each(function(){
		$(this).width($(this).attr('original-width'));
	});

	table.css({
		width: table.attr('original-width'),
		direction: dir
	});

	table.find('tbody tr:first td').each(function(){
		$(this).width($(this).attr('original-width'));
	});

	return table;
};