<?php
namespace Vhanla\Vuecli;

use Illuminate\Support\Facades\File;

class Preset extends Vuecli
{
  public static function install()
  {
    $this->info('Hello');
    static::cleanAssetsDirectory();
    static::copyVueStatic();
  }


  public static function cleaAssetsDirectory()
  {
    // File::cleanDirectory(resource_path('assets/sass'));

  }

  public static function copyVueStatic()
  {
    // copy(__DIR__.'/vuets', base_path());
  }
}