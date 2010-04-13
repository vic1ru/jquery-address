<?php 
    
    define('FRAGMENT', '_escaped_fragment_');
    
    // Initializes the fragment value
    $fragment = (!isset($_REQUEST[FRAGMENT]) || $_REQUEST[FRAGMENT] == '') ? '/' : $_REQUEST[FRAGMENT];
 
    // Parses parameters if any
    $arr = explode('?', $fragment);
    $parameters = array();
    if (count($arr) > 1) {
        parse_str($arr[1], $parameters);
    }

    // Adds support for both /name and /?page=name
    if (isset($parameters['page'])) {
        $page = '/?page=' . $parameters['page'];
    } else {
        $page = $arr[0];
    }
    
    // Loads the data file
	$doc = new DOMDocument();
	$doc->load('data.xml');
	$xp = new DOMXPath($doc);

	$pageNodes = $xp->query('/data/page');
    $pageNode = $xp->query('/data/page[@href="' . $page . '"]')->item(0);
    $pageNav = '';
    $pageTitle = '';
    $pageContent = '';
    
    // Prepares the navigation links
    foreach ($pageNodes as $node) {
    	$href = $node->getAttribute('href');
        $title = $node->getAttribute('title');
    	$pageNav .= '<li><a href="' . ($href == '/' ? '#' : '#!' . $href) . '"' 
            . ($page == $href ? ' class="selected"' : '') . '>' 
            . $title . '</a></li>';
    }
    
    
    // Prepares the content with support for a simple "More..." link
    if (isset($pageNode)) {
        $pageTitle = $pageNode->getAttribute('title');
        foreach ($pageNode->childNodes as $node) {
            if (!isset($parameters['more']) && $node->nodeType == XML_COMMENT_NODE && $node->nodeValue == ' page break ') {
                $pageContent .= '<p><a href="' . ($page == '/' ? '#' : '#!' . $page) . '&amp;more=true">More...</a></p>';
                break;
            } else {
                $pageContent .= $doc->saveXML($node);
            }
        }
    } else {
    	$pageContent .= '<p>Page not found.</p>';
        header("HTTP/1.0 404 Not Found");
    }
    
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo($pageTitle); ?> | jQuery Address Crawling</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link type="text/css" href="styles.css" rel="stylesheet">
        <script type="text/javascript"> 
            if (/Android|iPad|iPhone/.test(navigator.platform)) 
                document.write('<style type="text/css" media="screen">' + 
                        'body { -webkit-text-size-adjust: none; } ' +
                        '.nav a:hover { background: none; text-decoration: underline; color: #fff; } ' + 
                        '</style>');
        </script> 
        <script type="text/javascript" src="jquery-1.4.2.min.js"></script>
        <script type="text/javascript" src="jquery.address-1.2rc.min.js?crawlable=true"></script>
        <script type="text/javascript">
            
            $.address.init(function(event) {

                // Initializes plugin support for links
                $('.nav a').address();

            }).change(function(event) {

                // Identifies the page selection 
                var page = event.parameters.page ? '/?page=' + event.parameters.page : event.path;

                // Highlights the selected link
                $('.nav a').each(function() {
                    $(this).toggleClass('selected', $(this).attr('href') == (page == '/' ? '#' : '#!' + page));
                }).filter('.selected').focus();

                var handler = function(data) {
                    $('.content').html($('.content', data).html()).parent().show();
                    $.address.title(/>([^<]*)<\/title/.exec(data)[1]);
                };

                // Loads the page content and inserts it into the content area
                $.ajax({
                    url: location.pathname + '?<?php echo(FRAGMENT); ?>=' + encodeURIComponent(event.value),
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        handler($(XMLHttpRequest.responseText));
                    },
                    success: function(data, textStatus, XMLHttpRequest) {
                    	handler(data);
	                }
                });

            });
            
            // Hides the page during initialization
            document.write('<style type="text/css"> .page { display: none; } </style>');

        </script>
    </head>
    <body>
        <div class="page">
            <h1>jQuery Address Crawling</h1>
            <ul class="nav"><?php echo($pageNav); ?></ul>
            <div class="content"><?php echo($pageContent); ?></div>
        </div>
    </body>
</html>