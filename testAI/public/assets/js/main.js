
 document.addEventListener('DOMContentLoaded', function() {
        // Инициализация маски для всех полей с классом phone-mask
        const phoneInputs = document.querySelectorAll('.phone-mask');

        phoneInputs.forEach(input => {
            Inputmask({
                mask: '+7 (999) 999-9999',
                placeholder: '_',
                showMaskOnHover: false,
                showMaskOnFocus: true,
                clearIncomplete: true,
            }).mask(input);
        });
    });
