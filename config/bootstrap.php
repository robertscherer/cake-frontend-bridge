<?php

use Cake\Network\Request;

Request::addDetector('dialog', function(Request $request) {
    return $request->getQuery('dialog_action') === '1' || $request->getParam('dialogAction') === true;
});

Request::addDetector('jsonAction', function(Request $request) {
    return $request->getQuery('json_action') === '1' || $request->getParam('jsonAction') === true;
});