<?php
  $short_options = 'u:h:p:';
  $long_options = ['file:', 'dry_run', 'create_table', 'help'];

  $parsed_options = getopt($short_options, $long_options);


  var_dump($parsed_options);
?>