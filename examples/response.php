<?php

response\output('content', 200, ['Content-Type' => 'text/html']);
response\html('content');
response\json(['json' => 'content']);
response\text('Text content');
response\redirect('/url');
response\view('view/file', compact('posts'));

response\html('not found', 404);
response\json(['message' => '404 page not found'], 404);
