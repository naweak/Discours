<?php
use Phalcon\Mvc\Router;

// Pages:

function route_page ($page, $router)
{
  $router->add
  (
      "/$page",
      [
          "controller" => "page",
          "page"       => $page
      ]
  );
}

$pages = ["login",
         "register",
         "delete",
         "backup",
         "move",
         "sticky",
         "tidyup",
         "get-invite",
         "crawler",
         "convert",
         "verify",
         "verify2",
         "captcha",
         "pr-topic",
         "settings",
         "stat",
         "report",
         "my-topics",
         "notifications",
          
         "about",
         "rules",
         "faq",
         "contact",
         "developers",
         "markup",
         "why-us",
         "search"
];

foreach ($pages as $page)
{
  route_page($page, $router);
}

$router->add
(
    "/backu",
    [
        "controller" => "page",
        "page"       => "backup"
    ]
);

// https://discou.rs/
$router->add
(
    "/",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/posting/post
$router->add
(
    "/posting/post",
    [
        "controller" => "posting",
        "action"     => "post"
    ]
);

// https://discou.rs/notification/view
$router->add
(
    "/notification/view",
    [
        "controller" => "notification",
        "action"     => "view"
    ]
);

// Redirects:

// https://discou.rs/github
$router->add
(
    "/github",
    [
        "controller" => "redirect",
        "url"        => "https://github.com/DiscoursProject/Discours"
    ]
);

// https://discou.rs/cloud
$router->add
(
    "/cloud",
    [
        "controller" => "redirect",
        "url"        => "https://drive.google.com/drive/folders/1m1vAvOcXDJc_yvNaMqjZ122M7c0qa4AM"
    ]
);

// Forum:

// https://discou.rs/b/
$router->add
(
    "/b/",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/abc/
$router->add
(
    "/{slug:[a-z0-9]+}/",
    [
        "controller" => "forum"
    ]
);

// Topic:

// https://discou.rs/topic/123
$router->add
(
    "/topic/{topic:[0-9]+}",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/123
$router->add
(
    "/{topic:[0-9]+}",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/123/hello-world
$router->add
(
    "/{topic:[0-9]+}/{slug:[a-z0-9-]+}",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/chat/
$router->add
(
    "/chat/",
    [
        "controller" => "forum",
        "topic"      => 53117
    ]
);

// Wakaba-style:

// https://discou.rs/b/res/123.html
$router->add
(
    "/{slug:[a-z0-9]+}/res/{topic:[0-9]+}.html",
    [
        "controller" => "forum"
    ]
);

// https://discou.rs/res/123.html (Dollchan needs this to work in root directory)
$router->add
(
    "/res/{topic:[0-9]+}.html",
    [
        "controller" => "forum"
    ]
);
?>