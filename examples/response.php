<?php

response\output('content', 200, 'text/html');
response\html('content');
response\json(['json' => 'content']);
response\redirect('/url');
response\view('view/file', compact('posts'));
