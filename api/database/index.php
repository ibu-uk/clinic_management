<?php
// Prevent directory listing
header('HTTP/1.1 403 Forbidden');
echo 'Access denied';
