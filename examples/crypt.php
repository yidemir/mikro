<?php

crypt\secret('foobar');

$crypted = crypt\encrypt('foo');
crypt\decrypt($crypted);
