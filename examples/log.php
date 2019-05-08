<?php

log\path(__DIR__ . '/storage/logs');

log\error('Error occured');
log\info('User logged out');
log\debug(['id' => 5, 'name' => 'foo', 'email' => 'bar@example.net']);
log\write('alert', 'User role added');
