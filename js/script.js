function loadXML(){
	if( $('#xml-file').get(0).files.length > 0 ) {
		var formdata = new FormData($('#xml-form').get(0));
		$('.my-button').css('display', 'none');
		$('.log-txt-unfound').html('');
        $('.log-txt-zeroing').html('');
		notification('Обработка файла...', 'black');
		$.ajax({
			url: $('#xml-form').attr('action'),
			type: 'POST',
			data: formdata,
			processData: false,
			contentType: false,
			dataType: "json",
			success: function( respond ){
				if(respond === null) {
					notification('Ошибка обработки запроса!', 'red');
				} else if(respond['success']){
					notification(respond['success'], 'green');
					// if(respond['unfound']){
					// 	$('.log-txt-unfound').css('display', 'inline-block');
					// 	$('.log-txt-unfound').html(respond['unfound']);
					// }
                    // if(respond['zeroing']){
                    //     $('.log-txt-zeroing').css('display', 'inline-block');
                    //     $('.log-txt-zeroing').html(respond['zeroing']);
                    // }
					$('#xml-form')[0].reset();
				} else if(respond['error']) {
					notification(respond['error'], 'red');
				}
                $('.my-button').css('display', 'inline-block');
			},
			error: function( respond ){
				console.log(respond);
				notification('Error: Ошибка обработки запроса!', 'red');
                $('.my-button').css('display', 'inline-block');
			},
			complete: function() {
				$('.my-button').css('display', 'inline-block');
			}
		});
	} else {
		notification('Выберите файл!', 'red');
	}
}

function notification(mess, color) {
	$('.notification').html(mess);
	$('.notification').css("color", color);
}