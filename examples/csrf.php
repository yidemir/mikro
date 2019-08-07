<?php

session_start();

$token = csrf\token(); // string
$field = csrf\field(); // <input type="hidden">...

$isOk = csrf\check($token); // bool
