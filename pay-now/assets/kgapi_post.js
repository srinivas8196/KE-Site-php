function ke_form_post(formid) {
    var isvalid = $(formid).valid();

            var fname = $(formid+" input[name=Name_First]").val();
            var lname = $(formid+" input[name=Name_Last]").val();
            var email = $(formid+" input[name=Email]").val();
            var phone = $(formid+" input[name=phonevalue]").val();
            var maritalstatus = $(formid+" select[name=Marital_status] option:selected").val();
            //var yearofbirth = $(formid+" select[name=Year_of_Birth] option:selected").val();
            var yearofbirth = $(formid+" input[name=Year_of_Birth] option:selected").val();
            var credit_card_type = $(formid+" select[name=Credit_Card_Type] option:selected").val();

            var Occupation = $(formid+" input[name=Occupation]").val();
            var Age = $(formid+" input[name=Age]").val();

            var adult = $(formid+" input[name=Adult]").val();
            var children = $(formid+" input[name=Children]").val();
            var destination = $(formid+" select[name=Destination] option:selected").val();
            var arrival_date = $(formid+" input[name=arrival_date]").val();
            var departure_date = $(formid+" input[name=departure_date]").val();
            var Enquiry_sent_to = "data.leads@karmagroup.com";

            var subject = $(formid+" input[name=Subject]").val();

            var additionaldate2 = $(formid+" input[name=additionaldate2]").val();
            var additionaldate3 = $(formid+" input[name=additionaldate3]").val();


            var message = $(formid+" textarea[name=Message]").val();

            if ((typeof message === "undefined") && (typeof additionaldate2 === "undefined") && (typeof additionaldate3 === "undefined")){
              var messagemerge = '';
            }else{
              var messagemerge = message + "\nAdditional Info:\n" + additionaldate2 + "\n" + additionaldate3;
            }

            var lead_country = $(formid+" input[name=Country]").val();
            var brand = $(formid+" input[name=Brand]").val();
            var lead_sub_brand = $(formid+" input[name=Lead_Sub_Brand]").val();
            var lead_source = $(formid+" input[name=Lead_Source]").val();
            var lead_source_description = $(formid+" input[name=Lead_Source_Description]").val();
            var lead_regions = $(formid+" input[name=Lead_Regions]").val();
            var lead_locations = $(formid+" input[name=Lead_Location]").val();

            var Redirect_URL = $(formid+" input[name=Redirect_URL]").val();
            var redirectnow = "";
            var preferred_country = $(formid+" input[name=preferred_country]").val();

            var choosenHotel = $(formid+" select[name=Choosen_Hotel] option:selected").val();

            var current_URL = window.location.href;
            console.log(current_URL);

            if ((Redirect_URL == "") || (Redirect_URL == null) || (typeof Redirect_URL === 'undefined')){
                redirectnow = "https://karmaexperience.com/thanks/";
            }else{
                redirectnow = Redirect_URL;
            }


            var leadstatus = "";


            if (preferred_country == "id"){

                  if (typeof yearofbirth != 'undefined'){

                      if ( (yearofbirth >= 1955) && (yearofbirth <= 1990) && ( (maritalstatus == "Married") || (maritalstatus == "Living Together") ) && (credit_card_type != "") ) {
                        leadstatus = "Hot Prospect";
                      }else{
                        leadstatus = "Not Qualified";
                      }
                  }

                  if (typeof Age != 'undefined'){

                      if ( ( Age == "35 - 65" ) && (maritalstatus == "Married") && (credit_card_type != "") ){
                        leadstatus = "Hot Prospect";
                      }else{
                        leadstatus = "Not Qualified";
                      }
                  }

            }else{

                  if ( (yearofbirth >= 1955) && (yearofbirth <= 1990) && ( (maritalstatus == "Married") || (maritalstatus == "Living Together") )  ) {
                    leadstatus = "Hot Prospect";
                  }else{
                    leadstatus = "Not Qualified";
                  }

            }

            
            
            var zohoData = {
                data : {
                  'First_Name': fname,
                  'Last_Name': lname,
                  'Email': email,
                  'Phone': phone,
                  'Occupation': Occupation,
                  'Year_of_Birth': yearofbirth,
                  'Marital_Status': maritalstatus,
                  'Card_Type' : credit_card_type,
                  'Adult': adult,
                  'Age_Range': Age,
                  'Children': children,
                  'Subject': subject,
                  'Messages': messagemerge,
                  'Destination': destination,
                  'Arrival_Date': arrival_date,
                  'Departure_Date': departure_date,
                  'Enquiry_sent_to': Enquiry_sent_to,
                  'Brand': brand,
                  'Lead_Sub_Brand': lead_sub_brand,
                  'Lead_Source': lead_source,
                  'Lead_Source_Description': lead_source_description,
                  'Lead_Locations':[lead_locations],
                  'Lead_Regions': lead_regions,
                  'Country': lead_country,
                  'Lead_Status': leadstatus,
                  'Preferred_Destination': choosenHotel,
                  'Website': current_URL
                }
            };

            console.log(zohoData);

            if (isvalid) {
              console.log("msg:"+message);

                  if ( (message == "") || (message == null) || (typeof message === 'undefined') ){

                              if ( phone.length >= 7 && phone.length <= 15 ){

                                $("label.sbm").css('display', 'block');

                                $("button.btnonpost").hide();

                                $('.ajaxloader').show();

                                console.log('length= '+phone.length)

                                          console.log('valid, ready to post');

                                          checkTokenAvailability('GeneralForm_KE', function(token) {
                                
                                            $.ajax({
                                              method: "POST",
                                              url: "https://api.karmagroup.com/zoho/v2/records/Leads",
                                              data: JSON.stringify(zohoData),
                                              headers : {
                                                'Content-Type': 'application/json',
                                                'Authorization': token
                                              }
                                            })
                                            .done(function( response ) {
                                              console.log(response);
                                              parent.location = redirectnow;
                                              
                                            })
                                            .error(function(error){
                                              console.log(error);
                                              //alert('Ops.. Error! Please contact via contact us page!');
                                              parent.location = redirectnow;
                                            })

                                          })

                                return false


                              }else{

                                $(this).attr("disabled", false);
                                
                                $('.phoneMsg').text('Number is not valid!');

                                console.log('length= '+phone.length)

                                return false
                                
                              }

                }else{

                  console.log("post ready to send!");

                                $("label.sbm").css('display', 'block');

                                $("button.btnonpost").hide();

                                $('.ajaxloader').show();

                                  checkTokenAvailability('GeneralForm_KE', function(token) {
                                
                                            $.ajax({
                                              method: "POST",
                                              url: "https://api.karmagroup.com/zoho/v2/records/Leads",
                                              data: JSON.stringify(zohoData),
                                              headers : {
                                                'Content-Type': 'application/json',
                                                'Authorization': token
                                              }
                                            })
                                            .done(function( response ) {
                                              console.log(response);
                                              parent.location = "https://karmaexperience.com/thanks/";
                                              
                                            })
                                            .error(function(error){
                                              //alert('Ops.. Error! Please contact via contact us page!');
                                              parent.location = redirectnow;
                                            })

                                          })
                }

                
                        
            }else{
                console.log('not valid!!!!!')
            }

            return false;
}