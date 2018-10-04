<?php

namespace Vhanla\Vuecli;

use Illuminate\Support\Facades\Blade;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\PresetCommand;
use PHPUnit\Framework\Constraint\FileExists;

class VuecliServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {
    $this->registerHelpers();
    $this->publishConfig();

    Blade::directive('vuecli', function($expression){
      $params = explode(',', $expression);
      if (sizeof($params)==1){
        $params[] = false;
      }
      return vuecli($params[0], $params[1]);
    });

    Blade::directive('livereload', function($expression){
      $params = explode (',', $expression);
      if (\App::isLocal()){
        return "<script src='{$params[0]}:{$params[1]}/livereload.js'></script>";
      }
      else{
        return "";
      }
    });

    // It will also a preset command to modify default laravel to vuets
    PresetCommand::macro('vuecli', function ($cmd) {
      // Preset::install();
      $this->install($cmd);
    });
  }

  /**
   * Register the application services.
   *
   * @return void
   */
  public function register()
  {
    $this->mergeConfigs();
  }

  /**
   * Register helper file.
   */
  public function registerHelpers()
  {
    require_once __DIR__ . '/helper.php';
  }

  /**
   * Merge config file.
   */
  public function mergeConfigs()
  {
    $config_path = __DIR__ . '/../config/vuecli.php';
    $this->mergeConfigFrom($config_path, 'vuecli');
  }

  /**
   * Publish config.
   */
  public function publishConfig()
  {
    $config_path = __DIR__ . '/../config/vuecli.php';
    $publish_path = base_path('config/vuecli.php');

    $this->publishes([$config_path => $publish_path], 'config');
  }

  public static function updatePackageArray(array $packages)
  {
    return[
      "webpack-livereload-plugin" => "^2.1.1",
      "webpack-manifest-plugin" => "^2.0.4",
    ] + $packages;
  }

  /**
   * Creates vue.config.js
   *
   * @param string $filePath Path where to save it
   * @param boolean $liveReload Enable Livereload
   * @param boolean $typescript Use TypeScript
   */
  public static function createVueconfig($filePath , $liveReload = false, $typescript = false)
  {
    $plugin1a = "const ManifestPlugin = require('webpack-manifest-plugin')";
    $plugin1b = "new ManifestPlugin({fileName: 'assets.json'})";
    $plugin2a = "const LiveReload = require('webpack-livereload-plugin')";
    $plugin2b = "new LiveReload()";
    $appEntry = "entry: { app: __dirname + '/resources/src/main.js' },";
    if ($typescript){ $appEntry = "entry: { app: __dirname + '/resources/src/main.ts' }," ; }

    $tpl1 = "const fs = require('fs')
      if (!fs.existsSync('./public')){
        fs.mkdirSync('./public')
      }
      fs.chmodSync('./public', 0755)
      module.exports = {
        outputDir: 'public/',
        indexPath: 'spa.html',
        filenameHashing: true,
        configureWebpack: {
          resolve: {
            alias: {
              '@': __dirname + '/resources/src'
            }
          },";
    $tpl2 = " plugins: [\r\n            ";
    $tpl3 = "    ]
      },
      chainWebpack: config => {
        config.plugin('copy')
          .tap(args => [[{
            from: __dirname + '/resources/public/',
            to: __dirname + '/public/',
            toType: 'dir'
          }]])
      }
    }";

    $buff = $plugin1a . "\r\n";
    if ($liveReload){ $buff .= $plugin2a . "\r\n"; }
    $buff .= $tpl1 . "\r\n" . "          ". $appEntry . "\r\n" . "         ". $tpl2 . $plugin1b;
    if ($liveReload){ $buff .= ",\r\n            " . $plugin2b. "\r\n      "; }
    $buff .= $tpl3;

    file_put_contents($filePath, $buff);
  }

  /**
   * Update TSConfig
   *
   * @param
   */
  public static function updateTSConfig($filename, $outputfile)
  {
    $json = json_decode(file_get_contents($filename), true);
    $includes = $json['include'];
    $include_ = [];
    foreach($includes as $include){
      if (strpos($include, 'src') !== false){
        if (strpos($include, 'resources') !== false){
          $include_[] = $include;
        }
        else{
          $include_[] = 'resources/'.$include;
        }
      }
      else if (strpos($include, 'test') !== false){
        if (strpos($include, 'resources') !== false){
          $include_[] = $include;
        }
        else{
          $include_[] = 'resources/'.$include;
        }
      }
      else{
        $include_[] = $include;
      }
    }

    $json['compilerOptions']['paths']['@/*'] = ['resources/src/*'];

    $json['include'] = $include_;

    file_put_contents($outputfile, json_encode($json,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL);
  }

  public static function updateCypress($filename, $outputfile)
  {
    $json = json_decode(file_get_contents($filename), true);

    $pf = $json['pluginsFile'];
    if(strpos($pf, "tests") !== false){
      if (strpos($pf, "resources") !== false){
        $json['pluginsFile'] = $pf;
      }
      else{
        $json['pluginsFile'] = 'resources/'.$pf;
      }
    }

    file_put_contents($outputfile, json_encode($json,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL);
  }
  /**
   * Interactive installation
   */
  public function install($cmd)
  {
    $fs = new Filesystem();

    $cmd->info(' Vue Cli preset refactor v1.0');
    $cmd->info('');
    $cmd->info(' It refactors existing Vue-Cli projects to Laravel files structure.');
    $cmd->info(' First create a Vue-Cli project inside resources directory.');
    $cmd->info(' Preferably with "vue create --no-git <app-name>" option.');

    $dirs = scandir(resource_path());
    $projects = [];
    $exceptDirs = ['.', '..', 'js', 'lang', 'sass', 'views'];
    foreach ($dirs as $dir) {
      if (is_dir(resource_path() . '/' .$dir)) {
        if (!in_array($dir, $exceptDirs)){
          $projects[] = $dir;
        }
      }
    }

    $app_name = $cmd->choice('Then select its app-name here', $projects);
    $app_path = resource_path().'/'.$app_name;

    if (!is_dir($app_path)) {
      $cmd->error('"' . $app_name . '" was not found in resources directory.');
      return;
    }

      // check if package json exists
    if (!file_exists($app_path . '/package.json')) {
      $cmd->error('Invalid project, package.json was not found!');
      return;
    }

    // Lists files to move to root folder
    $appFiles = scandir($app_path);
    $app_files = [];
    $excludeFiles = ['README.md', '.gitignore', '.', '..'];
    foreach($appFiles as $appFile){
      if (!in_array($appFile, $excludeFiles) && !is_dir($app_path.'/'.$appFile)){
        $app_files[] = [$appFile, 'A To Laravel\'s root directory'];
      }
    }

    // Add npm packages required for our package
    $packages = json_decode(file_get_contents($app_path.'/package.json'), true);
    $packages['devDependencies'] = static::updatePackageArray(
      array_key_exists('devDependencies', $packages) ? $packages['devDependencies']:[],
      'devDependencies'
    );

    ksort($packages['devDependencies']);

    file_put_contents(
      $app_path.'/package.json',
      json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
    );

    // Create vue.config.js
    $lr = $cmd->confirm('Enable Livereload?');
    $ts = file_exists($app_path.'/src/main.ts');
    static::createVueconfig($app_path.'/vue.config.js', $lr, $ts);

    // Modify tsconfig.json
    if (file_exists($app_path.'/tsconfig.json')){
      static::updateTSConfig($app_path.'/tsconfig.json',
        $app_path.'/tsconfig.json');
    }

    // Modify Cypress config
    if (file_exists($app_path.'/cypress.json')){
      static::updateCypress($app_path.'/cypress.json',
        $app_path.'/cypress.json');
    }

    $cmd->info('This preset will make the following changes:');
    $changes = [];
    $changes[] = ['resources/src/*', 'A Vue project source code'];
    $changes[] = ['resources/tests/*', 'A Vue project tests files'];
    $changes[] = ['resources/public', 'M moved from default ./public/ and vue\'s static assets'];
    $changes[] = ['routes/web.php', 'M paths for html5 history'];
    $changes[] = ['resources/views/spa.blade.php', 'A SPA view'];
    $changes[] = ['.vscode', 'A (optional) Create Visual Studio Code tasks and launch files'];
    $changes[] = ['node_modules/', 'A From app\'s folder'];

    $rootFiles = array_merge($changes, $app_files);

    $cmd->table(['File/Directory', 'Action/Desc. [R]eplaced [M]oved [A]dded [D]eleted [U]pdated'], $rootFiles);
    $cmd->error('Warning: This procedure is irreversible.');
    if (!$cmd->confirm('Are you sure to continue?')) {
      return;
    }


    // Delete node_modules if exists
    if (is_dir(base_path().'/node_modules')){
      $fs->deleteDirectory(base_path().'/node_modules');
    }

    if ($cmd->confirm('(Optional) Add Visual Studio Code launch and tasks settings?')){

      $vscodepath = base_path('.vscodde');
      if ( !file_exists($vscodepath) ) {
        mkdir($vscodepath, 0755, true);
      }
      copy(__DIR__.'/vuestubs/tasks.json', $vscodepath.'/tasks.json');

      // update settings
      $json = json_decode(file_get_contents(__DIR__.'/vuestubs/launch.json'), true);

      $urlpath = $cmd->ask('Enter project url path (e.g. http://localhost/myapp/, http://mylocaldomain.local)');
      if (filter_var($urlpath, FILTER_VALIDATE_URL) !== false){
        $json['configurations'][3]['url'] = $urlpath;
        $json['configurations'][5]['url'] = $urlpath;
      }else{
        $cmd->error('Invalid URL, edit .vscode/launch.json manually');
        $json['configurations'][3]['url'] = "http://localhost/";
        $json['configurations'][5]['url'] = "http://localhost/";
      }

      // detect chrome canary on windows
      if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'){
        $appdata = getenv('LOCALAPPDATA');
        $chromepath = $appdata . '\Google\Chrome SxS\Application\chrome.exe' ;
        if (file_exists($chromepath)){
          if ($cmd->confirm('Use Chrome Canary instead?')){
            $json['configurations'][5]['runtimeExecutable'] = $chromepath;
          }
        }
      }

      file_put_contents(
        $vscodepath.'/launch.json',
        json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ).PHP_EOL
      );

    }

    // Change web.php and add spa.blade.php
    copy(__DIR__.'/vuestubs/web.php', base_path().'/routes/web.php');
    copy(__DIR__.'/vuestubs/spa.blade.php', resource_path().'/views/spa.blade.php');

    // Move src files to resources directory
    $fs->moveDirectory($app_path.'/src', resource_path().'/src');

    // Move tests files to resources directory
    if(file_exists($app_path.'/tests')){
      $fs->moveDirectory($app_path.'/tests', resource_path().'/tests');
    }

    // Move public files to resources directory
    $fs->moveDirectory($app_path.'/public', resource_path().'/public');

    // Delete .git directory if exists in app
    if (file_exists($app_path.'/.git')){
      $fs->deleteDirectory($app_path.'/.git');
    }

    // Delete gist directory if exists in app
    if (file_exists($app_path.'/dist')){
      $fs->deleteDirectory($app_path.'/dist');
    }

    // Move node_modules from resources/app in order to save bandwith, since `vue create` forces to download them
    $fs->moveDirectory($app_path.'/node_modules', base_path().'/node_modules');

    // Move all other files to base path
    foreach($app_files as $app_file){
      $fs->move($app_path.'/'.$app_file, base_path().'/'.$app_file);
    }

    // Move laravel's public files to resources/public
    $publicFiles = scandir(public_path());
    $excludeFiles = ['.', '..'];
    foreach($publicFiles as $pubFile){
      if (!in_array($pubFile, $excludeFiles)){
        if (is_dir(public_path().'/'.$pubFile)){
          $fs->moveDirectory(public_path().'/'.$pubFile, resource_path().'/public/'.$pubFile);
        }
        else{
          $fs->move(public_path().'/'.$pubFile, resource_path().'/public/'.$pubFile);
        }
      }
    }

    // Delete vue app directory
    $fs->deleteDirectory($app_path);
    $cmd->info('All changes for Vue-Cli preset has been done!');
    $cmd->info('Install/update node packages then build with `yarn build` or `npm run build`!');
  }
}
