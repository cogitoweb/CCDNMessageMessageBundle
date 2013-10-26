<?php

/*
 * This file is part of the CCDNMessage MessageBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/>
 *
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CCDNMessage\MessageBundle\Model\Manager;

use Symfony\Component\Security\Core\User\UserInterface;

use CCDNMessage\MessageBundle\Model\Manager\ManagerInterface;
use CCDNMessage\MessageBundle\Model\Manager\BaseManager;

use CCDNMessage\MessageBundle\Entity\Folder;

/**
 *
 * @category CCDNMessage
 * @package  MessageBundle
 *
 * @author   Reece Fowell <reece@codeconsortium.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  Release: 2.0
 * @link     https://github.com/codeconsortium/CCDNMessageMessageBundle
 *
 */
class FolderManager extends BaseManager implements ManagerInterface
{
	public function saveFolder(Folder $folder)
	{
		return $this->persist($folder)->flush();
	}

    /**
     *
     * @access public
     * @param  \Symfony\Component\Security\Core\User\UserInterface    $user
     * @return \CCDNMessage\MessageBundle\Model\Manager\FolderManager
     */
    public function setupDefaults(UserInterface $user)
    {
        if (! is_object($user) || ! $user instanceof UserInterface) {
            throw new \Exception('User id "' . $userId . '" is invalid!');
        }

        $folderNames = Folder::$defaultSpecialTypes; //array(1 => 'inbox', 2 => 'sent', 3 => 'drafts', 4 => 'junk', 5 => 'trash');

        foreach ($folderNames as $key => $folderName) {
            $folder = new Folder();
            $folder->setOwnedByUser($user);
            $folder->setName($folderName);
            $folder->setSpecialType($key);
            $folder->setCachedReadCount(0);
            $folder->setCachedUnreadCount(0);
            $folder->setCachedTotalMessageCount(0);

            $this->persist($folder);
        }

        return $this;
    }

    /**
     *
     * @access public
     * @param  \Symfony\Component\Security\Core\User\UserInterface    $user
     * @param  Array()                                                $folders
     * @return \CCDNMessage\MessageBundle\Model\Manager\FolderManager
     */
    public function updateAllFolderCachesForUser(UserInterface $user, $folders)
    {
        foreach ($folders as $folder) {
            $this->updateFolderCounterCaches($folder);
        }

        $this->flush();

        //$this->managerBag->getRegistryManager()->updateCacheUnreadMessagesForUser($user, null, $folders)->flush();

        return $this;
    }

    /**
     *
     * @access public
     * @param  \CCDNMessage\MessageBundle\Entity\Folder               $folder
     * @return \CCDNMessage\MessageBundle\Model\Manager\FolderManager
     */
    public function updateFolderCounterCaches(Folder $folder)
    {
        $readCount = $this->getReadCounterForFolderById($folder->getId(), $folder->getOwnedByUser()->getId());
        $readCount = $readCount['readCount'];
        $unreadCount = $this->getUnreadCounterForFolderById($folder->getId(), $folder->getOwnedByUser()->getId());

        $unreadCount = $unreadCount['unreadCount'];
        $totalCount = ($readCount + $unreadCount);

        $folder->setCachedReadCount($readCount);
        $folder->setCachedUnreadCount($unreadCount);
        $folder->setCachedTotalMessageCount($totalCount);

        $this->persist($folder);

        return $this;
    }
}