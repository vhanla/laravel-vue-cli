<?php

return [

  // Dist path relative to public directory, changing from default needs adjustments in other files too like vue.config.js baseUrl:
  'dist_path' => '',

  // Whether or not to use VueCli's serve host and port to assign assets
  'use_path' => false,

  // Webpack Manifest Plugin output path, if you are using a PWA plugin it is recommend not to set as /manifest.json
  // both, here and in npm plugin option too
  'assets_manifest' => '/assets.json',

  // Vue dev server options
  'dev_server' => [

    // Https
    'https' => false,

    // Vue dev server IP
    'ip' => '127.0.0.1',

    // Vue dev server port
    'port' => 8080,
  ]

];