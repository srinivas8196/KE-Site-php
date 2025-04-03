function ke_form_post(formid) {

	$($this+' #fcountry').val('Australia');

    var isvalid = $(formid).valid();

            /*var fname = $(formid+" input[name=Name_First]").val();
            var lname = $(formid+" input[name=Name_Last]").val();
            var email = $(formid+" input[name=Email]").val();
            var phone = $(formid+" input[name=phonevalue]").val();
            var maritalstatus = $(formid+" select[name=Marital_status] option:selected").val();
            var yearofbirth = $(formid+" select[name=Year_of_Birth] option:selected").val();
            var credit_card_type = $(formid+" select[name=Credit_Card_Type] option:selected").val();

            var adult = $(formid+" input[name=Adult]").val();
            var children = $(formid+" input[name=Children]").val();
            var destination = $(formid+" select[name=Destination] option:selected").val();
            var arrival_date = $(formid+" input[name=arrival_date]").val();
            var departure_date = $(formid+" input[name=departure_date]").val();

            var lead_country = $(formid+" input[name=Country]").val();
            var brand = $(formid+" input[name=Brand]").val();
            var lead_sub_brand = $(formid+" input[name=Lead_Sub_Brand]").val();
            var lead_source = $(formid+" input[name=Lead_Source]").val();
            var lead_source_description = $(formid+" input[name=Lead_Source_Description]").val();
            var lead_regions = $(formid+" input[name=Lead_Regions]").val();
            var lead_locations = $(formid+" input[name=Lead_Location]").val();


            var leadstatus = "";

            if ((yearofbirth >= 1955) && (yearofbirth <= 1985) && (maritalstatus == "Married") && (credit_card_type != "")) {
              leadstatus = "Hot Prospect";
            }else{
              leadstatus = "Not Qualified";
            }
            
            var zohoData = {
                data : {
                  'First_Name': fname,
                  'Last_Name': lname,
                  'Email': email,
                  'Phone': phone,
                  'Year_of_Birth': yearofbirth,
                  'Marital_Status': maritalstatus,
                  'Adult': adult,
                  'Children': children,
                  'Destination': destination,
                  'Arrival_Date': arrival_date,
                  'Departure_Date': departure_date,
                  'Brand': brand,
                  'Lead_Sub_Brand': lead_sub_brand,
                  'Lead_Source': lead_source,
                  'Lead_Source_Description': lead_source_description,
                  'Lead_Locations':['"'+lead_locations+'"'],
                  'Lead_Regions': lead_regions,
                  'Country': lead_country,
                  'Lead_Status': leadstatus
                }
            };

            console.log(zohoData);*/

            if (isvalid) {
                
                			console.log('validated!');

                              var numberfix = $("#detailphonesidebar").val();

                              if ( numberfix.length >= 9 && numberfix.length <= 15 ){

                                $("label.sbm").css('display', 'block');

                                $("button.btnonpost").hide();

                                $(this).attr("disabled", true);

                                $(this).prop('value', 'Sedang memproses...');

                                ga('send', 'pageview', '/bgidls/');

                                console.log('length= '+numberfix.length)

                                $(formid).submit();

                                return false


                              }else{

                                $(this).attr("disabled", false);
                                
                                $('.phoneMsg').text('Nomor tidak valid!');

                                console.log('length= '+numberfix.length)

                                return false
                                
                              }
                        
            }else{
                console.log('not valid!!!!!')
            }

            return false;
}