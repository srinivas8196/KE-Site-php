function removeLeadingZeros(code,numb){
    numb=numb.replace(/^[0]+/g,"");
    let code2=code.substring(1);
    let length=0;
    if(numb.startsWith(code)){
        length=code.length;
    }else if(numb.startsWith(code2)){
        length=code2.length;
    }
    numb=numb.substring(length);
    return (code+numb);
}

function setphoneByID(idphoneinput, lang){
    var input = document.querySelector(idphoneinput);
    var setlang = Array();
    if (lang == "au"){
        setlang = ['au','nz','id'];
    }else if (lang == "gb"){
        setlang = ['gb','nz','au','id','in'];
    }else if (lang == "custom"){
        setlang = ['au', 'nz', 'gb', 'vn', 'id'];
    }else if (lang == "in"){
        setlang = ['in', 'au', 'nz'];
    }else if (lang == "sg"){
        setlang = ['sg','in', 'au', 'nz'];
    }else if (lang == "us"){
        setlang = ['us','in', 'au', 'nz'];
    }else{
        setlang = ['id','au','nz'];
    }

    window.intlTelInput(input, ({
        separateDialCode: true,
        preferredCountries: setlang
    }));

    jQuery('.intl-tel-input li').on('click keydown keyup', function() {
        let code = '+' + $(this).attr('data-dial-code');
        let country = $(this).find('.country-name').text();
        let e = $(this).closest('.intl-tel-input').find(idphoneinput);
        let completePhoneNumber = removeLeadingZeros(code,e.val());
        $("input[name=SingleLine16]").val(country);
        $("input[name=Country]").val(country);
        $("input[name=surveyCtry]").val(country);
        $("input[name=SingleLine]").val(completePhoneNumber);
        let PhoneFormatLsq = removeLeadingZeros(code,"-"+e.val());
        $("#PhoneFormatLsq").val(PhoneFormatLsq);
        $("#phonevalue").val(completePhoneNumber);
    });

    $('.mob2 .intl-tel-input li').on('click keydown keyup', function() {
        let code = '+' + $(this).attr('data-dial-code');
        let country = $(this).find('.country-name').text();
        let e = $(this).closest('.intl-tel-input').find(idphoneinput);
        let completePhoneNumber = removeLeadingZeros(code,e.val());
        $("input[name=SingleLine16]").val(country);
        $("input[name=Country]").val(country);
        $("input[name=SingleLine]").val(completePhoneNumber);
        let PhoneFormatLsq = removeLeadingZeros(code,"-"+e.val());
        $("#PhoneFormatLsq2").val(PhoneFormatLsq);
        $("#phonevalue2").val(completePhoneNumber);
    });
    $(idphoneinput).on("click keyup", function() {
        let getselected = document.querySelector(".selected-flag").getAttribute("title");
        let country = getselected.split(":");

        var num = $(this).val();
        var code = $('form').find('.selected-dial-code').html();
        let completePhoneNumber = removeLeadingZeros(code,num);
        $("input[name=SingleLine]").val(completePhoneNumber);

        let PhoneFormatLsq = removeLeadingZeros(code,"-"+num);
        $("#PhoneFormatLsq").val(PhoneFormatLsq);
        $("#phonevalue").val(completePhoneNumber);

        $("#PhoneFormatLsq2").val(PhoneFormatLsq);
        $("#phonevalue2").val(completePhoneNumber);

        $("input[name=Country]").val(country[0]);
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
    $(idphoneinput).keyup(function() {
        var myLength = $(idphoneinput).val().length;
        if(myLength == 1) {
            if($(this).val() === '0') {
                $(this).val('');
            }
        }
    });
    $(idphoneinput).inputFilter(function(value) {
        return /^\d*$/.test(value);
    });
}

function setphoneByID_CustomForm(idphoneinput, lang, formzoho){
    var input = document.querySelector(idphoneinput);
    var setlang = Array();
    if (lang == "au"){
        setlang = ['au','nz','id'];
    }else{
        setlang = ['id','au','nz'];
    }
    window.intlTelInput(input, ({
        separateDialCode: true,
        preferredCountries: setlang
    }));

    $('.intl-tel-input li').on('click keydown keyup', function() {
        let code = '+' + $(this).attr('data-dial-code');
        let country = $(this).find('.country-name').text();
        let e = $(this).closest('.intl-tel-input').find(idphoneinput);
        let completePhoneNumber = removeLeadingZeros(code,e.val());
        if (formzoho == "form_big_list"){
            $("input[name=SingleLine16]").val(country);
            $("input[name=SingleLine]").val(completePhoneNumber);
        }else if (formzoho == "form_timeshare_arrival_reminder"){
            $("input[name=SingleLine12]").val(country);
            $("input[name=SingleLine2]").val(completePhoneNumber);
        }
        
    });
    $(idphoneinput).on("click keyup", function() {
        var num = $(this).val();
        var code = $('form').find('.selected-dial-code').html();
        let completePhoneNumber = removeLeadingZeros(code,num);
        if (formzoho == "form_big_list"){
            $("input[name=SingleLine]").val(completePhoneNumber);
        }else if (formzoho == "form_timeshare_arrival_reminder"){
            $("input[name=SingleLine2]").val(completePhoneNumber);
        }
        
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
    $(idphoneinput).keyup(function() {
        var myLength = $(idphoneinput).val().length;
        if(myLength == 1) {
            if($(this).val() === '0') {
                $(this).val('');
            }
        }
    });
    $(idphoneinput).inputFilter(function(value) {
        return /^\d*$/.test(value);
    });
}

function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function() {
        console.log('Async: Copying to clipboard was successful!');
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
    });
}

function setphoneByIDSurvey(idphoneinput, lang){
    var input = document.querySelector(idphoneinput);
    var setlang = Array();
    if (lang == "au"){
        setlang = ['au','nz','id'];
    }else if (lang == "gb"){
        setlang = ['gb','nz','au','id','in'];
    }else if (lang == "custom"){
        setlang = ['au', 'nz', 'gb', 'vn', 'id'];
    }else if (lang == "in"){
        setlang = ['in', 'au', 'nz'];
    }else if (lang == "sg"){
        setlang = ['sg','in', 'au', 'nz'];
    }else if (lang == "us"){
        setlang = ['us','in', 'au', 'nz'];
    }else{
        setlang = ['id','au','nz'];
    }

    window.intlTelInput(input, ({
        separateDialCode: true,
        preferredCountries: setlang
    }));

    $('.intl-tel-input li').on('click keydown keyup', function() {
        let code = '+' + $(this).attr('data-dial-code');
        let country = $(this).find('.country-name').text();
        let e = $(this).closest('.intl-tel-input').find(idphoneinput);
        let completePhoneNumber = removeLeadingZeros(code,e.val());
        $("#surveyCtry").val(country);
        let PhoneFormatLsq = removeLeadingZeros(code,"-"+e.val());
        $("#PhoneFormatLsq").val(PhoneFormatLsq);
        $("#phonevalue").val(completePhoneNumber);
    });

    $(idphoneinput).on("click keyup", function() {
        let getselected = document.querySelector(".selected-flag").getAttribute("title");
        let country = getselected.split(":");
        var num = $(this).val();
        var code = $('form').find('.selected-dial-code').html();
        let completePhoneNumber = removeLeadingZeros(code,num);
        let PhoneFormatLsq = removeLeadingZeros(code,"-"+num);
        $("#PhoneFormatLsq").val(PhoneFormatLsq);
        $("#phonevalue").val(completePhoneNumber);
        $("#surveyCtry").val(country[0]);
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
    $(idphoneinput).keyup(function() {
        var myLength = $(idphoneinput).val().length;
        if(myLength == 1) {
            if($(this).val() === '0') {
                $(this).val('');
            }
        }
    });
    $(idphoneinput).inputFilter(function(value) {
        return /^\d*$/.test(value);
    });
}