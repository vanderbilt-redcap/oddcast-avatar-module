<?php

$pid = $module->getProjectId();

$module->sendErrorEmail("Analytics data is not being captured because the Analytics module is not enabled on project $pid! Please enable it ASAP to avoid further data loss!");