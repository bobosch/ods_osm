<?php

namespace Bobosch\OdsOsm\FuncWizards;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mass-update geo coordinates in address records
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class GeocodeWizard extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * Main function
     *
     * @returnstring Output HTML for the module
     */
    public function main()
    {
        $html = '';
        if (isset($_POST['start']) && isset($_POST['geocode'])) {
            $html .= $this->geocode($_POST['geocode']);
        }

        $html .= $this->overview();

        return $html;
    }

    /**
     * Run the geocoding process.
     *
     * @param string Geocoding mode: "missing" or "all"
     *
     * @return string HTML code with geocoding results
     */
    protected function geocode($mode)
    {
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();


        if ($mode != 'all' && $mode != 'missing') {
            //wrong mode
            $message = GeneralUtility::makeInstance(
                'TYPO3\CMS\Core\Messaging\FlashMessage',
                'Invalid geocoding mode',
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $defaultFlashMessageQueue->enqueue($message);
            return $defaultFlashMessageQueue->renderFlashMessages();
        }

        if ($mode == 'missing') {
            $where = 'longitude = 0 AND latitude = 0';
        } else {
            $where = '1';
        }

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*', 'tt_address', $where . ' AND pid = ' . $this->pObj->id
        );

        $count = 0;
        $updated = 0;
        $html = '';
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $oldRow = $row;
            ++$count;
            if (!Div::updateAddress($row)) {
                //not updated
                continue;
            }

            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                'tt_address', 'uid = ' . intval($row['uid']),
                array(
                    'latitude' => $row['lat'],
                    'longitude' => $row['lon']
                )
            );

            $err = $GLOBALS['TYPO3_DB']->sql_error();
            if ($err) {
                $message = GeneralUtility::makeInstance(
                    'TYPO3\CMS\Core\Messaging\FlashMessage',
                    'SQL error: ' . htmlspecialchars($err),
                    '',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $defaultFlashMessageQueue->enqueue($message);
                $html .= $defaultFlashMessageQueue->renderFlashMessages();
            }

            ++$updated;
        }

        $message = GeneralUtility::makeInstance(
            'TYPO3\CMS\Core\Messaging\FlashMessage',
            'Updated ' . $updated . ' of ' . $count . ' address records',
            '',
            $updated == $count ? \TYPO3\CMS\Core\Messaging\FlashMessage::OK : \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
        );

        $defaultFlashMessageQueue->enqueue($message);
        $html .= $defaultFlashMessageQueue->renderFlashMessages();

        return $html . '<br/><br/>';
    }

    /**
     * Show overview of uncoded addresses and form to start geocoding
     *
     * @return string HTML code
     */
    protected function overview()
    {
        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);

        $builder = $pool->getQueryBuilderForTable('tt_address');

        $num = $builder->count('uid')
            ->from('tt_address')
            ->where('longitude = 0')
            ->andWhere('latitude = 0')
            ->andWhere('pid = ' . $this->pObj->id)->execute()->fetch();

        $numAll = $builder->count('uid')
            ->from('tt_address')
            ->andWhere('pid = ' . $this->pObj->id)->execute()->fetch();

        $html = '<p>'
            . '<b>' . $num . '</b> addresses without coordinates on this page'
            . '</p>';
        $html .= '<p>'
            . '<b>' . $numAll . '</b> addresses in total on this page'
            . '</p>';

        $html .= '<br/>'
            . '<form action="' . $this->getFormUrl() . '" method="post">'
            . '<input type="radio" name="geocode" id="geocode-missing" value="missing" checked="checked"/>'
            . '<label for="geocode-missing">Update <b>missing</b> coordinates only</label>'
            . '<br/>'
            . '<input type="radio" name="geocode" id="geocode-all" value="all"/>'
            . '<label for="geocode-all">Update coordinates of <b>all</b> addresses</label>'
            . '<br/>'
            . '<br/>'
            . '<input type="submit" name="start" value="Start geocoding" />';

        return $html;
    }

    /**
     * @return string
     */
    protected function getFormUrl()
    {
        $urlParams = $this->pObj->MOD_SETTINGS;
        $urlParams['id'] = $this->pObj->id;
        return $this->pObj->doc->scriptID . '?' . GeneralUtility::implodeArrayForUrl(
                '',
                $urlParams
            );
    }

}

?>
