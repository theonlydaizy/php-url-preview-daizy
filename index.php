<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="author" content="Ashok Kumar Pachauri">
  <title>URL Preview</title>
</head>

<body>
    <form method="POST" action="" style="text-align: center;">
        <input type="text" name="url" style="height: 30px; border: 1px solid #ABABAB;width: 25%; border-radius: 3px;">
        <input type="submit" name="submit" value="Show Preview" style="height: 30px;border: 1px solid #ABABAB;border-radius: 3px;cursor: pointer;">
    </form>
    <?php
    if(isset($_POST["submit"])){ 
        $result = getUrlData($_POST["url"]);
        // echo '<pre>'; print_r($result); echo '</pre>';

        $img_src = ""; $description = "";
        if(isset($result["metaTags"]["og:description"]["value"]))
            $description = $result["metaTags"]["og:description"]["value"];
        if(isset($result["metaTags"]["description"]["value"]) && $description == "")
            $description = $result["metaTags"]["description"]["value"];

        if(isset($result["metaTags"]["twitter:image"]["value"]))
            $img_src = $result["metaTags"]["twitter:image"]["value"];
        if(isset($result["metaTags"]["og:image"]["value"]) && $img_src == "")
            $img_src = $result["metaTags"]["og:image"]["value"];
        if($img_src == ""){
            libxml_use_internal_errors(true);
            $doc = new DomDocument();
            $header = file_get_contents($_POST["url"]);
            $doc->loadHTML($header);
            $xpath = new DOMXPath($doc);
            $img_src_temp = $xpath->evaluate("//img");
            foreach ($img_src_temp as $image) {
                $src[] = $image->getAttribute('src');
            }
            $img_src = $src[0];
            // else $img_src = $img_src_temp;
        } ?>
        <center>
        <div style="max-width: 600px;min-height: 90px;border: 1px solid #ABABAB;padding: 5px;text-align: justify;">
            <img src="<?php echo $img_src; ?>" style="float:left;margin: 5px;width: 100px; height: 80px;">
            <div><a href="<?php echo $_POST["url"]; ?>" target="_blank" style="font-weight: bold;text-decoration: none;"><?php echo $result["title"]; ?></a></div>
            <div><?php echo $description; ?>
            </div>
        </div>
        </center><?php 
    } 
    
    // All function here
    function getUrlData($url)
    {
        $result = false;

        $url_file_contents = getWebContent($url);

        if (isset($url_file_contents) && is_string($url_file_contents))
        {
            $title = null;
            $metaTags = null;

            preg_match('/<title>([^>]*)<\/title>/si', $url_file_contents, $match );

            if (isset($match) && is_array($match) && count($match) > 0)
            {
                $title = strip_tags($match[1]);
            }
            $metaTags = array();

            preg_match_all('/<[\s]*meta[\s]*name="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $url_file_contents, $match);

            if (isset($match) && is_array($match) && count($match) == 3)
            {
                $originals = $match[0];
                $names = $match[1];
                $values = $match[2];

                if (count($originals) == count($names) && count($names) == count($values))
                {
                    for ($i=0, $limiti=count($names); $i < $limiti; $i++)
                    {
                        $metaTags[$names[$i]] = array (
                            'html' => htmlentities($originals[$i]),
                            'value' => $values[$i]
                        );
                    }
                }
            }

            preg_match_all('/<[\s]*meta[\s]*property="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $url_file_contents, $match);

            if (isset($match) && is_array($match) && count($match) == 3)
            {
                $originals = $match[0];
                $names = $match[1];
                $values = $match[2];

                if (count($originals) == count($names) && count($names) == count($values))
                {
                    // if(!isset($i)) $i=0;
                    for ($j=0, $limiti=count($names); $j < $limiti; $j++)
                    {
                        $metaTags[$names[$j]] = array (
                            'html' => htmlentities($originals[$j]),
                            'value' => $values[$j]
                        );
                    }
                }
            }

            $result = array (
                'title' => $title,
                'metaTags' => $metaTags
            );
        }

        return $result;
    }

    function getWebContent($url, $atmost_redirect = null, $ongoing_redirect = 0)
    {
        $result = false;
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        $url_file_contents = @file_get_contents($url,false,$context);
        // $url_file_contents = @file_get_contents($url);

        // Check if we need to go somewhere else

        if (isset($url_file_contents) && is_string($url_file_contents))
        {
            preg_match_all('/<[\s]*meta[\s]*http-equiv="?REFRESH"?' . '[\s]*content="?[0-9]*;[\s]*URL[\s]*=[\s]*([^>"]*)"?' . '[\s]*[\/]?[\s]*>/si', $url_file_contents, $match);

            if (isset($match) && is_array($match) && count($match) == 2 && count($match[1]) == 1)
            {
                if (!isset($atmost_redirect) || $ongoing_redirect < $atmost_redirect)
                {
                    return getWebContent($match[1][0], $atmost_redirect, ++$ongoing_redirect);
                }

                $result = false;
            }
            else
            {
                $result = $url_file_contents;
            }
        }

        return $url_file_contents;
    }
?>
</body>
</html>