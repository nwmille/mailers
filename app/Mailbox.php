<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Mailbox extends Model
{
    private $serverConnection;
    public $emails = array();

    public function __construct($user, $password, $folder = "INBOX", $mailServer = null)
    {
        parent::__construct();

        $this->connect($user, $password, $folder, $mailServer);

    }

    private function connect($user, $password, $folder, $mailServer)
    {
        $mailServer = $mailServer == null ? env("MAIL_SERVER") : $mailServer;
        $mailServer = "{" . $mailServer . "/ssl/novalidate-cert}$folder";

        try {
            $inbox = imap_open(
                     $mailServer,
                $user,
                $password,
                CL_EXPUNGE,
                3,
                array('DISABLE_AUTHENTICATOR' => array('GSSAPI', 'NTLM')));
            $this->serverConnection = $inbox;
        } catch (\ErrorException $e) {
            Log::error('Failed to access mail server.');
            Log::error($e);
            mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to access mail server', $e );
        }

    }


    public function search($searchBy = "ALL", $criteria = null)
    {

        switch ($searchBy) {
            case "ALL":
                $this->emails = imap_search($this->getServerConnection(), 'ALL', SE_UID);
                break;
            case "SINCE":
                $this->emails = imap_search($this->getServerConnection(),  "SINCE "."05-Nov-2018", SE_UID);
                break;
            case "UNSEEN":
                $this->emails = imap_search($this->getServerConnection(), $searchBy, SE_UID);
                break;
        }

    }

    public function getServerConnection ()
    {
        return $this->serverConnection;
    }

    public function closeConnection()
    {
        imap_close($this->serverConnection, CL_EXPUNGE);
    }

}
