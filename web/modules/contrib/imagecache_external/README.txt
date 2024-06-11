========================
ABOUT
------------------------
Imagecache External is a utility module that allows you to store external
images on your server and apply your own imagecache (D6) / Image Styles (D7, D8+).

========================
CONFIGURATION
------------------------
To get the module to work, you need to visit
admin/config/media/imagecache_external and either:

 - Add some domains to the whitelist -or-
 - De-activate whitelist functionality


========================
 USAGE INSTRUCTIONS
------------------------
 In your module or theme, you may call the following theme function to
 process an image via Imagecache External:

Render array:

<?php
  return [
    '#theme' => 'imagecache_external',
    '#uri' => 'https://drupal.org/files/druplicon.large_.png',
    '#style_name' => 'thumbnail',
    '#alt' => 'Druplicon',
  ];
?>

Twig:

{{ 'https://my.web.site/my-image.jpg'|imagecache_external('thumbnail') }}

Theme function (D7):

<?php
  $build = theme('imagecache_external', [
    'uri' => 'https://drupal.org/files/druplicon.large_.png',
    'style_name'=> 'thumbnail',
    'alt' => 'Druplicon'
  ]);

You can also use external images without coding at all by adding an Text or
Link field to a Node Type and then use the Imagecache External Image formatter.

========================
ADDITIONAL RESOURCES
------------------------
View the Imagecache External project page for additional information
https://drupal.org/project/imagecache_external
