<?php

cookie\disable_encryption(true);
cookie\disable_encryption(false); // default false

cookie\set('key', 'value'); // extra parameters: int|string expires, string path, string domain, bool secure, bool httpOnly
cookie\get('key');
