<?php

foreach ($js_groupings as $key => $groupings) {
    foreach ($groupings as $file => $target) {
        if ($target == 'include/javascript/sugar_grp7.min.js') {
            $js_groupings[$key]['custom/javascript/systemdataroute.js'] = 'include/javascript/sugar_grp7.min.js';
        }
        break;
    }
}
