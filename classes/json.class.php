<?php
date_default_timezone_set("UTC");
class json {
    public static $APICallsG = 0;
    public static $GeoTweets = 0;
    public static $LocationTweets = 0;
    public static $LocationValidTweets = 0;
    public static $InUKTweets = 0;
    protected static $FileOut = array();
    // TODO: remove instances of static and replace on main page :: with $class->hello(); (and $this->) thank you ##php
    private static function load_file() {
        self::$FileOut = file("static/50kgaz2013.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Open Data
        // www.ordnancesurvey.co.uk/opendata/licence
        // Provided from http://parlvid.mysociety.org/os/gaz50k_gb-2013-06.zip
    }

    public static function json_return($JSON) {
        return json_decode($JSON, true);
    }

    public static function parseTweets($JSON, $GApiKey = '')
    {
        $ParseFurther = array();
        $UKValues = array();
        self::load_file();
        ###########################
        # LOCATION MATCHING BLOCK #
        ###########################

        foreach ($JSON as $Key => $Val) {
            if (isset($JSON[$Key]['geo'])) {
                if (!empty($JSON[$Key]['geo']) && is_array($JSON[$Key]['geo']['coordinates'])) {
                    // Tweets containing geo-coordinates can be added right away. We will check to make sure the location is
                    // in the UK later on using a bounding box of UK geo-coords.
                    self::$GeoTweets++;
                    array_push($ParseFurther, $JSON[$Key]);
                }
            } elseif (isset($JSON[$Key]['user']['location'])) {
                // We'll check for location names now. We will use a database of locations to geo-coords, and check if the
                // location exists. This allows us to push through up to hundreds of requests a seconds instead of 10/sec
                // as limited by google's API. We can now run this script in real-time with the tweets!

                // If the string contains "UK" or "United Kingdom" we can proceed. Additionally we'll also allow the names
                // of major UK cities to be found.
                // The reason for this is that locations such as "Boston" would match up to Boston, Lincolnshire when it's
                // far more likely it's actually Boston, Massachusetts. There's no real way to check this if the user has
                // just put 'Boston' as their location. We will get enough from the geo-coords so that missing out on a few
                // here won't matter.
                if (!empty($JSON[$Key]['user']['location'])) {
                    self::$LocationTweets++;
                   if (ctype_alnum(str_replace(",", "", (string)$JSON[$Key]['user']['location']))) {
                        // If alphanumerical including commas
                        if (stripos($JSON[$Key]['user']['location'], 'UK') !== false
                            || stripos($JSON[$Key]['user']['location'], 'United Kingdom') !== false
                            || stripos($JSON[$Key]['user']['location'], 'England') !== false
                            || stripos($JSON[$Key]['user']['location'], 'Scotland') !== false
                            || stripos($JSON[$Key]['user']['location'], 'Wales') !== false
                            || preg_match(
                                '/^(London|Birmingham|Leeds|Glasgow|Sheffield|Bradford|Liverpool|Edinburgh|Manchester|Bristol|Kirklees|Fife|Wirral|North Lanarkshire|Wakefield|Cardiff|Dudley|Wigan|East Riding|South Lanarkshire|Coventry|Belfast|Leicester|Sunderland|Sandwell|Doncaster|Stockport|Sefton|Nottingham|Newcastle-upon-Tyne|Kingston-upon-Hull|Bolton|Walsall|Plymouth|Rotherham|Stoke-on-Trent|Wolverhampton|Rhondda|Cynon|Taff|South Gloucestershire|Derby|Swansea|Salford|Aberdeenshire|Barnsley|Tameside|Oldham|Trafford|Aberdeen|Southampton|Highland|Rochdale|Solihull|Gateshead|Milton Keynes|North Tyneside|Calderdale|Northampton|Portsmouth|Warrington|North Somerset|Bury|Luton|St Helens|Stockton-on-Tees|Renfrewshire|York|Thamesdown|Southend-on-Sea|New Forest|Caerphilly|Carmarthenshire|Bath|North East Somerset|Wycombe|Basildon|Bournemouth|Peterborough|North East Lincolnshire|Chelmsford|Brighton|South Tyneside|Charnwood|Aylesbury Vale|Colchester|Knowsley|North Lincolnshire|Huntingdonshire|Macclesfield|Blackpool|West Lothian|South Somerset|Dundee|Basingstoke|Deane|Harrogate|Dumfries|Galloway|Middlesbrough|Flintshire|Rochester-upon-Medway|The Wrekin|Newbury|Falkirk|Reading|Wokingham|Windsor|Maidenhead|Maidstone|Redcar|Cleveland|North Ayrshire|Blackburn|Neath Port Talbot|Poole|Wealden|Arun|Bedford|Oxford|Lancaster|Newport|Canterbury|Preston|Dacorum|Cherwell|Perth|Kinross|Thurrock|Tendring|Kings Lynn|West Norfolk|St Albans|Bridgend|South Cambridgeshire|Braintree|Norwich|Thanet|Isle of Wight|Mid Sussex|South Oxfordshire|Guildford|Elmbridge|Stafford|Powys|East Hertfordshire Torbay|Wrexham Maelor|East Devon|East Lindsey|Halton|Warwick|East Ayrshire|Newcastle-under-Lyme|North Wiltshire|South Kesteven|Epping Forest|Vale of Glamorgan|Reigate|Banstead|Chester|Mid Bedfordshire|Suffolk Coastal|Horsham|Nuneaton|Bedworth|Gwynedd|Swale|Havant|Waterloo|Teignbridge|Cambridge|Vale Royal|Amber Valley|North Hertfordshire|South Ayrshire|Waverley|Broadland|Crewe|Nantwich|Breckland|Ipswich|Pembrokeshire|Vale of White Horse|Salisbury|Gedling|Eastleigh|Broxtowe|Stratford-on-Avon|South Bedfordshire|Angus|East Hampshire|East Dunbartonshire|Conway|Sevenoaks|Slough|Bracknell Forest|West Lancashire|West Wiltshire|Ashfield|Lisburn|Scarborough|Stroud|Wychavon|Waveney|Exeter|Dover|Test Valley|Gloucester|Erewash|Cheltenham|Bassetlaw|Scottish Borders)$/i'
                                , $JSON[$Key]['user']['location'])
                        ) {
                            // www.citymayors.com/gratis/uk_topcities.html
                            self::$LocationValidTweets++;
                            array_push($ParseFurther, $JSON[$Key]);
                        }
                   }
                }
            }
        }
        foreach ($ParseFurther as $Key => $Val) {
            if (isset($ParseFurther[$Key]['geo'])) {
                if (!empty($ParseFurther[$Key]['geo']) && is_array($ParseFurther[$Key]['geo']['coordinates'])) {
                    // Having geo-coords makes life a lot easier. We can just filter the text and put them straight on the google map.
                    if (self::isUK($ParseFurther[$Key]['geo']['coordinates'][0], $ParseFurther[$Key]['geo']['coordinates'][1])) {
                        // [0] => lat, [1] => long
                        self::$InUKTweets++;
                        array_push($UKValues, $ParseFurther[$Key]);
                    }
                }
            }
            else {
                $LatLng = self::convertLatLngToDec(self::findGeoCoordsFromFile(str_replace(",", "",$JSON[$Key]['user']['location'])));
                if (!empty($LatLng)) {
                    if (self::isUK($LatLng[0], $LatLng[1])) {
                        $ParseFurther[$Key]['geo']['coordinates'][0] = $LatLng[0];
                        $ParseFurther[$Key]['geo']['coordinates'][1] = $LatLng[1];
                        array_push($UKValues, $ParseFurther[$Key]);
                        self::$InUKTweets++;
                    }
                }
            }
        }
       # $UKValues = $ParseFurther;

        foreach($UKValues as $Key => $Val) {
            G::$DB->store($UKValues[$Key]);
        }

        return $UKValues;
    }

    private static function convertLatLngToDec($Arr) {
        list($LatDeg, $LatMin, $LngDeg, $LngMin, $Dir) = $Arr;
        $OutLat = $LatDeg + ($LatMin / 60);
        $OutLng = $LngDeg + ($LngMin / 60);
        if ($Dir == "W") {
            $OutLng = $OutLng * -1;
        }
        return array($OutLat, $OutLng);
    }

    private static function googleAPI($Call, $Data, $GApiKey) {
        // Google API limits: 2,500 a day, 10 / second
        switch ($Call) {
            case 1:
                if (is_array($Data)) {
                    return json_decode(self::curlGrab("https://maps.googleapis.com/maps/api/geocode/json?latlng=".implode(",", $Data)."&key=".$GApiKey), true);
                }
                else {
                    return 0;
                }
                break;
            case 2:
                return json_decode(self::curlGrab("https://maps.googleapis.com/maps/api/geocode/json?address=".$Data."&key=".$GApiKey), true);
                break;
            default:
                return 0;
        }
    }

    private static function curlGrab($URL) {
        self::$APICallsG++;
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $URL);
        curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

        $o = curl_exec($curlSession);
        curl_close($curlSession);
        return $o;
    }
    private static function isUK($Lat, $Lng) {
        $ne_lat = 61.061;
        $ne_lng = 2.092;
        $sw_lat = 49.674;
        $sw_lng = -14.016;
        // Open data: http://boundingbox.klokantech.com - OpenStreetMap (c) CC-BY-SA
        return $sw_lat <= $Lat && $Lat <= $ne_lat && $sw_lng <= $Lng && $Lng <= $ne_lng;
    }

