<?php

$route = [];

$route['default_controller'] = 'home';
$route['info/(:any)'] = 'home/info/$1';
$route['project/(:num)'] = 'home/project/$1';