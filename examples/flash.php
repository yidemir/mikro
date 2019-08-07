<?php

flash\push('Flash message');
flash\push('Other message');

flash\get(); // ['Flash message', 'Other message']

flash\message('Flash Message');
flash\get('message'); // ['Flash Message']

flash\error('Error Message 1');
flash\error('Error Message 2');
flash\get('error'); // ['Error Message 1', 'Error Message 2']
