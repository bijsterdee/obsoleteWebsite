<?php

namespace FaynticServices\Website\Controller\Account\Product;

use FaynticServices\Website\Controller\Account\Product;
use FaynticServices\Website\Model\AccountFtpQuery;
use FaynticServices\Website\Controller\Account\Product\Ftp\Validator;

class Ftp extends Product
{
    /** @var int */
    protected $uid;

    /** @var int */
    protected $gid;

    /** @var string */
    protected $home;

    /** @var Validator */
    private $validator;

    protected function preDispatch()
    {
        parent::preDispatch();

        $this->validator = new Validator();

        if ($systemUser = posix_getpwnam("customer-{$this->account->getId()}")) {
            $this->uid = $systemUser['uid'];
            $this->gid = $systemUser['gid'];
            $this->home = $systemUser['dir'];
        }
    }

    /**
     * @return Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    protected function handleDefault()
    {
        $this->setTemplate('Account/Product/Ftp.twig', [
            'users' => AccountFtpQuery::create()->filterByAccount($this->account)->find()
        ]);
    }

    protected function handleAjaxValidateUsername()
    {
        try {
            $this->getValidator()->validateUsername(filter_input(INPUT_POST, 'value'));
        } catch (\UnexpectedValueException $exception) {
            echo $exception->getMessage();
        }
    }

    protected function handleAjaxValidatePassword()
    {
        try {
            $this->getValidator()->validatePassword(filter_input(INPUT_POST, 'value'));
        } catch (\UnexpectedValueException $exception) {
            echo $exception->getMessage();
        }
    }

    protected function handleAjaxValidateLocation()
    {
        try {
            $this->getValidator()->validateLocation($this->home.filter_input(INPUT_POST, 'value'));
        } catch (\UnexpectedValueException $exception) {
            echo $exception->getMessage();
        }
    }

    protected function handleAjaxDirectories()
    {
        $filePath = filter_input(INPUT_GET, 'path');

        $directories = array();
        if ($searchDirectory = realpath("{$this->home}" . $filePath ? : '/')) {
            /** @var \DirectoryIterator[] $entries */
            $entries = new \DirectoryIterator($searchDirectory);
            foreach ($entries as $entry) {
                if (!$entry->isDot() && $entry->isDir()) {
                    $directories[] = ['value' => str_replace($this->home, '', $entry->getPathname())];
                }
            }
            $_SESSION['last_valid_ftp_directories'] = $directories;
        } elseif (isset($_SESSION['last_valid_ftp_directories'])) {
            $directories = $_SESSION['last_valid_ftp_directories'];
        }

        echo json_encode($directories);
    }
}
