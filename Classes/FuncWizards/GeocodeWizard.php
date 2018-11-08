<?php

namespace Bobosch\OdsOsm\FuncWizards;

use Bobosch\OdsOsm\Div;
use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
     * @return string Output HTML for the module
     * @throws \TYPO3\CMS\Core\Exception
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
     * @return string HTML code with geocoding results
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function geocode($mode)
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();


        if ($mode != 'all' && $mode != 'missing') {
            //wrong mode
            /** @var FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'Invalid geocoding mode',
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $defaultFlashMessageQueue->enqueue($message);
            return $defaultFlashMessageQueue->renderFlashMessages();
        }


        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);

        $builder = $pool->getQueryBuilderForTable('tt_address');
        $query = $builder->select('*')->from('tt_address')
            ->where('pid = ' . $this->pObj->id);

        if ($mode == 'missing') {
            $query->andWhere('longitude=0 OR longitude IS NULL', 'latitude=0 OR latitude IS NULL');
            print_r($query->getSQL());
        }

        $res = $query->execute();

        $count = 0;
        $updated = 0;
        $html = '';
        while ($row = $res->fetch(FetchMode::ASSOCIATIVE)) {
            ++$count;

            if (!Div::updateAddress($row)) {
                //not updated
                continue;
            }

            $num = $builder->getConnection()->update(
                'tt_address',
                array(
                    'latitude' => $row['lat'],
                    'longitude' => $row['lon']
                ),
                ['uid' => intval($row['uid'])]
            );

            if ($num == 0) {
                continue;
            }

            ++$updated;
        }

        /** @var FlashMessage $message */
        $message = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            'Updated ' . $updated . ' of ' . $count . ' address records',
            '',
            $updated == $count ? \TYPO3\CMS\Core\Messaging\FlashMessage::OK : \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
        );

        $defaultFlashMessageQueue->enqueue($message);
        $html .= $defaultFlashMessageQueue->renderFlashMessages();

        return $html . ' <br /><br />';
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
            ->where('pid = ' . $this->pObj->id)
            ->andWhere('latitude = 0 OR latitude IS NULL')
            ->andWhere('longitude = 0 OR longitude IS NULL')
            ->execute()->fetch(FetchMode::NUMERIC);

        $numAll = $builder->count('uid')
            ->from('tt_address')
            ->where('pid = ' . $this->pObj->id)
            ->execute()->fetch(FetchMode::NUMERIC);

        $html = ' <p>'
            . ' <b>' . $num[0] . ' </b > addresses without coordinates on this page'
            . ' </p > ';
        $html .= '<p> '
            . '<b> ' . $numAll[0] . '</b > addresses in total on this page'
            . ' </p> ';

        $html .= '<br />'
            . '<form action = "' . $this->getFormUrl() . '" method = "post" > '
            . '<input type = "radio" name = "geocode" id = "geocode-missing" value = "missing" checked = "checked" />'
            . '<label for="geocode-missing" > Update <b> missing</b> coordinates only </label> '
            . '<br />'
            . '<input type = "radio" name = "geocode" id = "geocode-all" value = "all" />'
            . '<label for="geocode-all" > Update coordinates of <b> all </b> addresses</label> '
            . '<br />'
            . '<br />'
            . '<input type = "submit" name = "start" value = "Start geocoding" />';

        return $html;
    }

    /**
     * @return string
     */
    protected function getFormUrl()
    {
        $urlParams = $this->pObj->MOD_SETTINGS;
        $urlParams['id'] = $this->pObj->id;
        return $this->pObj->doc->scriptID . ' ? ' . GeneralUtility::implodeArrayForUrl(
                '',
                $urlParams
            );
    }

}

?>