    private static function findGeoCoordsFromFile($Location) {
        // WARNING: This is an EXPENSIVE function (or, assumed to be, I haven't tested)
        $Found = 0;
        $Out = array_values(preg_grep("/" . preg_quote(trim($Location), '/') . ":/i", self::$FileOut));
        // preg_quote returns the string with all regex chars escaped
        if (!empty($Out)) {
            foreach($Out as $Key => $Val) {
                if (!isset($Found)) {
                    $OutT = explode(":",$Out[$Key]);
                    if (stripos($OutT[2], $Location) !== false) { // Solves major issue with everything being plotted in the same place on rare occasions
                        $OutFinal = $Out[$Key];
                        $Found = 1;
                    }
                }
            }
            if (!isset($OutFinal)) {
                return 0;
            }

            $Out = explode(":",$OutFinal);
            return array($Out[4], $Out[5], $Out[6], $Out[7], $Out[10]);
        }
        else {
            return 0; // Not found
        }
    }

    public static function getLatestTweets() {
        $Out = array();
        $Query = G::$DB->query("SELECT id,
                              tweet_id,
                              json_data
                       FROM tweets
                       ORDER BY tweet_id DESC
                       LIMIT 0,15");
        while ($Result = mysqli_fetch_assoc($Query)) {
            $Result['json_data'] = json_decode($Result['json_data'],true);
            $Result['json_data']['ago'] = self::ago(strtotime($Result['json_data']['created_at']));
            array_push($Out, $Result);
        }

        return json_encode($Out);
    }
    public static function getDebug() {
        $Out = 0;
        $Query = G::$DB->query("SELECT * FROM process_debug ORDER BY id DESC LIMIT 0,1");
        while ($Result = mysqli_fetch_array($Query)) {
            $Out = array($Result[0], $Result[1], $Result[2]);
        }

        return json_encode($Out);
    }
    public static function submitDebug($a, $b, $c, $d, $e, $f, $g, $h, $i, $j) {
        //echo Twitter::$FoundTweets."<br />";
       // echo Twitter::$TrashedTweets."<br />";
        //echo JSON::$GeoTweets."<br />";
       // echo JSON::$LocationTweets."<br />";
      //  echo JSON::$LocationValidTweets."<br />";
       // echo JSON::$InUKTweets;
        // mysql queries
        // mysql time
        // $ScriptExecutionTime
        // peak memory usage

        $All = implode(",", array($a, $b, $c, $d, $e, $f, $g, $h, $i, $j));
        G::$DB->query("INSERT INTO process_debug (text, time)
                     VALUES ('" . $All . "', '" . time() . "')
        ");

    }
    public static function ago($when) {
        // http://stackoverflow.com/questions/3470820/php-minutes-ago-seconds-ago-is-this-correct-coded
        $diff = date("U") - $when;

        // Days
        $day = floor($diff / 86400);
        $diff = $diff - ($day * 86400);

        // Hours
        $hrs = floor($diff / 3600);
        $diff = $diff - ($hrs * 3600);

        // Mins
        $min = floor($diff / 60);
        $diff = $diff - ($min * 60);

        // Secs
        $sec = $diff;

        // Return how long ago this was. eg: 3d 17h 4m 18s ago
        // Skips left fields if they aren't necessary, eg. 16h 0m 27s ago / 10m 7s ago
        $str = sprintf("%s%s%s%s",
            $day != 0 ? $day."d " : "",
            ($day != 0 || $hrs != 0) ? $hrs."h " : "",
            ($day != 0 || $hrs != 0 || $min != 0) ? $min."m " : "",
            $sec."s ago"
        );

        return $str;
    }
}