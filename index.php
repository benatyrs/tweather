<?php
require('classes/config.php');
session_start();
header('Content-type: text/html; charset=utf-8');

if (!isset($_SESSION['rand_key'])) {
    $_SESSION['rand_key'] = md5(mt_rand(0,9999) . time() . SECRET_KEY);
    $_SESSION['key_timeout'] = time() + 3600;
    setcookie('key_timeout', time() + 3600, time() + 3600);
}
elseif (time() > $_SESSION['key_timeout']) {
    $_SESSION['rand_key'] = md5(mt_rand(0,9999) . time() . SECRET_KEY);
    $_SESSION['key_timeout'] = time() + 3600;
    setcookie('key_timeout', time() + 3600, time() + 3600);
}

$APIKey = md5($_SERVER['REMOTE_ADDR'] . $_SESSION['rand_key'] . SECRET_KEY); // Valid for 1 hour
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tweather: Weather around the UK as shown by Tweets</title>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <link rel="icon"
          type="image/png"
          href="http://localhost:8080/favicon.ico">
    <style type="text/css">
        body, html {
            height: 100%;
        }
        body {
            margin: 0;
        }
        #googlemap {
            width: 100%;
            height: 100%;
            position: relative;
        }
        #map-canvas {
            position: absolute;
            left: 0;
            top: 0;
            overflow: hidden;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        #main {
            width: 100%;
            height: 100%;
        }
        #banner  {
            width: 100%;
            height: 40px;
            background-color: #382E24;
            background: -o-linear-gradient(bottom, #000000 0%, #382E24 100%);
            background: -moz-linear-gradient(bottom, #000000 0%, #382E24 100%);
            background: -webkit-linear-gradient(bottom, #000000 0%, #382E24 100%);
            background: -ms-linear-gradient(bottom, #000000 0%, #382E24 100%);
            overflow: hidden;
            white-space: nowrap;
        }
        #logo {
            background: url('static/logo2.png') top center no-repeat;
            z-index: 10;
            display: inline-block;
            width: 187px;
            height: 50px;
            padding: 4px 0 4px 4px;
            float: left;
        }
        #overflow-wrap {
            overflow: hidden;
            height: calc(100% - 58px);
        }
        #wrap {
            float: left;
            width: 70%;
            /*height: calc(100% - 58px);*/
            height: 100%;
        }
        #sidebar {
            float: right;
            width: 30%;
            /*height: calc(100% - 58px);*/
            height: 100%;
            overflow-y: scroll;
        }
        #footer {
            width: 99%;
            height: 8px;
            margin-left:10px;

        }
        .pi {
            float: right;
            font-family: helvetica, sans-serif;
            font-size: 12px;
            color: #444444;
        }
        .Tweet {
            -moz-box-sizing: border-box;
            background-color: #FFFFFF;
            border: 1px solid #E1E8ED;
            line-height: 1.375em;
            padding: 13px 15px 15px;
            position: relative;
            font-family: "Gotham Narrow SSm",sans-serif;
            color: #292F33;
        }

        .Tweet-authorDetails {
            line-height: 14px;
            padding-top: 2px;
        }
        .Tweet-avatar {
            border-radius: 4px;
            float: left;
            height: 24px;
            margin: 0 6px 0 0;
            width: 24px;
        }

        .Tweet-fullname {
            color: #292F33;
            font-size: 14px;
            font-weight: bold;
        }
        .Tweet-screenname {
            color: #8899A6;
            font-size: 13px;
            text-decoration: none;
            cursor: hand;
        }
        .Tweet-timestamp {
            color: #8899A6;
            font-size: 13px;
            white-space: nowrap;
        }
        a {
            color: #8899A6;
            font-size: 13px;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .infobutton {
            -moz-box-shadow:inset 0 1px 0 0 #f29c93;
            -webkit-box-shadow:inset 0 1px 0 0 #f29c93;
            box-shadow:inset 0 1px 0 0 #f29c93;
            background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #fe1a00), color-stop(1, #ce0100) );
            background:-moz-linear-gradient( center top, #fe1a00 5%, #ce0100 100% );
            filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fe1a00', endColorstr='#ce0100');
            background-color:#fe1a00;
            -webkit-border-top-left-radius:20px;
            -moz-border-radius-topleft:20px;
            border-top-left-radius:20px;
            -webkit-border-top-right-radius:20px;
            -moz-border-radius-topright:20px;
            border-top-right-radius:20px;
            -webkit-border-bottom-right-radius:20px;
            -moz-border-radius-bottomright:20px;
            border-bottom-right-radius:20px;
            -webkit-border-bottom-left-radius:20px;
            -moz-border-radius-bottomleft:20px;
            border-bottom-left-radius:20px;
            text-indent:0;
            border:1px solid #d83526;
            display:inline-block;
            color:#ffffff;
            font-family: Arial, sans-serif;
            font-size:15px;
            font-weight:bold;
            font-style:normal;
            height:25px;
            line-height:25px;
            width:86px;
            text-decoration:none;
            text-align:center;
            text-shadow:1px 1px 0 #b23e35;
            z-index:10;
            float:right;
            margin: 6px 6px 0 0;

        }
        .infobutton:hover {
            background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #ce0100), color-stop(1, #fe1a00) );
            background:-moz-linear-gradient( center top, #ce0100 5%, #fe1a00 100% );
        }
        #popup {
            background: url("static/back.png") repeat-x scroll 0 bottom #FFFFFF;
            border: 1px solid #D1CED3;
            height: 270px;
            width: 500px;
            font-family: "Proxima Nova Regular","Segoe UI",Roboto,"Droid Sans","Helvetica Neue",Arial,sans-serif;
            border-radius: 10px;
            box-shadow: 0 0 2px 0 rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top:-135px;
            margin-left: -250px;
            z-index: 1000000;
            display: none;
            padding: 10px 20px 20px 20px;
        }
        .coin_container {
            margin-right: 7px;
            color: #333333;
            font-weight: bold;
            text-shadow: 0 -1px 0 #FFFFFF;
            font-size:12px;
        }
        .small {
            font-size:8px;
            color:#666666;
        }

    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCXeAK5uQJTdg8XWnzoaqITj4GEbFWaWOk">
    </script>
    <script type="text/javascript">
        var infowindow = 1;
        var debug = "N/A";
        var map;
        var icons = {
            Sunny: {
                icon: 'static/weathericons/sunny.png'
            },
            Rain: {
                icon: 'static/weathericons/rainy.png'
            },
            Cloudy: {
                icon: 'static/weathericons/cloudy.png'
            },
            Thunderstorm: {
                icon: 'static/weathericons/thunderstorm.png'
            },
            Windy: {
                icon: 'static/weathericons/wind-2.png'
            },
            Snowing: {
                icon: 'static/weathericons/snowy-2.png'
            }
        };
        function initialize() {
            infowindow = new google.maps.InfoWindow();
            var mapOptions = {
                center: new google.maps.LatLng(54.559322, -4.174804),
                zoom: 6
            };
            map = new google.maps.Map(document.getElementById("map-canvas"),
                mapOptions);
        }
        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
</head>
<body>
<div id="main">
    <div id="popup"><img src="static/logo3.png" /><a href="#"><img src="static/close-button.png" width="40" height="40" style="float: right; cursor: pointer;" id="closebutton" onclick="$(function() {$('#popup').fadeOut(400)});"/></a> <br />
        Tweather is an experiment. We find tweets about the weather in the UK, find the location they were tweeted from and place them on a map - we'll leave it up to you to identify trends.<br />

        <br />
        Open data used: <a href="http://boundingbox.klokantech.com">klokantech.com</a>, <a href="http://www.ordnancesurvey.co.uk/opendata/">ordnancesurvey.co.uk</a>
        <br />
        Map Icons: <a href="http://mapicons.nicolasmollet.com/category/markers/nature/weather/">nicolasmollet.com</a><br />
        <br /><div align="center"><a href="https://twitter.com/the_tweatherapp" class="twitter-follow-button" data-show-count="false" data-dnt="true">Follow @the_tweatherapp</a>

        <iframe src="http://ghbtns.com/github-btn.html?user=benatyrs&type=follow&count=true" allowtransparency="true" frameborder="0" scrolling="0" width="165" height="20"></iframe>
<br />
        <span class="coin_container"><img src="static/icon_bitcoin.png" style="vertical-align: middle;" />&nbsp;<span>1NsVruxNxeYawYJiXBWPUBo5mPkFe1CJKG</span><br />
        <span class="small"> YRS 2014. Contact: benedictyrs at gmail dot com </div>


        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script></div>
    <div id="banner"><div><span id="logo"></span><a href="#" class="infobutton" onclick="$(function() {$('#popup').fadeIn(400)});">About</a></div></div>
    <div id="overflow-wrap">
    <div id="wrap"><div id="googlemap"><div id="map-canvas"></div> </div></div>
    <div id="sidebar">
    </div>
    </div>
    <div id="footer"><div class="pi"><span id="status">Waiting... if this persists, enable JavaScript or update your browser.</span> | <span id="debug" onmouseout="$(this).text('&pi;');">&pi;</span></div></div>
</div>
<script language="javascript">
        $(document).ready(function () {
        String.prototype.stripSlashes = function () {
            return this.replace(/\\(.)/mg, "$1");
        };
        var marker = [];
        var currentAdded = [];
        var thiscycle = 0;
        var first = "0";

        var update = function () {
            $('#status').text("Updating...");

            if(first == 0) {
                setTimeout(update, 5000);
                first = 1;
            }

            $.ajax({
                url: 'api.php?API_KEY=<?=$APIKey?>&debug=true',
                dataType: 'json',
                timeout: 5000
            }).done(function (data) {
                updateDebug(data)
            });

            $.ajax({
                url: 'api.php?API_KEY=<?=$APIKey?>',
                dataType: 'json',
                timeout: 5000,
                error: function(x, t, m) {
                    if (t==="timeout") {
                        $('#status').text("API timeout. Refresh the page or try again later.");
                    }
                    else {
                        $('#status').text("Error" + m);
                    }
                }
            }).done(function (data) {
               // var findthis = $.parseJSON(data);
                if (data['error'] == "403") {
                    alert("Please refresh your page to continue updating.");
                    $('#status').text("API key has expired. Please refresh the page to generate a new one.");
                }
                else {
                    parseJson(data);
                    if(first == 1) {
                        setTimeout(update, 5000);
                    }
                }
            });
        };
        var parseJson = function (data) {
            //var jsonParsed = jQuery.parseJSON( data );
            // console.log(jsonParsed);
            thiscycle = 0;
            $.each(data.reverse(), function (key, val) {

                if (currentAdded.indexOf(data[key]['id']) == -1) {
                    console.log('index', data[key]['id']);
                    addMarker(data[key]);
                    addMarkerWindow(data[key]);
                    addSideTweet(data[key]);
                    currentAdded.push(data[key]['id'])
                }
                $('#status').text("Updated.");
            })
        };
        var updateDebug = function (data) {
            $('#debug').mouseover(function() {
                $('#debug').text("π | query ID: " + data[0] +  " | statistics: " + data[1] + " | last updated: " + data[2])
            });
        };
        var addMarker = function (data) {
            var icontype = data['json_data']['type'];
            var name = data['id'];
            marker[name] = new google.maps.Marker({
                position: new google.maps.LatLng(data['json_data']['geo']['coordinates']['0'], data['json_data']['geo']['coordinates']['1']),
                map: map,
                title: data['json_data']['location'],
                icon: icons[icontype].icon,
                animation: google.maps.Animation.DROP
            });
        };
        var addMarkerWindow = function (data) {
            var name = data['id'];
            infowindow[name] = new google.maps.InfoWindow();
            google.maps.event.addListener(marker[name], 'mouseover', function () {
                infowindow[name].setContent("<p>Tweet by <strong>@<a href=\"http://twitter.com/" + data['json_data']['user']['screen_name'] + "\">" + data['json_data']['user']['screen_name'] + "</a>:</strong> <i>" + data['json_data']['text'] + "</i></p>");
                infowindow[name].open(map, marker[name]);
            });

            google.maps.event.addListener(map, 'mousemove', function () {
                infowindow[name].close();
            });
        };
        var addSideTweet = function (data) {
            var tweet = "<div class=\"Tweet\" id=\"tweet_" + data['id'] + "\"><img alt=\"\" src=\"" + data['json_data']['user']['profile_image_url'].stripSlashes() + "\" class=\"Tweet-avatar\"><div class=\"Tweet-authorDetails\"><b class=\"Tweet-fullname\">" + data['json_data']['user']['name'].stripSlashes() + "</b> <span class=\"Tweet-screenname\"><a href=\"http://twitter.com/" + data['json_data']['user']['screen_name'] + "\">@" + data['json_data']['user']['screen_name'].stripSlashes() + "</a></span> <span class=\"Tweet-timestamp\">" + data['json_data']['ago'].stripSlashes() + "</span></div>" + data['json_data']['text'].stripSlashes() + "</div>";
            $("#sidebar").prepend(
                tweet
            );
            $("#tweet_" + data['id']).hide().delay(thiscycle).fadeIn(800);
            thiscycle = thiscycle + 600;

        };

        update();
    });
</script>
</body>
</html>
