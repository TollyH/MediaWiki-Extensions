<?php
use MediaWiki\MediaWikiServices;

class SpecialInfoboxGenerator extends SpecialPage {
	function __construct() {
		parent::__construct( 'InfoboxGenerator' );
	}

	function execute( $par ) {
		$output = $this->getOutput();
        $request = $this->getRequest();
        $queryValues = $request->getQueryValuesOnly();

        $output->setPageTitle( 'Infobox Generator' );
        if ($par == "") {
            $output->addHtml('<style>textarea.oo-ui-inputWidget-input{height:200px;}</style>');
            $fields = [
                'prename' => [
                    'type' => 'text',
                    'name' => 'prename',
                    'label' => 'Pre-name',
                    'placeholder' => 'Sir / Dame / etc',
                    'required' => false,
                    'section' => 'name-section',
                ],
                'mainname' => [
                    'type' => 'text',
                    'name' => 'mainname',
                    'label' => 'Name',
                    'placeholder' => 'Joe Bloggs / Eva Smith / Microsoft / etc',
                    'required' => true,
                    'section' => 'name-section',
                ],
                'postname' => [
                    'type' => 'text',
                    'name' => 'postname',
                    'label' => 'Post-name',
                    'placeholder' => 'CBE / FRS / etc',
                    'required' => false,
                    'section' => 'name-section',
                ],
                'altname' => [
                    'type' => 'text',
                    'name' => 'altname',
                    'label' => 'Alternate name',
                    'placeholder' => '宮本茂 / etc',
                    'required' => false,
                    'section' => 'name-section',
                ],
                'topimage' => [
                    'type' => 'text',
                    'name' => 'topimage',
                    'label' => 'Top image filename',
                    'placeholder' => 'Do not include the "File:" prefix',
                    'required' => false,
                    'section' => 'topimage-section',
                ],
                'topimage-caption' => [
                    'type' => 'text',
                    'name' => 'topimage-caption',
                    'label' => 'Top image caption',
                    'placeholder' => 'Joe Bloggs at the 2020 XYZ conference / Microsoft logo / etc',
                    'required' => false,
                    'section' => 'topimage-section',
                ],
                'field-names' => [
                    'type' => 'textarea',
                    'name' => 'field-names',
                    'label' => 'Field names',
                    'placeholder' => "Separate\nField\nNames\n*Onto\nDifferent\nLines",
                    'help' => 'Put an asterisk at the start of any name you want to be a list field. Use \'\n\' to insert a new line',
                    'required' => false,
                    'section' => 'fields-section',
                ],
                'field-contents' => [
                    'type' => 'textarea',
                    'name' => 'field-contents',
                    'label' => 'Field contents',
                    'placeholder' => "Separate\nField\nContents\nOnto,Item 2,Item 3\nDifferent\nLines",
                    'help' => 'In list fields, separate list items with a comma. Use \'\n\' to insert a new line',
                    'required' => false,
                    'section' => 'fields-section',
                ],
                'bottomimage-caption' => [
                    'type' => 'text',
                    'name' => 'bottomimage-caption',
                    'label' => 'Bottom image title',
                    'placeholder' => 'Signature / etc',
                    'required' => false,
                    'section' => 'bottomimage-section',
                ],
                'bottomimage' => [
                    'type' => 'text',
                    'name' => 'bottomimage',
                    'label' => 'Bottom image filename',
                    'placeholder' => 'Do not include the "File:" prefix',
                    'required' => false,
                    'section' => 'bottomimage-section',
                ],
            ];
            $context = new DerivativeContext($this->getContext());
            $context->setTitle($this->getPageTitle()); // Remove subpage
            $form = HTMLForm::factory('ooui', $fields, $context)
                ->setMethod('get')
                ->setAction('Special:InfoboxGenerator/view')
                ->setWrapperLegend('Infobox parameters')
                ->addHeaderText('Fill in the form below to generate an infobox that you can copy onto a page')
                ->setSubmitText('Generate')
                ->prepareForm();
            $form->displayForm(false);
        }
        else if ($par == "view") {
            if (!array_key_exists('prename', $queryValues) or !array_key_exists('mainname', $queryValues) or !array_key_exists('postname', $queryValues) or
                !array_key_exists('altname', $queryValues) or !array_key_exists('topimage', $queryValues) or !array_key_exists('topimage-caption', $queryValues) or
                !array_key_exists('field-names', $queryValues) or !array_key_exists('field-contents', $queryValues) or
                !array_key_exists('bottomimage-caption', $queryValues) or !array_key_exists('bottomimage', $queryValues)) {
                    $output->showErrorPage('error', 'missingparamserror');
                    return;
                }
            if ($queryValues['field-names'] != '') {
                $fieldNames = explode("\n", str_replace("\r\n", "\n", $queryValues['field-names']));
                $fieldContents = explode("\n", str_replace("\r\n", "\n", $queryValues['field-contents']));
            }
            else {
                $fieldNames = [];
                $fieldContents = [];
            }
            if (count($fieldNames) != count($fieldContents)) {
                $output->showErrorPage('error', 'inequalfieldserror');
                return;
            }

            $output->addHtml("<style>.tolly-wiki-infobox-table{float:unset}</style>");
            $output->addSubtitle("<a href=\"" . str_replace("$1", "Special:InfoboxGenerator", $this->getConfig()->get('ArticlePath')) . "\">Return to the form</a>");
            $output->addWikiTextAsInterface("===This is what your Infobox will look like:===");
            $output->addWikiTextAsInterface("<small>''Note that it will be floating to the right on the actual page''</small>");

            $finalInfobox = "{| class=\"tolly-wiki-infobox-table\"";
            $finalInfobox .= "\n|-";
            $finalInfobox .= "\n|colspan=\"2\"| <div class=\"tolly-wiki-infobox-prename\">" . $queryValues['prename'] . "<br><div class=\"tolly-wiki-infobox-mainname\">";
            $finalInfobox .= $queryValues['mainname'] . "</div>" . $queryValues['postname'] . "</div>";
            $finalInfobox .= "\n|-";
            if ($queryValues['altname'] != '') {
                $finalInfobox .= "\n|colspan=\"2\" class=\"tolly-wiki-infobox-altname\"| " . $queryValues['altname'];
                $finalInfobox .= "\n|-";
            }
            if ($queryValues['topimage'] != '' or $queryValues['topimage-caption'] != '') {
                $finalInfobox .= "\n|colspan=\"2\" class=\"tolly-wiki-infobox-topimage\"| [[File:";
                $finalInfobox .= $queryValues['topimage'] . "|frameless|center|" . $queryValues['topimage-caption'] . "|250px]]";
                $finalInfobox .= "<div class=\"tolly-wiki-infobox-topimage-caption\">" . $queryValues['topimage-caption'] . "</div>";
            }
            else {
                $finalInfobox .= "\n|";
            }
            if (count($fieldNames) > 0) {
                for ($i = 0; $i < count($fieldNames); $i++) {
                    $finalInfobox .= "\n|-";
                    $finalInfobox .= "\n| class=\"tolly-wiki-infobox-field\" | '''" . ltrim(str_replace('\n', '<br>', $fieldNames[$i]), '*') ."'''";
                    if ($fieldNames[$i][0] != '*') {
                        $finalInfobox .= "\n| class=\"tolly-wiki-infobox-field\" | " . str_replace('\n', '<br>', $fieldContents[$i]);
                    }
                    else {
                        $fieldList = explode(',', str_replace('\n', '<br>', $fieldContents[$i]));
                        $finalInfobox .= "\n| class=\"tolly-wiki-infobox-field\" |<div class=\"mw-collapsible mw-collapsed tolly-wiki-infobox-list\">";
                        $finalInfobox .= "\n<div class=\"tolly-wiki-infobox-list-header\">List (" . count($fieldList) . ")</div>\n<div class=\"mw-collapsible-content\">";
                        foreach ($fieldList as $fieldListItem) {
                            $finalInfobox .= "\n*" . $fieldListItem;
                        }
                        $finalInfobox .= "\n</div>\n</div>";
                    }
                }
            }
            if ($queryValues['bottomimage'] != '' or $queryValues['bottomimage-caption'] != '') {
                $finalInfobox .= "\n|-";
                $finalInfobox .= "\n| colspan=\"2\" class=\"tolly-wiki-infobox-bottomimage-caption\" |'''" . $queryValues['bottomimage-caption'] ."'''";
                $finalInfobox .= "\n|-";
                $finalInfobox .= "\n| colspan=\"2\" |[[File:" . $queryValues['bottomimage'] . "|frameless|center]]";
            }
            $finalInfobox .= "\n|}";
            $output->addWikiTextAsInterface($finalInfobox);
            $output->addWikiTextAsInterface("===Copy the following code to the top of your page:===");
            $output->addWikiTextAsInterface("<syntaxhighlight lang=\"text\">" . $finalInfobox . "</syntaxhighlight>");
        }
        else {
            $output->showErrorPage('error', 'invalidsubpageerror', [$par]);
        }
	}

    function getGroupName() {
        return 'wiki';
    }
}