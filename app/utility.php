<?php


namespace App;


use phpseclib\Net\SFTP;
class utility
{

    public static function SFTPmoveFiles($fnRemotePath, $fnLocalPath,  array $server, $sftp_connection_option = SFTP::SOURCE_LOCAL_FILE)
    {
        $retVal = false;
        $sftp_connection = new SFTP($server['address']);

        if ($sftp_connection->login($server['un'], $server['pw']))
        {
            $retVal = $sftp_connection->put($fnRemotePath, $fnLocalPath, $sftp_connection_option);
        }

        return $retVal;
    }

    public static function explodeFileKey($file_key)
    {
        return explode('!', $file_key);
    }


}
