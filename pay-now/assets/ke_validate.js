function ke_form_validate(formid) {
    $(formid).validate({
            onsubmit: true,
            errorClass: "error-validator",
            errorElement: "div",
            wrapper: "div",
            errorPlacement: function(error, element) {
                offset = element.offset();
                error.insertBefore(element)
                error.addClass('message-validator');
                error.css('position', 'absolute');
                error.css('left', $(element).position().left + $(element).width() - 5);
                error.css('top', $(element).position().top);
            }
    });
}


function ke_form_fix_phone(formid) {
                var preferred_country = $(formid+" input[name=preferred_country]").val();
                var setcountry = "";
                if ( (preferred_country == "") || (preferred_country == null) || (typeof preferred_country === 'undefined')){
                    setcountry = "au";
                }else{
                    setcountry = preferred_country;
                }

                var input = document.querySelector(formid+" input[name=phone]");
                    window.intlTelInput(input, ({ 
                    separateDialCode: true, preferredCountries:[setcountry, "id"]
                }));

                $(formid+' .intl-tel-input li').bind('click keydown keyup', function() {

                      var code = '+' + $(this).attr('data-dial-code');
                      var country = $(this).find('.country-name').text();
                      var e = $(this).closest('.intl-tel-input').find('#phone');
                      var completePhoneNumber = code + e.val();
                      var completePhoneNumberLsq = code + "-"+e.val();
                      $(formid+" input[name=Country]").val(country);
                      $(formid+" input[name=phonevalue]").val(completePhoneNumber);
                      $(formid+" input[name=PhoneFormatLsq]").val(completePhoneNumberLsq);

                });

                $(formid+" input[name=phone]").keyup(function() { 
                  var myLength = $(this).val().length;
                      if(myLength == 1)
                      {
                          if($(this).val() === '0')
                          {
                              $(this).val('');
                          }
                      }
                });

                $(formid+" input[name=phone]").bind('click keydown keyup change', function() {

                      var num = $(this).val();
                      var code = $(formid).find('.selected-dial-code').html();
                      $(formid+" input[name=phonevalue]").val(code + '' + num);
                      $(formid+" input[name=PhoneFormatLsq]").val(code + '-' + num);
                });

                $.fn.inputFilter = function(inputFilter) {
                  return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
                    if (inputFilter(this.value)) {
                      this.oldValue = this.value;
                      this.oldSelectionStart = this.selectionStart;
                      this.oldSelectionEnd = this.selectionEnd;
                    } else if (this.hasOwnProperty("oldValue")) {
                      this.value = this.oldValue;
                      this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
                    }
                  });
                };

                $(formid+" input[name=phone]").inputFilter(function(value) {
                  return /^\d*$/.test(value);
                });
}