<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destination Form</title>
    <style>
        .form-container {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            /* Align items to the top */
            height: 100vh;
            position: sticky;
            /* Ensure the form-container is sticky */
            top: 0;
            z-index: 1000;
        }

        .form-container form {
            background: #fff;
            padding: 15px;
            /* Reduced padding */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: fit-content;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            /* Enable flexbox wrapping */
        }

        .form-container .form-group {
            margin-bottom: 10px;
            /* Reduced margin */
            width: 48%;
            /* Set width for two columns */
            margin-right: 2%;
            /* Add space between columns */
        }

        .form-container .form-group:nth-child(2n) {
            margin-right: 0;
            /* Remove right margin for even items */
        }

        .form-container .form-group label {
            display: block;
            margin-bottom: 3px;
            /* Reduced margin */
            font-weight: normal;
            /* Changed to normal weight */
            font-size: 14px;
            /* Reduced font size */
        }

        .form-container .form-group input,
        .form-container .form-group select {
            width: 100%;
            padding: 6px;
            /* Reduced padding */
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            /* Reduced font size */
        }

        .form-container .form-group input[type="radio"] {
            width: auto;
            margin-right: 10px;
        }

        .form-container .form-group .radio-group {
            display: flex;
            align-items: center;
        }

        .form-container .form-group .radio-group label {
            margin-right: 10px;
        }

        .form-container .hidden {
            display: none;
        }

        .form-container .form-group.full-width {
            width: -webkit-fill-available;
            /* Ensure full width for long text */
        }

        .form-container button {
            background-color: #007BFF;
            color: #fff;
            padding: 8px 12px;
            /* Reduced padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            /* Reduced font size */
            margin-top: 10px;
            /* Add margin to the top of the button */
        }

        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        function updateResorts() {
            var destination = document.getElementById("holiday-destination").value;
            var resorts = document.getElementById("preferred-resort");
            var options = {
                "India": ["Karma Royal Haathi Mahal, Goa", "Karma Royal Palms, Goa", "Karma Royal MonteRio, Goa", "Goa Villagio, South Goa (Partner Hotel)", "Karma Sitabani, Corbett National Park", "Karma Seven Lakes, Udaipur", "Karma Golden Camp, Jaisalmer", "Karma Haveli, Jaipur", "Karma Sunshine Village, Bengaluru", "Karma Martam Retreat, Gangtok", "Karma Utopia, Manali", "Karma Munnar, Munnar", "Whispering Palms, Kumarakom(Partner Hotel)", "Copper Castle, Munnar (Partner Hotel)"],
                "Indonesia": ["Mentari Residences, Karma Kandara, Bali", "Jimbaran Bay Beach Resort, Bali (Partner Hotel)", "Swiss-Belhotel Tuban, Bali (Partner Hotel)"],
                "Thailand": ["Karma Royal Boat Lagoon, Phuket", "Karma Panalee, Koh Samui", "Supicha Pool Access Hotel, Phuket (Partner Hotel)", "The Oceanic Sportel, Phuket (Partner Hotel)", "The Pago Design Hotel, Phuket (Partner Hotel)", "March Samui Resort, Koh Samui (Partner Hotel)"],
                "Vietnam": ["Amina Lantana, Hoi An (Partner Hotel)"],
                "Cambodia": ["Karma Bayon, Siem Reap"],
                "Egypt": ["Karma Karnak, Luxor"],
                "Spain": ["Karma La Herriza, Gaucín"],
                "Germany": ["Karma Bavaria, Schliersee"],
                "Italy": ["Karma Borgo di Colleoli, Tuscany"],
                "United Kingdom": ["Karma Lake of Menteith, Stirling", "Karma Salford Hall, The Vale of Evesham"],
                "Maldives": ["Karma Fushi, Maldives"],
                "Greece": ["Karma Minoan, Crete"]
            };
            resorts.innerHTML = "";
            if (options[destination]) {
                options[destination].forEach(function(resort) {
                    var option = document.createElement("option");
                    option.value = resort;
                    option.text = resort;
                    resorts.appendChild(option);
                });
                resorts.parentElement.classList.remove("hidden");
            } else {
                resorts.parentElement.classList.add("hidden");
            }
        }
    </script>
</head>

<body>
    <div class="form-container">
        <form>
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first-name" required>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="last-name" required>
            </div>
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone-number">Phone Number</label>
                <input type="tel" id="phone-number" name="phone-number" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>
            <div class="form-group">
                <label for="passport">Do you have a passport?</label>
                <select id="passport" name="passport" required>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <select id="nationality" name="nationality" required>
                <option value="" selected disabled >Select</option>
                    <option value="India">India</option>
                    <option value="Australia">Australia</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Spain">Spain</option>
                    <option value="Egypt">Egypt</option>
                    <option value="Germany">Germany</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="form-group">
                <label for="holiday-destination">Preferred Destinations</label>
                <select id="holiday-destination" name="holiday-destination" onchange="updateResorts()" required>
                    <option value="" selected disabled >Select</option>
                    <option value="Cambodia">Cambodia</option>
                    <option value="Egypt">Egypt</option>
                    <option value="Germany">Germany</option>
                    <option value="Greece">Greece</option>
                    <option value="Italy">Italy</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="India">India</option>
                    <option value="Maldives">Maldives</option>
                    <option value="Spain">Spain</option>
                    <option value="Thailand">Thailand</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Vietnam">Vietnam</option>
                </select>
            </div>
            <div class="form-group hidden full-width">
                <label for="preferred-resort">Select Preferred Resorts</label>
                <select id="preferred-resort" name="preferred-resort" required>
                    <!-- Options will be populated based on the selected destination -->
                </select>
            </div>
            <div class="form-group full-width">
                <input type="checkbox" id="terms1" name="terms1" required>
                <label for="terms1">Allow Karma Experience/Karma Group related brands to communicate with me via SMS/email/call during and after your submission on this promotional offer.</label>
            </div>
            <div class="form-group full-width">
                <input type="checkbox" id="terms2" name="terms2" required>
                <label for="terms2">Should you be a registered DND subscriber, you agree that you have requested to be contacted about this contest/promotional offer.</label>
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>

</html>