<?php

event\listen('say.name', function($name) {
    echo "<p>Hello $name!</p>";
});

event\listen('say.name', function($name) {
    echo "<p>Are you here $name?</p>";
});

event\listen('say.name', function() {
    echo '<p>Olmaz</p>';
});

event\emit('say.name', ['Deniz']);
