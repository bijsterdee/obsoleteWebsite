<?php

namespace FaynticServices\Website\Controller\Account;

use FaynticServices\Website\Configuration;
use FaynticServices\Website\Controller\Auth;
use FaynticServices\Website\Model\AccountContact;
use FaynticServices\Website\Model\Base\AccountSupportQuery;
use Zendesk\API\Client;
use Zendesk\API\Tickets;

class Support extends Auth
{
    /** @var Client */
    protected $zendesk;

    /** @var int */
    protected $zendeskUserId;

    public function preDispatch()
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->get('configuration');

        $this->zendesk = new Client($configuration->vendor->zendesk->subdomain, $configuration->vendor->zendesk->username);
        $this->zendesk->setAuth('token', $configuration->vendor->zendesk->token);

        // todo validate the ZendeskUserId once a while
        $accountSupport = AccountSupportQuery::create()->filterByAccount($this->account)->findOneOrCreate();
        if (!$accountSupport->getZendeskUserId()) {
            try {
                $zendeskUserId = $this->determineZendeskUserId($this->account->getAccountContact());
            } catch (\Exception $exception) {
                try {
                    $this->zendesk->users()->create([
                        'name' => $this->account->getAccountContact()->getName(),
                        'email' => $this->account->getAccountContact()->getEmail()
                    ]);
                    $zendeskUserId = $this->determineZendeskUserId($this->account->getAccountContact());
                } catch (\Exception $exception) {
                    $accountSupport->setZendeskUserId(null);
                    $accountSupport->save();
                    $this->setStatus("Support isn't available at this moment, for further information please send us a mail to support@fayntic.com");
                    $this->redirect('/');
                }
            }
            if (isset($zendeskUserId)) {
                $accountSupport->setZendeskUserId($zendeskUserId);
                $accountSupport->save();
            }
        }
        $this->zendeskUserId = $accountSupport->getZendeskUserId();
        parent::preDispatch();
    }

    public function handleDefault()
    {
        /** @var Tickets $tickets */
        $tickets = $this->zendesk->tickets()->findAll(array('user_id' => $this->zendeskUserId, 'ccd' => null));
        $this->setTemplate('Account/support.twig', [
            'tickets' => $tickets->tickets
        ]);
    }

    /**
     * @param AccountContact $accountContact
     * @return int
     * @throws \Exception
     */
    private function determineZendeskUserId(AccountContact $accountContact)
    {
        $zendeskUsers = $this->zendesk->users()->findAll()->users;
        foreach ($zendeskUsers as &$zendeskUser) {
            $zendeskUser = (array)$zendeskUser;
        }
        $zendeskUsers = array_column($zendeskUsers, 'id', 'email');
        if (isset($zendeskUsers[$accountContact->getEmail()])) {
            return $zendeskUsers[$accountContact->getEmail()];
        }
        throw new \Exception('No user could be found');
    }
}
