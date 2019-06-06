<?php
	/*
	Copyright: Deux Huit Huit 2018
	License: MIT, see the LICENCE file
	http://deuxhuithuit.mit-license.org/
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/preset_selectbox_field/fields/field.preset_selectbox.php');

	class extension_preset_selectbox_field extends Extension {

		const EXT_NAME = 'Preset Selectbox';
		const SETTING_GROUP = 'preset-selectbox';

		/**
		 * private variable for holding the errors encountered when saving
		 * @var array
		 */
		protected $errors = array();

		/**
		 *
		 * Symphony utility function that permits to
		 * implement the Observer/Observable pattern.
		 * We register here delegate that will be fired by Symphony
		 */
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendToHead'
				),
			);
		}

		/**
		 *
		 * Appends file references into the head, if needed
		 * @param array $context
		 */
		public function appendToHead(Array $context) {
			// store de callback array locally
			$c = Administration::instance()->getPageCallback();

			// publish page
			if($c['driver'] == 'publish'){
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/preset_selectbox_field/assets/publish.preset_selectbox.css');
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/preset_selectbox_field/assets/publish.preset_selectbox.js');
			}
		}

		/**
		 *
		 * Delegate fired when the extension is install
		 */
		public function install() {
			return FieldPreset_selectbox::createFieldTable();
		}

		/**
		 *
		 * Delegate fired when the extension is updated (when version changes)
		 * @param string $previousVersion
		 */
		public function update($previousVersion = false) {
			return true;
		}

		/**
		 *
		 * Delegate fired when the extension is uninstall
		 * Cleans settings and Database
		 */
		public function uninstall() {
			return FieldPreset_selectbox::deleteFieldTable();
		}

	}
