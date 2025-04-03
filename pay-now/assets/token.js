
function diff_minutes(dt2, dt1) {
    var diff = (dt2.getTime() - dt1.getTime()) / 1000;
    diff /= 60;
    return Math.round(diff)
}
function checkTokenAvailability(tokenName, callback) {
    if (typeof(Storage) !== "undefined") {
        var ACCESS_TOKEN = localStorage.getItem(tokenName);
        if (ACCESS_TOKEN && JSON.parse(ACCESS_TOKEN) != '') {
            ACCESS_TOKEN = JSON.parse(ACCESS_TOKEN);
            var exp = new Date(ACCESS_TOKEN.exp * 1000);
            var today = new Date();
            if (diff_minutes(exp, today) <= 3) {
                requestToken(function(requestToken) {
                    localStorage.setItem(tokenName, JSON.stringify(requestToken));
                    callback(requestToken.token)
                })
            } else {
                callback(ACCESS_TOKEN.token)
            }
        } else {
            requestToken(function(requestToken) {
                localStorage.setItem(tokenName, JSON.stringify(requestToken));
                callback(requestToken.token)
            })
        }
    } else {
        requestToken(function(requestToken) {
            callback(requestToken.token)
        })
    }
}
function requestToken(callback) {
    $.ajax({
        type: "GET",
        url: "https://api.karmagroup.com/request-token",
        cache: !1,
        success: function(result) {
            if (result.success) {
                callback(result)
            } else {
                alert("An error has occured, please try again later.");
                location.reload(!0)
            }
        },
        error: function(err) {
            alert("An error has occured, please try again later.");
            location.reload(!0)
        }
    })
}