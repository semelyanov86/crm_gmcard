AdvanceFilter_Owner_Field_Js('AdvanceFilter_Owner_Field_Js',{},{

	getUi : function(){
		var comparatorSelectedOptionVal = this.get('comparatorElementVal');
		if(comparatorSelectedOptionVal == 'e' || comparatorSelectedOptionVal =='n'){
			var html = '<select class="select2 inputElement row-fluid" multiple name="'+ this.getName() +'[]">';
			var pickListValues = this.getPickListValues();
			var selectedOption = app.htmlDecode(this.getValue());
			var selectedOptionsArray = selectedOption.split(',')

			html += '<option value="0" ';
			if(jQuery.inArray('Logged User',selectedOptionsArray) != -1){
				html += ' selected ';
			}
			html += '>Logged User</option>';

			for(var optGroup in pickListValues){
				html += '<optgroup label="'+optGroup+'">'
				var optionGroupValues = pickListValues[optGroup];
				for(var option in optionGroupValues) {
					html += '<option value="'+option+'" ';
					//comparing with the value instead of key , because saved value is giving username instead of id.
					if(jQuery.inArray(jQuery.trim(app.htmlDecode(optionGroupValues[option])),selectedOptionsArray) != -1){
						html += ' selected ';
					}
					html += '>'+optionGroupValues[option]+'</option>';
				}
				html += '</optgroup>'
			}

			html +='</select>';
			var selectContainer = jQuery(html);
			this.addValidationToElement(selectContainer);
			return selectContainer;
		} else {
			var selectedOption = this.getValue();
			var pickListValues = this.getPickListValues();
			var tagsArray = new Array();
			jQuery.each( pickListValues, function(groups, blocks) {
				jQuery.each(blocks,function(i,e){
					tagsArray.push(jQuery.trim(app.htmlDecode(e)));
				})
			});
			var html = '<input data-tags="'+tagsArray +'" type="hidden" class="row-fluid col-lg-12 select2" name="'+ this.getName() +'">';
			var selectContainer = jQuery(html).val(selectedOption);
			selectContainer.data('tags', tagsArray);
			this.addValidationToElement(selectContainer);
			return selectContainer;
		}
	}
});