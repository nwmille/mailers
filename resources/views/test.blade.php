
{!! $url =  $fullPath!!}
{!! $content = file_get_contents($url) !!}
<?php
header('Content-Type: application/pdf');
//header('Content-Length: ' . strlen($content));
header('Content-Disposition: inline; filename="YourFileName.pdf"');
//header('Content-Disposition: attachment; filename="' . $fileName . '"');
//header('Cache-Control: private, max-age=0, must-revalidate');
//header('Pragma: public');
//ini_set('zlib.output_compression','0');
?>
{!! die($content) !!}

