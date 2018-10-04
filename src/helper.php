<?php

if (!function_exists('vuecli')) {
  /**
   * Get the path to Vue-Cli-Service generated assets.
   *
   * @param string $bundle Default bundle
   * @param string $ignore Ignores error message if not found
   *
   * @throws \InvalidArgumentException
   */
  function vuecli($bundle, $ignore = false)
  {
    static $assets = null;
    $distPath = config('vuecli.dist_path', 'dist');
    $useVuePath = config('vuecli.use_path', false);
    $ssl = config('vuecli.dev_server.https', false);
    $server = config('vuecli.dev_server.ip', '127.0.0.1');
    $port = config('vuecli.dev_server.port', 8080);
    $urlbase = 'http' . ($ssl ? 's' : '') . '://' . $server . ':' . $port;


    if (is_null($assets)) {
      $json = @file_get_contents(
        public_path(
          $distPath . config('vuecli.assets_manifest', '/manifest.json')
        )
      );
      if ($json === false)
        throw new InvalidArgumentException("Error loading assets manifest.json, set to correct filename in config/vuecli.php");
      $assets = @json_decode($json, true);
    }

    if (isset($assets[$bundle])) {
      if (App::isLocal())// && !File::exists(public_path($distPath.$assets[$bundle])))
      {
        $url = '';

        // It is slow but only use it when you really like it
        if ($useVuePath) {
          $vueserver = @get_headers($urlbase);
          if (!$vueserver || $vueserver[0] == 'HTTP/1.1 404 Not Found')
          // if (!$fp = curl_init($urlbase))
          {

          } else {
            if (!preg_match('~^(http\:\/\/|https\:\/\/)~i', $assets[$bundle])) {
              $url = $urlbase;
            }
          }
        }

        return $url . $assets[$bundle];
      }

      return $distPath . $assets[$bundle];
    }

    if (!$ignore){
      throw new InvalidArgumentException("Bundle {$bundle} not found in asset manifest.");
    }
    else{
      return ":AssetNotFound_{$bundle}";
    }
  }
}