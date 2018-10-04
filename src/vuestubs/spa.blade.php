<!doctype html>
<html lang="{{ app()->getlocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">

        <title>VueSPA</title>
        <meta name="theme-color" content="#7b4e97">
        <!-- remove next tag if not pwa -->
        <link rel="manifest" href="/manifest.json">
        <!-- preload assets -->
        <link href="@vuecli(about.js)" rel=prefetch>
        <link href="@vuecli(app.css)" rel=preload as=style>
        <link href="@vuecli(app.js)" rel=preload as=script>
        <link href="@vuecli(chunk-vendors.js, true)" rel=preload as=script>
        <!-- styles -->
        <link href="@vuecli(app.css)" rel=stylesheet>
    </head>
    <body>
        <noscript>
            sorry, but this is a pwa, needs javascript and modern browser.
        </noscript>
        <div id="app"></div>
        <script src="@vuecli(chunk-vendors.js,true)" type="text/javascript" charset="utf-8"></script>
        <script src="@vuecli(app.js)" type="text/javascript" charset="utf-8"></script>
        <!-- remove it if no livereload is used -->
        @livereload(http://localhost,35729)
    </body>
</html>
