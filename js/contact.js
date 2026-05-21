$(document).ready(function() {
	var $form = $('#contact-form');
	var $button = $form.find('button[type="submit"]');

	function fieldValue(field) {
		return $.trim(field && field.value ? field.value : '');
	}

	function getField(form, names) {
		var field = null;

		$.each(names, function(index, name) {
			field = form.elements[name] || document.getElementById(name);
			return !field;
		});

		return field;
	}

	function setFilledState() {
		$form.find('.form-control').each(function() {
			$(this).toggleClass('input-filled', fieldValue(this) !== '');
		});
	}

	function addFieldError(field, message) {
		var $field = $(field);
		$field.next('label')
			.append('<span class="error-message" style="display:none;">' + message + '.</span>')
			.find('.error-message')
			.fadeIn('fast');
	}

	function showMessage(message) {
		$form.find('.contact-form-message').remove();
		$form.append('<p class="contact-form-message">' + message + '</p>');
	}

	function isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
	}

	setFilledState();

	$form.find('.form-control').on('input blur', function() {
		$(this).toggleClass('input-filled', fieldValue(this) !== '');
	});

	$form.find('.form-control').on('focus', function() {
		$(this).parent('.controls').find('.error-message').fadeOut(300, function() {
			$(this).remove();
		});
	});

	$form.on('submit', function(event) {
		event.preventDefault();

		if ($form.hasClass('clicked')) {
			return;
		}

		var form = this;
		var fields = {
			nome: getField(form, ['nome', 'txtNome', 'name']),
			email: getField(form, ['email', 'txtEmail']),
			mensagem: getField(form, ['mensagem', 'txtMsg', 'message'])
		};
		var errorMessage = $button.data('error-message');
		var sendingMessage = $button.data('sending-message');
		var okMessage = $button.data('ok-message');
		var hasError = false;

		$form.find('.error-message,.contact-form-message').remove();

		if (fieldValue(fields.nome) === '') {
			addFieldError(fields.nome, $(fields.nome).data('error-empty'));
			hasError = true;
		}

		if (fieldValue(fields.email) === '') {
			addFieldError(fields.email, $(fields.email).data('error-empty'));
			hasError = true;
		} else if (!isValidEmail(fieldValue(fields.email))) {
			addFieldError(fields.email, $(fields.email).data('error-invalid'));
			hasError = true;
		}

		if (fieldValue(fields.mensagem) === '') {
			addFieldError(fields.mensagem, $(fields.mensagem).data('error-empty'));
			hasError = true;
		}

		if (hasError) {
			showMessage(errorMessage);
			return;
		}

		if (typeof grecaptcha === 'undefined' || grecaptcha.getResponse() === '') {
			showMessage('Por favor, confirme que você não é um robô.');
			return;
		}

		if (window.location.protocol === 'file:') {
			showMessage('Abra o site por um servidor local para testar o envio: php -S 127.0.0.1:8080.');
			return;
		}

		$form.addClass('clicked');
		showMessage('<i class="fa fa-spinner fa-pulse"></i>' + sendingMessage);

		$.ajax({
			type: 'POST',
			url: $form.attr('action'),
			data: $form.serialize()
		}).done(function() {
			showMessage(okMessage);
			$form[0].reset();
			setFilledState();
			if (typeof grecaptcha !== 'undefined') {
				grecaptcha.reset();
			}
		}).fail(function(xhr) {
			var responseMessage = $.trim(xhr.responseText || '');
			showMessage(responseMessage || 'Nao foi possivel enviar a mensagem.');
		}).always(function() {
			$form.removeClass('clicked');
		});
	});
});
