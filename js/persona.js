$(function () {

    $('#persona').click(function () {
        var p, h = window.location.hash;
        if (h.length >= 2 && h.indexOf("required_email") !== -1) {
            h = h.substring(1);
            p = parseQueryString(h);
            navigator.id.get(gotAssertion, {requiredEmail: p.required_email});
            return false;
        }
        navigator.id.get(gotAssertion);
        return false;
    });
});

function gotAssertion(assertion) {
    // got an assertion, now send it up to the server for verification  
    if (assertion !== null) {
        $.ajax({
            type: 'POST',
            url: 'linkback.php',
            data: {
                assertion: assertion
            },
            success: function (res, status, xhr) {
                if (res === null) {} //loggedOut();  
                else loggedIn(res);
            },
            error: function (res, status, xhr) {
                alert("login failure" + res);
            }
        });
    } else {
        //loggedOut();  
    }
}

function loggedIn(res) {
    window.location = res.return_uri;
}

var parseQueryString = function (qs) {
    var e,
    a = /\+/g, // Regex for replacing addition symbol with a space
    r = /([^&;=]+)=?([^&;]*)/g,
    d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
    q = qs,
    urlParams = {};

    while (e = r.exec(q))
        urlParams[d(e[1])] = d(e[2]);

    return urlParams;
}

