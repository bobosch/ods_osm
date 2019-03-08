<?php

declare(strict_types=1);

namespace Bobosch\OdsOsm\Wizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the icon with link parameters to open the element browser.
 * Used in InputLinkElement.
 */
class InputControl extends AbstractNode
{
    /**
     *  control
     *
     * @return array As defined by FieldControl class
     */
    public function render(): array
    {
        $options = $this->data['renderData']['fieldControlOptions'];

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $windowOpenParameters = $options['windowOpenParameters'] ?? 'height=800,width=1000,status=0,menubar=0,scrollbars=1';

        $linkBrowserArguments = [];

        if ($this->data['tableName'] === 'tx_odsosm_vector') {
            $linkBrowserArguments['mode'] = 'vector';
        } else {
            $linkBrowserArguments['mode'] = 'point';
        }

        $urlParameters = [
            'P' => [
                'params' => $linkBrowserArguments,
                'table' => $this->data['tableName'],
                'uid' => $this->data['databaseRow']['uid'],
                'pid' => $this->data['databaseRow']['pid'],
                'field' => $this->data['fieldName'],
                'formName' => 'editform',
                'itemName' => $itemName,
                'hmac' => GeneralUtility::hmac('editform' . $itemName, 'wizard_js'),
                'fieldChangeFunc' => $parameterArray['fieldChangeFunc'],
                'fieldChangeFuncHash' => GeneralUtility::hmac(serialize($parameterArray['fieldChangeFunc'])),
            ],
        ];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('coordinatepicker', $urlParameters);
        $onClick = [];
        $onClick[] = 'this.blur();';
        $onClick[] = 'vHWin=window.open(';
        $onClick[] = GeneralUtility::quoteJSvalue($url);
        $onClick[] = '+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(';
        $onClick[] = 'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
        $onClick[] = ')';
        $onClick[] = '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(';
        $onClick[] = GeneralUtility::quoteJSvalue($itemName);
        $onClick[] = '),';
        $onClick[] = '\'\',';
        $onClick[] = GeneralUtility::quoteJSvalue($windowOpenParameters);
        $onClick[] = ');';
        $onClick[] = 'vHWin.focus();';
        $onClick[] = 'return false;';

        return [
            'iconIdentifier' => 'ods_osm',
            'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:coordinatepicker.search_coordinates',
            'linkAttributes' => [
                'class' => 'coordinatepicker ',
                'data-id' => $this->data['databaseRow']['uid'],
                'onClick' => implode('', $onClick),
            ],
        ];

    }
}
