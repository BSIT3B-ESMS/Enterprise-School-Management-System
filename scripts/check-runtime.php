<?php
if(PHP_SAPI!=='cli')exit(1);
$missing=array_filter(['pdo_mysql','openssl','mbstring','fileinfo'],fn($extension)=>!extension_loaded($extension));
if(PHP_VERSION_ID<80400||$missing){fwrite(STDERR,'PHP 8.4+ and required extensions are needed. Missing: '.implode(', ',$missing).PHP_EOL);exit(1);}
