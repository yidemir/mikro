<?php

lang\path(__DIR__ . '/languages');
lang\lang('tr');

lang\get('file.hello-world'); // "Hello world"
lang\phrase('Merhaba dünya'); // "Hello world"

lang\get('file.attributes.name'); // "İsim"
lang\get('file.attributes.location'); // "Konum"
