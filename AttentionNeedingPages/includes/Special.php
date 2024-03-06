<?php
use MediaWiki\MediaWikiServices;

class SpecialAttentionNeedingPages extends SpecialPage {
	function __construct() {
		parent::__construct( 'AttentionNeedingPages' );
	}

	function execute( $par ) {
        $output = $this->getOutput();
        $output->setPageTitle( 'Attention Needing Pages' );
        $request = $this->getRequest();
        $queryValues = $request->getQueryValuesOnly();

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $dbr = $lb->getConnectionRef( DB_REPLICA );

        $config = $this->getConfig();
        $allAttentionTemplateNames = $config->get( 'AttentionTemplateNames' );

        if (!is_array($allAttentionTemplateNames) || count($allAttentionTemplateNames) == 0) {
            $output->showErrorPage('error', 'notemplateserror');
            return;
        };

        if (array_key_exists('templates', $queryValues)) {
            $attentionTemplateNames = $queryValues['templates'];
            if (!is_array($attentionTemplateNames) || count($attentionTemplateNames) == 0) {
                $output->showErrorPage('error', 'noprovidedtemplateserror');
                return;
            };
            foreach ($attentionTemplateNames as $templateName) {
                if (!in_array($templateName, $allAttentionTemplateNames)) {
                    $output->showErrorPage('error', 'badtemplatenameerror');
                    return;
                };
            };
        } else {
            $attentionTemplateNames = $allAttentionTemplateNames;
        };

        $attentionPageNames = [];
        foreach ($attentionTemplateNames as $templateName) {
            $attentionPageIds = $dbr->select(
                [ 'templatelinks', 'linktarget' ],
                'templatelinks.tl_from',
                [ 'linktarget.lt_title' => str_replace(' ', '_', $templateName) ],
                __METHOD__,
                [],
                [ 'linktarget' => [ 'JOIN', 'templatelinks.tl_target_id = linktarget.lt_id' ] ]
            );
            foreach ($attentionPageIds as $pageId) {
                $pageName = $dbr->select(
                    'page',
                    'page_title',
                    [
                        'page_id' => $pageId->tl_from
                    ],
                    __METHOD__
                );
                $pageName = str_replace('_', ' ', $pageName->fetchObject()->page_title);
                if (!array_key_exists($pageName, $attentionPageNames)) {
                    $attentionPageNames[$pageName] = [$templateName];
                } else {
                    $attentionPageNames[$pageName][] = $templateName;
                };
            };
        };

        asort($attentionPageNames);

        $formMultiselectOptions = [];
        foreach ($allAttentionTemplateNames as $templateName) {
            $formMultiselectOptions[$templateName] = $templateName;
        };
        $fields = [
            'templates' => [
                'type' => 'multiselect',
                'name' => 'templates',
                'options' => $formMultiselectOptions,
                'default' => $attentionTemplateNames,
            ],
        ];
        $context = new DerivativeContext($this->getContext());
        $context->setTitle($this->getPageTitle()); // Remove subpage
        $form = HTMLForm::factory('ooui', $fields, $context)
            ->setMethod('get')
            ->setAction('Special:AttentionNeedingPages')
            ->setWrapperLegend('Selected Templates')
            ->setSubmitText('Select')
            ->prepareForm();
        $form->displayForm(false);

		$output->addWikiTextAsInterface(
            'The following is a list of \'\'\'' . count($attentionPageNames) . '\'\'\' pages marked as needing attention by \'\'\'' . count($attentionTemplateNames) .'\'\'\' different templates:'
        );
        foreach ($attentionPageNames as $pageName => $templateNames) {
            $output->addWikiTextAsInterface('*[[' . $pageName . ']] (' . implode(", ", $templateNames) . ")");
        };
	}

    function getGroupName() {
        return 'maintenance';
    }
}
