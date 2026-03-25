<?php

$url = "https://interbiharboard.com/Result.aspx";
$cookie = __DIR__ . "/cookie.txt";

function fixEncoding($html) {

    // Add UTF-8 meta if missing
    if (!preg_match('/<meta.*charset=/i', $html)) {
        $html = preg_replace(
            '/<head.*?>/i',
            '$0<meta charset="UTF-8">',
            $html
        );
    }

    return $html;
}



function fetchResult($rollCode, $rollNumber, $url, $cookie) {

    $ch = curl_init();

    // STEP 1: GET
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ]);

    $html = curl_exec($ch);

    preg_match('/id="__VIEWSTATE" value="(.*?)"/', $html, $vs);
    preg_match('/id="__EVENTVALIDATION" value="(.*?)"/', $html, $ev);
    preg_match('/id="__VIEWSTATEGENERATOR" value="(.*?)"/', $html, $vg);

    // STEP 2: POST
    $postData = http_build_query([
        "__VIEWSTATE" => $vs[1] ?? "",
        "__VIEWSTATEGENERATOR" => $vg[1] ?? "",
        "__EVENTVALIDATION" => $ev[1] ?? "",
        "mobile" => $rollCode,
        "password" => $rollNumber,
        "btn_login" => "View Result"
    ]);

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
            "Origin: https://interbiharboard.com",
            "Referer: https://interbiharboard.com/",
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>All Results (Print Perfect)</title>

    <style>
        body {
    margin: 0;
}

iframe {
    width: 100%;
    border: none;
    display: block;
    height: 1200px;
}

.page {
    break-after: page;
}

.page:last-child {
    break-after: auto;
}

@media print {
    iframe:nth-of-type(even) {
        display: none !important;
    }
    iframe {
        width: 100%;
        height: 1100px;
    }
     
}
    </style>

    <script>
        function resizeIframe(iframe) {
            try {
                const doc = iframe.contentWindow.document;
                const height = doc.body.scrollHeight;
                iframe.style.height = height + "px";
            } catch (e) {
                console.log("Resize blocked (cross-origin safe ignore)");
            }
        }
    </script>

</head>
<body>

<?php

$rollCode = "33206";
$start = 26010001;
$end   = 26010004;

for ($i = $start; $i <= $end; $i++) {

    echo "<div class='page'>";
    // echo "<h3 style='padding:10px;'>Roll: $i</h3>";

    $html = fetchResult($rollCode, $i, $url, $cookie);

    if (strpos($html, "Final Result") !== false) {

    $html = fixEncoding($html);
        $encoded = base64_encode($html);

        echo "<iframe src='data:text/html;base64,$encoded' onload='resizeIframe(this)'></iframe>";

    } else {
        echo "<p style='color:red;'>No result</p>";
    }

    echo "</div>";

    flush();
    ob_flush();
    sleep(1);
}
?>

</body>
</html>